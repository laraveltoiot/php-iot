<?php

declare(strict_types=1);

namespace ScienceStories\Mqtt\Util;

use DateTimeImmutable;
use Psr\Log\AbstractLogger;

/**
 * Very small console logger for examples and debugging.
 * Writes lines to STDERR in the format: "[time] LEVEL: message json_context".
 */
final class ConsoleLogger extends AbstractLogger
{
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $ts  = new DateTimeImmutable()->format('H:i:s.u');
        $msg = \is_string($message) ? $message : (string) $message;

        // Normalize level to string
        if (\is_string($level)) {
            $levelStr = $level;
        } elseif (\is_scalar($level)) {
            $levelStr = (string) $level;
        } elseif (\is_object($level) && method_exists($level, '__toString')) {
            $levelStr = (string) $level;
        } else {
            $levelStr = \gettype($level);
        }

        $ctx = '';
        if ($context !== []) {
            // Best-effort JSON
            $ctx = ' '.json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
        fwrite(STDERR, \sprintf('[%s] %s: %s%s', $ts, strtoupper($levelStr), $msg, $ctx).PHP_EOL);
    }
}
