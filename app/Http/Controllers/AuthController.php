<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Modules\HRMS\Employees\Models\EmployeeProfile;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const TWO_FACTOR_SETUP_CACHE_PREFIX = 'two_factor_setup_secret:';
    private const TWO_FACTOR_ISSUER = 'Shizzy CMS';
    private const TRUSTED_DEVICE_COOKIE = 'trusted_2fa_device';
    private const TRUSTED_DEVICE_CACHE_PREFIX = 'trusted_2fa_device:';
    private const TRUSTED_DEVICE_DAYS = 14;

    public function showLogin()
    {
        return view('hrms.auth.login');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        $rememberDevice = (bool) ($credentials['remember_device'] ?? false);
        unset($credentials['remember_device']);
        $loginId = (string) ($credentials['email'] ?? '');
        $password = (string) ($credentials['password'] ?? '');

        /** @var User|null $candidate */
        $candidate = User::query()
            ->where('email', $loginId)
            ->first();

        if (! $candidate || ! Auth::attempt(['email' => $candidate->email, 'password' => $password], $rememberDevice)) {
            return back()
                ->withErrors(['email' => 'Invalid credentials.'])
                ->onlyInput('email');
        }

        if ($rememberDevice) {
            config(['session.lifetime' => 60 * 24 * self::TRUSTED_DEVICE_DAYS]);
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        if ($user->isEmployee()) {
            $profile = EmployeeProfile::query()->where('user_id', $user->id)->first();
            if ($profile && $profile->status === 'inactive') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()
                    ->withErrors(['email' => 'Your employment status is Inactive. Please contact the admin.'])
                    ->onlyInput('email');
            }
            if ($profile && $profile->status === 'former') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()
                    ->withErrors(['email' => 'You are no longer associated with the organization and are not permitted access.'])
                    ->onlyInput('email');
            }
            if ($profile && $profile->attendance_locked_at) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return back()
                    ->withErrors(['email' => 'Your login is locked because your attendance was below 9 hours for 3 days in this month. Please contact HR admin for unlock.'])
                    ->onlyInput('email');
            }
        }

        if (! $this->userHasTotpEnabled($user)) {
            $this->startPendingTwoFactorFlow($request, $user);
            $request->session()->put('twofactor_remember_device', $rememberDevice);
            return redirect()->route('twofactor.setup.show');
        }

        if ($rememberDevice && $this->hasTrustedDevice($request, $user, Cache::store())) {
            return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'employee.dashboard');
        }

        $this->startPendingTwoFactorFlow($request, $user);
        $request->session()->put('twofactor_remember_device', $rememberDevice);
        return redirect()->route('twofactor.challenge.show');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function dashboard()
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'employee.dashboard');
    }

    public function showTwoFactorChallenge(Request $request)
    {
        if (! $request->session()->has('twofactor_pending_user_id')) {
            return redirect()->route('login');
        }

        return view('hrms.auth.twofactor');
    }

    public function verifyTwoFactorChallenge(Request $request, CacheRepository $cache)
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $pendingUserId = (int) $request->session()->get('twofactor_pending_user_id', 0);
        if ($pendingUserId <= 0) {
            return redirect()->route('login');
        }

        /** @var User|null $pendingUser */
        $pendingUser = User::query()->find($pendingUserId);
        if (! $pendingUser || ! $this->verifyTotpCodeForUser($pendingUser, (string) $data['code'])) {
            return back()->withErrors(['code' => 'Invalid authenticator code.']);
        }

        Auth::loginUsingId($pendingUserId, true);
        $rememberDevice = (bool) $request->session()->pull('twofactor_remember_device', false);
        $request->session()->forget('twofactor_pending_user_id');
        $request->session()->regenerate();
        if ($rememberDevice) {
            $this->rememberTrustedDevice(Auth::user(), $cache);
        }

        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'employee.dashboard');
    }

    public function showTwoFactorSetup(Request $request, CacheRepository $cache)
    {
        $pendingUserId = (int) $request->session()->get('twofactor_pending_user_id', 0);
        if ($pendingUserId <= 0) {
            return redirect()->route('login');
        }

        /** @var User|null $user */
        $user = User::query()->find($pendingUserId);
        if (! $user) {
            $request->session()->forget('twofactor_pending_user_id');
            return redirect()->route('login');
        }

        if ($this->userHasTotpEnabled($user)) {
            return redirect()->route('twofactor.challenge.show');
        }

        $secret = (string) $cache->get(self::TWO_FACTOR_SETUP_CACHE_PREFIX . $user->id, '');
        if ($secret === '') {
            $secret = $this->generateBase32Secret();
            $cache->put(self::TWO_FACTOR_SETUP_CACHE_PREFIX . $user->id, $secret, now()->addMinutes(15));
        }

        $otpauth = $this->buildOtpAuthUrl(self::TWO_FACTOR_ISSUER, $user->email, $secret);

        return view('hrms.auth.twofactor-setup', [
            'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($otpauth),
            'manualSecret' => $secret,
        ]);
    }

    public function completeTwoFactorSetup(Request $request, CacheRepository $cache)
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $pendingUserId = (int) $request->session()->get('twofactor_pending_user_id', 0);
        if ($pendingUserId <= 0) {
            return redirect()->route('login');
        }

        /** @var User|null $user */
        $user = User::query()->find($pendingUserId);
        if (! $user) {
            $request->session()->forget('twofactor_pending_user_id');
            return redirect()->route('login');
        }

        $secret = (string) $cache->get(self::TWO_FACTOR_SETUP_CACHE_PREFIX . $user->id, '');
        if ($secret === '' || ! $this->verifyTotpCode($secret, (string) $data['code'])) {
            return back()->withErrors(['code' => 'Invalid authenticator code.'])->withInput();
        }

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_enabled_at' => now(),
        ])->save();

        $cache->forget(self::TWO_FACTOR_SETUP_CACHE_PREFIX . $user->id);
        $rememberDevice = (bool) $request->session()->pull('twofactor_remember_device', false);
        $request->session()->forget('twofactor_pending_user_id');
        $request->session()->regenerate();
        Auth::loginUsingId($user->id, true);
        if ($rememberDevice) {
            $this->rememberTrustedDevice($user, $cache);
        }

        return redirect()->route($user->isAdmin() ? 'admin.dashboard' : 'employee.dashboard');
    }

    public function showTwoFactorSettings(Request $request, CacheRepository $cache)
    {
        /** @var User $user */
        $user = $request->user();

        $secret = (string) $cache->get(self::TWO_FACTOR_SETUP_CACHE_PREFIX . $user->id, '');
        if ($secret === '') {
            $secret = $this->generateBase32Secret();
            $cache->put(
                self::TWO_FACTOR_SETUP_CACHE_PREFIX . $user->id,
                $secret,
                now()->addMinutes(15)
            );
        }

        $otpauth = $this->buildOtpAuthUrl(self::TWO_FACTOR_ISSUER, $user->email, $secret);

        return view('hrms.auth.twofactor-settings', [
            'userHasTotp' => $this->userHasTotpEnabled($user),
            'qrUrl' => 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($otpauth),
            'manualSecret' => $secret,
        ]);
    }

    public function enableTwoFactor(Request $request, CacheRepository $cache)
    {
        /** @var User $user */
        $user = $request->user();
        $rules = ['code' => ['required', 'digits:6']];
        if ($this->userHasTotpEnabled($user)) {
            $rules['password'] = ['required', 'current_password'];
        }
        $data = $request->validate($rules);

        $secret = (string) $cache->get(self::TWO_FACTOR_SETUP_CACHE_PREFIX . $user->id, '');
        if ($secret === '' || ! $this->verifyTotpCode($secret, (string) $data['code'])) {
            return back()->withErrors(['code' => 'Invalid authenticator code.'])->withInput();
        }

        $user->forceFill([
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_enabled_at' => now(),
        ])->save();
        $this->clearTrustedDevices($user, $cache);

        $cache->forget(self::TWO_FACTOR_SETUP_CACHE_PREFIX . $user->id);

        return back()->with('status', 'Authenticator-based 2FA is enabled for this device profile.');
    }

    public function disableTwoFactor(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_enabled_at' => null,
        ])->save();
        $this->clearTrustedDevices($user, Cache::store());

        return back()->with('status', 'Authenticator-based 2FA has been disabled.');
    }

    public function adminResetUserTwoFactor(User $user)
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_enabled_at' => null,
        ])->save();
        $this->clearTrustedDevices($user, Cache::store());

        return back()->with('status', 'User 2FA has been reset. They will be forced to re-register on next login.');
    }

    private function startPendingTwoFactorFlow(Request $request, User $user): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->put('twofactor_pending_user_id', $user->id);
    }

    private function hasTrustedDevice(Request $request, User $user, CacheRepository $cache): bool
    {
        $token = (string) $request->cookie(self::TRUSTED_DEVICE_COOKIE, '');
        if ($token === '') {
            return false;
        }

        $cacheKey = self::TRUSTED_DEVICE_CACHE_PREFIX . $user->id . ':' . hash('sha256', $token);
        return (bool) $cache->get($cacheKey, false);
    }

    private function rememberTrustedDevice(?User $user, CacheRepository $cache): void
    {
        if (! $user) {
            return;
        }

        $token = Str::random(64);
        $cacheKey = self::TRUSTED_DEVICE_CACHE_PREFIX . $user->id . ':' . hash('sha256', $token);
        $cache->put($cacheKey, true, now()->addDays(self::TRUSTED_DEVICE_DAYS));
        Cookie::queue(
            cookie(
                self::TRUSTED_DEVICE_COOKIE,
                $token,
                60 * 24 * self::TRUSTED_DEVICE_DAYS,
                '/',
                null,
                true,
                true,
                false,
                'Lax'
            )
        );
    }

    private function clearTrustedDevices(User $user, CacheRepository $cache): void
    {
        $token = request()->cookie(self::TRUSTED_DEVICE_COOKIE);
        if (is_string($token) && $token !== '') {
            $cache->forget(self::TRUSTED_DEVICE_CACHE_PREFIX . $user->id . ':' . hash('sha256', $token));
        }
        Cookie::queue(Cookie::forget(self::TRUSTED_DEVICE_COOKIE));
    }

    private function userHasTotpEnabled(User $user): bool
    {
        return ! empty($user->two_factor_secret) && $user->two_factor_enabled_at !== null;
    }

    private function verifyTotpCodeForUser(User $user, string $code): bool
    {
        if (! $this->userHasTotpEnabled($user)) {
            return false;
        }

        try {
            $secret = Crypt::decryptString((string) $user->two_factor_secret);
        } catch (\Throwable) {
            return false;
        }

        return $this->verifyTotpCode($secret, $code);
    }

    private function verifyTotpCode(string $secret, string $code, int $window = 1): bool
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timeSlice = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->generateTotpCode($secret, $timeSlice + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    private function generateTotpCode(string $secret, int $timeSlice): string
    {
        $secretKey = $this->base32Decode($secret);
        if ($secretKey === '') {
            return '000000';
        }

        $time = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $segment = substr($hash, $offset, 4);
        $value = unpack('N', $segment)[1] & 0x7FFFFFFF;

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function generateBase32Secret(int $length = 32): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $secret;
    }

    private function base32Decode(string $secret): string
    {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', $secret) ?? '');
        $alphabet = array_flip(str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'));
        $bits = '';
        foreach (str_split($secret) as $char) {
            if (! isset($alphabet[$char])) {
                return '';
            }
            $bits .= str_pad(decbin($alphabet[$char]), 5, '0', STR_PAD_LEFT);
        }

        $result = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $result .= chr(bindec($byte));
            }
        }

        return $result;
    }

    private function buildOtpAuthUrl(string $issuer, string $email, string $secret): string
    {
        $label = rawurlencode($issuer . ':' . $email);
        $issuerEncoded = rawurlencode($issuer);

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuerEncoded}&algorithm=SHA1&digits=6&period=30";
    }
}
