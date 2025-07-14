<?php

namespace Metallheart\LaravelTelegramLogger;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\LogRecord;
use Monolog\Level;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class TelegramHandler extends AbstractProcessingHandler
{
    private string $botToken;
    private string $chatId;
    private int $timeout;
    private string $fallbackChannel;
    private bool $enabled;
    private string $messageFormat;
    private bool $includeContext;
    private bool $includeExtra;
    private string $dateFormat;

    public function __construct(
        ?string $botToken = null,
        ?string $chatId = null,
        int|string|Level $level = Level::Error,
        bool $bubble = true
    ) {
        $this->botToken = $botToken ?? Config::get('telegram-logger.bot_token');
        $this->chatId = $chatId ?? Config::get('telegram-logger.chat_id');
        $this->timeout = Config::get('telegram-logger.timeout', 5);
        $this->fallbackChannel = Config::get('telegram-logger.fallback_channel', 'single');
        $this->enabled = Config::get('telegram-logger.enabled', true);
        $this->messageFormat = Config::get('telegram-logger.message_format', '<b>[{level}]</b> {message}');
        $this->includeContext = Config::get('telegram-logger.include_context', true);
        $this->includeExtra = Config::get('telegram-logger.include_extra', true);
        $this->dateFormat = Config::get('telegram-logger.date_format', 'Y-m-d H:i:s');

        $levelValue = is_string($level) ? Level::fromName($level) : $level;
        
        parent::__construct($levelValue, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if (!$this->enabled || !$this->botToken || !$this->chatId) {
            $this->fallbackToFile($record);
            return;
        }

        $message = $this->formatMessage($record);
        
        try {
            $response = Http::timeout($this->timeout)->post(
                "https://api.telegram.org/bot{$this->botToken}/sendMessage",
                [
                    'chat_id' => $this->chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ]
            );

            if (!$response->successful()) {
                $this->fallbackToFile($record);
            }
        } catch (\Exception $e) {
            $this->fallbackToFile($record);
        }
    }

    private function formatMessage(LogRecord $record): string
    {
        $level = strtoupper($record->level->name);
        $message = $record->message;
        $context = $this->includeContext && !empty($record->context) 
            ? json_encode($record->context, JSON_UNESCAPED_UNICODE) 
            : '';
        $extra = $this->includeExtra && !empty($record->extra) 
            ? json_encode($record->extra, JSON_UNESCAPED_UNICODE) 
            : '';
        
        $text = str_replace(
            ['{level}', '{message}'],
            [$level, $message],
            $this->messageFormat
        );
        
        if ($context) {
            $text .= "\n<b>Context:</b> {$context}";
        }
        
        if ($extra) {
            $text .= "\n<b>Extra:</b> {$extra}";
        }
        
        $text .= "\n<b>Time:</b> " . $record->datetime->format($this->dateFormat);
        
        // Ограничиваем длину сообщения (Telegram максимум 4096 символов)
        if (strlen($text) > 4096) {
            $text = substr($text, 0, 4093) . '...';
        }
        
        return $text;
    }

    private function fallbackToFile(LogRecord $record): void
    {
        Log::channel($this->fallbackChannel)->log(
            $record->level->name,
            '[TELEGRAM_FALLBACK] ' . $record->message,
            $record->context
        );
    }
} 