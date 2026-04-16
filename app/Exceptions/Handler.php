<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Do not report noise-level exceptions.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        NotFoundHttpException::class,
        AuthenticationException::class,
    ];

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (! $this->shouldReport($e)) {
                return;
            }

            $this->sendToTelegram($e);
        });
    }

    private function sendToTelegram(Throwable $e): void
    {
        $token = (string) config('services.telegram.bot_token', '');
        $chatId = (string) config('services.telegram.chat_id', '');
        if ($token === '' || $chatId === '') {
            return;
        }

        $dedupeKey = 'err_telegram_' . md5($e->getMessage() . '|' . $e->getFile());
        if (Cache::has($dedupeKey)) {
            return;
        }
        Cache::put($dedupeKey, true, now()->addMinutes(5));

        $request = request();
        $errorUrl = $request ? (string) $request->fullUrl() : 'n/a';
        $referrer = $request ? (string) $request->headers->get('referer', 'n/a') : 'n/a';
        $method = $request ? (string) $request->method() : 'n/a';
        $ip = $request ? (string) $request->ip() : 'n/a';
        $userAgent = $request ? (string) $request->userAgent() : 'n/a';
        $routeName = $request && $request->route() ? (string) ($request->route()->getName() ?? 'n/a') : 'n/a';
        $errorType = class_basename($e);
        $userEmail = $request && $request->user() ? (string) ($request->user()->email ?? 'n/a') : 'guest';
        $traceSnippet = collect($e->getTrace())
            ->take(8)
            ->map(function (array $frame): string {
                $file = (string) ($frame['file'] ?? 'n/a');
                $line = (string) ($frame['line'] ?? 'n/a');
                $function = (string) ($frame['function'] ?? 'unknown');
                return $file . ':' . $line . ' -> ' . $function . '()';
            })
            ->implode("\n");

        $errorSnippet = Str::limit($e->getMessage(), 1200);
        $traceSnippet = Str::limit($traceSnippet, 1400);

        $text = implode("\n", [
            '🚨 <b>Laravel Error Alert</b>',
            '<b>App:</b> ' . $this->escape(config('app.name', 'Laravel')),
            '<b>Env:</b> ' . $this->escape(config('app.env', 'production')),
            '<b>Error Type:</b> ' . $this->escape($errorType),
            '<b>Error Message:</b> ' . $this->escape($errorSnippet),
            '<b>File:</b> ' . $this->escape($e->getFile()),
            '<b>Line:</b> ' . $e->getLine(),
            '<b>Error On Page:</b> ' . $this->escape($errorUrl),
            '<b>Referring Page:</b> ' . $this->escape($referrer),
            '<b>Method:</b> ' . $this->escape($method),
            '<b>Route:</b> ' . $this->escape($routeName),
            '<b>IP:</b> ' . $this->escape($ip),
            '<b>User:</b> ' . $this->escape($userEmail),
            '<b>User Agent:</b> ' . $this->escape(Str::limit($userAgent, 300)),
            '<b>Trace Snippet:</b>',
            '<code>' . $this->escape($traceSnippet) . '</code>',
        ]);

        try {
            Http::timeout(3)
                ->asForm()
                ->post('https://api.telegram.org/bot' . $token . '/sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true,
                ]);
        } catch (\Throwable) {
            // Never interrupt application flow if Telegram delivery fails.
        }
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
