<?php

namespace Tests\Feature;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class ExceptionTelegramAlertTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_token' => 'test-token',
            'services.telegram.chat_id' => '123456',
        ]);

        Cache::flush();
    }

    public function test_it_skips_noise_exceptions_from_telegram_alerts(): void
    {
        Http::fake();

        app(ExceptionHandler::class)->report(new NotFoundHttpException('not found'));
        app(ExceptionHandler::class)->report(new AuthenticationException('unauthenticated'));

        Http::assertNothingSent();
    }

    public function test_it_deduplicates_same_exception_for_five_minutes(): void
    {
        Http::fake();

        $handler = app(ExceptionHandler::class);
        $exception = new \RuntimeException('telegram dedupe check');

        $handler->report($exception);
        $handler->report($exception);

        Http::assertSentCount(1);
    }
}

