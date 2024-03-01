<?php

declare(strict_types=1);

namespace Xima\XimaApiClient\Helper;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Log\Channel;
use TYPO3\CMS\Core\Log\LogDataTrait;

class LogHelper
{
    use LogDataTrait;
    protected array $logs;

    /**
     * Log level priority array
     */
    final public const LOG_LEVELS = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    ];

    public function __construct(#[Channel('xima_api_client')]protected LoggerInterface $logger)
    {
    }

    public function logEmergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function logAlert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function logCritical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function logError(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function logWarning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function logNotice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function logInfo(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function logDebug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log(string $level, string|\Stringable $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        $this->logger->log($level, $message, $context);
    }

    public function getLastLogs(bool $format = false, string $minLogLevel = LogLevel::DEBUG): array
    {
        if (!$format) {
            return $this->logs;
        }

        // remove log messages with insufficient log level
        $logs = $this->logs;

        if ($minLogLevel !== LogLevel::DEBUG) {
            $result = [];
            $logIndex = array_search($minLogLevel, static::LOG_LEVELS);

            foreach ($this->logs as $log) {
                if (array_search($log['level'], static::LOG_LEVELS) > $logIndex) {
                    continue;
                }

                $result[] = $log;
            }

            $logs = $result;
        }

        return array_map(fn (array $logEntry) => $this->formatLogDetails(strtoupper((string)$logEntry['level']) . ': ' . $logEntry['message'], $logEntry['context']), $logs);
    }

    public function clearLogs(): void
    {
        $this->logs = [];
    }
}
