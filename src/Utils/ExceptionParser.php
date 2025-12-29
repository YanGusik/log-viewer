<?php

namespace Opcodes\LogViewer\Utils;

use Throwable;

class ExceptionParser
{
    /**
     * Парсит exception из текста лога
     *
     * @param string $text
     * @return array|null
     */
    public static function parseFromText(string $text): ?array
    {
        // Ищем первую строку с exception классом
        // Поддерживаем разные форматы:
        // 1. [object] (ExceptionClass(code: 0): message at /path/to/file.php:123)
        // 2. ExceptionClass: message in /path/to/file.php:123
        // 3. ExceptionClass: message
        $patterns = [
            '/\[object\]\s*\(([a-zA-Z0-9\\\\]+)\(code:\s*\d+\):\s*(.+?)\s+at\s+(.+?):(\d+)\)/m',
            '/^([a-zA-Z0-9\\\\]+):\s*(.+?)\s+in\s+(.+?):(\d+)$/m',
            '/^([a-zA-Z0-9\\\\]+Exception):\s*(.+?)$/m',
        ];

        $exceptionClass = null;
        $message = '';
        $file = '';
        $line = 0;

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $exceptionClass = $matches[1] ?? null;
                $message = $matches[2] ?? '';
                $file = $matches[3] ?? '';
                $line = (int)($matches[4] ?? 0);
                break;
            }
        }

        if (!$exceptionClass) {
            return null;
        }

        // Если file не найден в первой строке, ищем в stacktrace
        if (empty($file)) {
            // Ищем строку с "Thrown in /path/to/file.php:123" или "at /path:123"
            if (preg_match('/(?:thrown|Thrown|at)\s+(.+?):(\d+)$/m', $text, $thrownMatch)) {
                $file = $thrownMatch[1] ?? '';
                $line = (int)($thrownMatch[2] ?? 0);
            }
        }

        // Парсим stacktrace
        $frames = static::parseStackTraceFromText($text);

        // Парсим previous exception если есть
        $previous = static::parsePreviousException($text);

        $result = [
            'class' => $exceptionClass,
            'message' => trim($message),
            'file' => $file,
            'line' => $line,
            'snippet' => $file && $line ? static::getCodeSnippet($file, $line) : null,
            'frames' => $frames,
        ];

        if ($previous) {
            $result['previous'] = $previous;
        }

        return $result;
    }

    /**
     * Парсит previous exception из текста
     *
     * @param string $text
     * @return array|null
     */
    public static function parsePreviousException(string $text): ?array
    {
        // Ищем секцию [previous exception]
        if (!preg_match('/\[previous exception\]\s*\[object\]\s*\(([^)]+)\)/is', $text, $prevMatch)) {
            return null;
        }

        $previousClass = $prevMatch[1] ?? null;

        // Ищем текст после [previous exception] до конца
        $parts = preg_split('/\[previous exception\]/i', $text, 2);
        if (count($parts) < 2) {
            return null;
        }

        $previousText = $parts[1];

        // Парсим данные previous exception
        $prevData = [
            'class' => $previousClass,
        ];

        // Ищем сообщение
        if (preg_match('/:\s*(.+?)\s+at\s+/s', $previousText, $msgMatch)) {
            $prevData['message'] = trim($msgMatch[1]);
        }

        // Ищем файл и строку
        if (preg_match('/at\s+(.+?):(\d+)/m', $previousText, $fileMatch)) {
            $prevData['file'] = $fileMatch[1];
            $prevData['line'] = (int)$fileMatch[2];
            $prevData['snippet'] = static::getCodeSnippet($prevData['file'], $prevData['line']);
        }

        // Парсим stacktrace для previous
        $prevData['frames'] = static::parseStackTraceFromText($previousText);

        return $prevData;
    }

    /**
     * Парсит stacktrace из текста
     *
     * @param string $text
     * @return array
     */
    public static function parseStackTraceFromText(string $text): array
    {
        $frames = [];

        // Ищем строки вида: #0 /path/to/file.php(123): Class->method()
        $pattern = '/#\d+\s+(.+?)\((\d+)\):\s*(.+?)$/m';

        if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $file = $match[1] ?? null;
                $line = (int)($match[2] ?? 0);
                $call = $match[3] ?? '';

                // Парсим вызов метода/функции
                preg_match('/^(?:(.+?)(->|::))?(.+?)\(/', $call, $callParts);

                $isVendor = static::isVendorFile($file);
                $showVendorSnippets = config('log-viewer.exception_debugging.show_vendor_snippets', false);
                $shouldShowSnippet = $file && $line && (!$isVendor || $showVendorSnippets);

                $frames[] = [
                    'file' => $file,
                    'line' => $line,
                    'class' => $callParts[1] ?? null,
                    'type' => $callParts[2] ?? null,
                    'function' => $callParts[3] ?? null,
                    'is_vendor' => $isVendor,
                    'relative_file' => static::getRelativePath($file),
                    'snippet' => $shouldShowSnippet ? static::getCodeSnippet($file, $line) : null,
                ];
            }
        }

        return $frames;
    }

    /**
     * Определяет является ли файл из vendor директории
     *
     * @param string|null $file
     * @return bool
     */
    public static function isVendorFile(?string $file): bool
    {
        if (!$file) {
            return false;
        }

        return str_contains($file, '/vendor/') || str_contains($file, '\\vendor\\');
    }

    /**
     * Получает относительный путь к файлу от базового пути проекта
     *
     * @param string|null $file
     * @return string|null
     */
    public static function getRelativePath(?string $file): ?string
    {
        if (!$file) {
            return null;
        }

        $basePath = base_path();

        if (str_starts_with($file, $basePath)) {
            return substr($file, strlen($basePath) + 1);
        }

        return $file;
    }

    /**
     * Извлекает все Throwable объекты из context данных
     *
     * @param mixed $context
     * @return array
     */
    public static function extractExceptions($context): array
    {
        if (empty($context)) {
            return [];
        }

        $exceptions = [];

        // Если context это массив
        if (is_array($context)) {
            foreach ($context as $key => $value) {
                if ($value instanceof Throwable) {
                    $exceptions[] = static::parseException($value);
                } elseif (is_string($value) && static::isExceptionString($value)) {
                    // Парсим exception из строки
                    $parsed = static::parseFromText($value);
                    if ($parsed) {
                        $exceptions[] = $parsed;
                    }
                } elseif (is_array($value)) {
                    // Рекурсивно ищем в подмассивах
                    $exceptions = array_merge($exceptions, static::extractExceptions($value));
                }
            }
        }

        return $exceptions;
    }

    /**
     * Проверяет является ли строка exception текстом
     *
     * @param string $text
     * @return bool
     */
    public static function isExceptionString(string $text): bool
    {
        // Проверяем на наличие паттернов exception
        return (
            str_contains($text, '[object] (') &&
            str_contains($text, '[stacktrace]')
        ) || (
            preg_match('/^[a-zA-Z0-9\\\\]+Exception:/m', $text) > 0
        );
    }

    /**
     * Парсит Throwable в структурированный массив
     *
     * @param Throwable $exception
     * @return array
     */
    public static function parseException(Throwable $exception): array
    {
        return [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'snippet' => static::getCodeSnippet($exception->getFile(), $exception->getLine()),
            'frames' => static::parseStackTrace($exception->getTrace()),
        ];
    }

    /**
     * Парсит stacktrace в массив фреймов с code snippets
     *
     * @param array $trace
     * @return array
     */
    public static function parseStackTrace(array $trace): array
    {
        $frames = [];
        $showVendorSnippets = config('log-viewer.exception_debugging.show_vendor_snippets', false);

        foreach ($trace as $frame) {
            $file = $frame['file'] ?? null;
            $line = $frame['line'] ?? null;
            $isVendor = static::isVendorFile($file);
            $shouldShowSnippet = $file && $line && (!$isVendor || $showVendorSnippets);

            $frames[] = [
                'file' => $file,
                'line' => $line,
                'class' => $frame['class'] ?? null,
                'function' => $frame['function'] ?? null,
                'type' => $frame['type'] ?? null,
                'is_vendor' => $isVendor,
                'relative_file' => static::getRelativePath($file),
                'snippet' => $shouldShowSnippet ? static::getCodeSnippet($file, $line) : null,
            ];
        }

        return $frames;
    }

    /**
     * Извлекает код вокруг указанной строки из файла
     *
     * @param string $file
     * @param int $line
     * @param int|null $linesAround
     * @return array|null
     */
    public static function getCodeSnippet(string $file, int $line, ?int $linesAround = null): ?array
    {
        if (!file_exists($file) || !is_readable($file)) {
            return null;
        }

        // Используем конфиг если linesAround не указан
        if ($linesAround === null) {
            $linesAround = config('log-viewer.exception_debugging.snippet_lines_around', 10);
        }

        $lines = file($file);
        if ($lines === false) {
            return null;
        }

        $start = max(0, $line - $linesAround - 1);
        $end = min(count($lines), $line + $linesAround);

        $snippet = [];
        for ($i = $start; $i < $end; $i++) {
            $lineNumber = $i + 1;
            $snippet[] = [
                'line' => $lineNumber,
                'code' => rtrim($lines[$i]),
                'highlighted' => $lineNumber === $line,
            ];
        }

        return $snippet;
    }

    /**
     * Удаляет Throwable объекты из context для чистого вывода
     *
     * @param mixed $context
     * @return mixed
     */
    public static function removeExceptionsFromContext($context)
    {
        if (!is_array($context)) {
            return $context;
        }

        $cleaned = [];

        foreach ($context as $key => $value) {
            if ($value instanceof Throwable) {
                // Пропускаем exception объекты
                continue;
            } elseif (is_string($value) && static::isExceptionString($value)) {
                // Пропускаем exception строки
                continue;
            } elseif (is_array($value)) {
                // Рекурсивно чистим подмассивы
                $cleaned[$key] = static::removeExceptionsFromContext($value);
            } else {
                $cleaned[$key] = $value;
            }
        }

        return $cleaned;
    }
}
