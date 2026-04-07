<?php

namespace App\Http\Middleware;

use App\Models\CmsActivityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class LogCmsActivity
{
    /**
     * Record an audit entry for authenticated CMS requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $route = $request->route();
        $routeName = $route?->getName();

        try {
            $response = $next($request);
            $statusCode = $response->getStatusCode();
        } catch (\Throwable $e) {
            $statusCode = ($e instanceof HttpExceptionInterface) ? $e->getStatusCode() : 500;

            if ($user) {
                CmsActivityLog::query()->create([
                    'user_id' => $user->id,
                    'user_email' => $user->email ?? null,
                    'action_key' => $routeName ?: $request->path(),
                    'method' => $request->method(),
                    'route_name' => $routeName,
                    'url' => $request->fullUrl(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'status_code' => $statusCode,
                    'context' => [
                        'params' => $route?->parameters() ?? [],
                        'exception' => get_class($e),
                    ],
                ]);
            }

            throw $e;
        }

        if (!$user) {
            return $response;
        }

        $context = null;
        // Avoid logging large/sensitive payloads; store only lightweight request info.
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $input = $request->except([
                '_token',
                '_method',
                'password',
                'password_confirmation',
                'current_password',
                'file',
                'files',
                'profile_image',
                'signed_contract',
                'pan_card',
                'id_card',
            ]);

            $context = [
                'query' => $request->query(),
                'params' => $route?->parameters() ?? [],
                'input' => $this->stringifyScalars($input),
            ];
        } elseif ($request->method() === 'GET') {
            $context = [
                'query' => $request->query(),
                'params' => $route?->parameters() ?? [],
            ];
        }

        CmsActivityLog::query()->create([
            'user_id' => $user->id,
            'user_email' => $user->email ?? null,
            'action_key' => $routeName ?: $request->path(),
            'method' => $request->method(),
            'route_name' => $routeName,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status_code' => $statusCode,
            'context' => $context,
        ]);

        return $response;
    }

    /**
     * Ensure only scalar/array data ends up in JSON context.
     */
    private function stringifyScalars(array $input): array
    {
        $out = [];
        foreach ($input as $key => $value) {
            if (is_scalar($value) || is_null($value)) {
                $out[$key] = (string) $value;
                continue;
            }

            if (is_array($value)) {
                $out[$key] = $this->stringifyScalars($value);
                continue;
            }

            // For objects/files/resources: drop.
            $out[$key] = '[unlogged]';
        }

        return $out;
    }
}

