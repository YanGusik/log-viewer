# Log Viewer Custom - Enhanced Exception Debugging

Кастомный форк `opcodesio/log-viewer` с улучшенной отладкой исключений в стиле Laravel Ignition.

## Добавленные возможности

### 🔥 Ignition-Style Exception Debugging

Вместо обычного текстового stacktrace теперь доступна структурированная информация об ошибках:

#### 1. **Code Snippets для главной ошибки**
- Показывает код вокруг строки где произошла ошибка
- Подсветка проблемной строки
- Настраиваемое количество строк до/после ошибки

#### 2. **Детальный Stacktrace с кодом**
- Для каждого фрейма в stacktrace показывается:
  - Имя файла (относительный путь)
  - Номер строки
  - Класс, метод, тип вызова
  - Code snippet вокруг вызова
  - Флаг vendor/app кода

#### 3. **Previous Exceptions**
- Парсинг вложенных исключений
- Полная информация для каждого уровня
- Code snippets для каждого previous exception

#### 4. **Сохранение пользовательского Context**
- Если вы передаете `Log::error('msg', ['exception' => $e, 'user_id' => 123])`
- Exception автоматически извлекается и парсится
- Остальной context (`user_id`) остается в `context` поле

## Структура API Response

```json
{
  "has_exception": true,
  "exception": {
    "class": "App\\Exceptions\\WBRequestException",
    "message": "Request failed",
    "file": "/app/Services/WBApi.php",
    "line": 123,
    "snippet": [
      {"line": 113, "code": "    public function request()", "highlighted": false},
      {"line": 123, "code": "        throw new WBRequestException();", "highlighted": true},
      {"line": 133, "code": "    }", "highlighted": false}
    ],
    "frames": [
      {
        "file": "/app/Controllers/Controller.php",
        "line": 45,
        "class": "App\\Controllers\\Controller",
        "function": "handle",
        "type": "->",
        "is_vendor": false,
        "relative_file": "app/Controllers/Controller.php",
        "snippet": [...]
      }
    ],
    "previous": {
      // Аналогичная структура для вложенного exception
    }
  },
  "context_exceptions": [
    // Exceptions найденные в context данных
  ],
  "context": {
    // Пользовательские данные без Throwable объектов
    "user_id": 123,
    "additional_info": {...}
  },
  "message": "Custom error message",
  "full_text": "... полный текст лога ..."
}
```

## Конфигурация

В `config/log-viewer.php` добавлена секция `exception_debugging`:

```php
'exception_debugging' => [
    // Включить/выключить exception debugging
    'enabled' => env('LOG_VIEWER_EXCEPTION_DEBUGGING', true),

    // Количество строк вокруг ошибки в code snippet
    'snippet_lines_around' => 10,

    // Показывать code snippets для vendor файлов
    'show_vendor_snippets' => false,

    // Парсить previous/nested exceptions
    'parse_previous_exceptions' => true,
],
```

### Environment переменные

```env
# Включить exception debugging
LOG_VIEWER_EXCEPTION_DEBUGGING=true
```

## Примеры использования

### Базовый пример

```php
try {
    // Какой-то код
    throw new \Exception('Something went wrong');
} catch (\Exception $e) {
    Log::error('Custom error message', [
        'exception' => $e,
        'user_id' => 123,
        'context_data' => 'important info',
    ]);
}
```

**Результат в API:**
- `exception` - полная информация об ошибке с code snippets
- `context` - только `user_id` и `context_data`
- `has_exception` - `true`

### Nested Exceptions

```php
try {
    try {
        DB::query('invalid sql');
    } catch (\PDOException $e) {
        throw new \App\Exceptions\DatabaseException('DB Error', 0, $e);
    }
} catch (\Exception $e) {
    Log::error('Application error', ['exception' => $e]);
}
```

**Результат:**
- `exception.class` - `App\Exceptions\DatabaseException`
- `exception.previous.class` - `PDOException`
- Оба exception имеют code snippets

## Отличия от оригинала

| Функция | Оригинал | Custom Fork |
|---------|----------|-------------|
| Stacktrace | Только текст | Структурированный с code snippets |
| Code Snippets | ❌ | ✅ |
| Previous Exceptions | ❌ | ✅ |
| Vendor/App разделение | ❌ | ✅ |
| Context parsing | Базовый | Извлечение Throwable |

## Установка

### Локальный пакет (для разработки)

1. Скопировать пакет в `packages/log-viewer-custom`
2. Настроить `composer.json`:

```json
{
  "repositories": {
    "log-viewer-custom": {
      "type": "path",
      "url": "./packages/log-viewer-custom"
    }
  },
  "require": {
    "yubid/log-viewer-custom": "@dev"
  },
  "minimum-stability": "dev"
}
```

3. Установить: `composer update yubid/log-viewer-custom`

### Из GitHub Fork (production)

```json
{
  "repositories": {
    "log-viewer": {
      "type": "vcs",
      "url": "https://github.com/your-username/log-viewer"
    }
  },
  "require": {
    "opcodesio/log-viewer": "dev-custom-branch"
  }
}
```

## Технические детали

### Новые классы

- `ExceptionParser` - утилита для парсинга exceptions
  - `parseFromText()` - парсинг из текста лога
  - `parseException()` - парсинг Throwable объекта
  - `getCodeSnippet()` - извлечение кода из файлов
  - `parsePreviousException()` - парсинг вложенных exception

### Изменённые файлы

- `src/Logs/LaravelLog.php` - добавлен `extractExceptionData()`
- `src/Http/Resources/LogResource.php` - добавлены поля `exception`, `context_exceptions`, `has_exception`
- `config/log-viewer.php` - добавлена секция `exception_debugging`

## Производительность

- Code snippets читаются лениво (только если файл доступен)
- Vendor файлы по умолчанию без snippets (оптимизация)
- Кеширование на уровне Laravel не затронуто

## Frontend UI

### ExceptionDebugger Vue компонент

Добавлен полнофункциональный Vue компонент для красивого отображения exceptions:

**Возможности:**
- ✅ Отображение exception class, message, file:line
- ✅ Code snippet с подсветкой строки ошибки
- ✅ PHP syntax highlighting для кода (highlight.js)
- ✅ Раскрывающийся stacktrace с фреймами
- ✅ Code snippets для каждого фрейма stacktrace
- ✅ Поддержка previous/nested exceptions
- ✅ Разделение vendor/app кода (vendor фреймы отмечены)
- ✅ Dark mode support

**Использование:**
1. Открой log-viewer в браузере
2. Кликни на лог с ошибкой
3. Если есть exception - появится новый таб **"Exception"**
4. Stacktrace можно раскрыть/свернуть
5. Каждый фрейм можно кликнуть для просмотра code snippet

### Сборка Frontend

```bash
cd packages/log-viewer-custom
npm install
npm run production  # или npm run dev для разработки
```

## Roadmap

- [x] Frontend UI для отображения
- [x] Code snippets в UI
- [x] Раскрывающийся stacktrace
- [x] Syntax highlighting для code snippets (highlight.js + PHP)
- [ ] Фильтрация stacktrace (как в Ignition)
- [ ] Открытие файла в IDE по клику (URL schemes)
- [ ] Копирование кода из snippet

## Автор изменений

Custom fork created for YUboost project.
Based on `opcodesio/log-viewer` v3.10.2
