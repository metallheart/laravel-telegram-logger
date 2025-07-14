<?php

namespace Metallheart\LaravelTelegramLogger;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;

class TelegramLoggerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/telegram-logger.php',
            'telegram-logger'
        );

        // Регистрируем TelegramHandler в контейнере
        $this->app->singleton(TelegramHandler::class, function ($app) {
            return new TelegramHandler(
                config('telegram-logger.bot_token'),
                config('telegram-logger.chat_id'),
                config('telegram-logger.log_level', 'error')
            );
        });

        // Регистрируем кастомный log channel
        $this->app->extend('log', function ($log, $app) {
            $log->extend('telegram', function ($app, $config) {
                $handler = $app->make(TelegramHandler::class);
                return new Logger('telegram', [$handler]);
            });

            return $log;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Публикуем конфигурацию
        $this->publishes([
            __DIR__ . '/config/telegram-logger.php' => config_path('telegram-logger.php'),
        ], 'telegram-logger-config');
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            TelegramHandler::class,
        ];
    }
} 