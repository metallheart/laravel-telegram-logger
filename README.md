# Laravel Telegram Logger

Пакет для отправки логов Laravel в Telegram.

## Установка

```bash
composer require metallheart/laravel-telegram-logger
```

Пакет автоматически регистрируется через Laravel Package Discovery.

## Настройка

1. Опубликуйте конфигурацию:

```bash
php artisan vendor:publish --tag=telegram-logger-config
```

2. Добавьте переменные в `.env`:

```env
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id
TELEGRAM_LOG_LEVEL=error
TELEGRAM_LOG_ENABLED=true
```

3. Добавьте канал в `config/logging.php`:

```php
'channels' => [
    // ... другие каналы
  
    'telegram' => [
        'driver' => 'telegram',
    ],
  
    // Или добавьте в stack
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'telegram'],
    ],
],
```

## Использование

### Основное использование

```php
use Illuminate\Support\Facades\Log;

// Отправка логов через канал telegram
Log::channel('telegram')->error('Произошла ошибка!');
Log::channel('telegram')->info('Информационное сообщение');

// Или используя stack
Log::error('Ошибка будет отправлена в файл и Telegram');
```

### С контекстом

```php
Log::channel('telegram')->error('Ошибка в оплате', [
    'user_id' => 123,
    'amount' => 1000,
    'payment_method' => 'card'
]);
```


## Конфигурация

Доступные опции в `config/telegram-logger.php`:

- `bot_token` - Токен бота Telegram
- `chat_id` - ID чата для отправки сообщений
- `log_level` - Минимальный уровень логов (debug, info, notice, warning, error, critical, alert, emergency)
- `timeout` - Таймаут HTTP запроса (секунды)
- `fallback_channel` - Резервный канал при недоступности Telegram
- `enabled` - Включить/отключить логирование
- `message_format` - Формат сообщения
- `include_context` - Включать контекст в сообщения
- `include_extra` - Включать дополнительные данные
- `date_format` - Формат даты

## Получение Bot Token и Chat ID

### Bot Token

1. Найдите @BotFather в Telegram
2. Отправьте `/newbot`
3. Следуйте инструкциям
4. Скопируйте токен

### Chat ID

1. Добавьте бота в чат или группу
2. Отправьте сообщение
3. Откройте `https://api.telegram.org/bot<TOKEN>/getUpdates`
4. Найдите `chat.id` в ответе

## Лицензия

MIT
