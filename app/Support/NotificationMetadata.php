<?php

namespace App\Support;

class NotificationMetadata
{
    public static function enrich(string $type, array $data = []): array
    {
        $severity  = $data['severity'] ?? self::severityForType($type);
        $retryable = self::isRetryable($type, $severity);

        return array_merge([
            'type'                          => $type,
            'severity'                      => $severity,
            'tag'                           => $data['tag'] ?? "ministry-{$type}",
            'play_sound'                    => true,
            'sound_mode'                    => self::soundModeForSeverity($severity),
            'require_interaction'           => $severity === 'critical',
            'renotify'                      => in_array($severity, ['critical', 'high'], true),
            'vibrate'                       => self::vibrationPatternForSeverity($severity),
            'web_urgency'                   => self::webUrgencyForSeverity($severity),
            'android_message_priority'      => self::androidMessagePriorityForSeverity($severity),
            'android_notification_priority' => self::androidNotificationPriorityForSeverity($severity),
            'apns_priority'                 => $severity === 'critical' ? '10' : '5',
            'retryable'                     => $retryable,
            'retry_after_seconds'           => $retryable ? 300 : 0,
            'retry_limit'                   => $retryable ? 3 : 0,
            'retry_count'                   => (int) ($data['retry_count'] ?? 0),
            'last_retry_at'                 => $data['last_retry_at'] ?? null,
        ], $data);
    }

    public static function severityForType(string $type): string
    {
        return match ($type) {
            'critical_case' => 'critical',
            'visit_reminder', 'unvisited_alert' => 'high',
            'birthday', 'new_beneficiary', 'servant_registered' => 'medium',
            'welcome_servant' => 'low',
            default           => 'medium',
        };
    }

    public static function isRetryable(string $type, string $severity): bool
    {
        return $type === 'critical_case' || $severity === 'critical';
    }

    public static function soundModeForSeverity(string $severity): string
    {
        return match ($severity) {
            'critical' => 'alarm',
            'high'     => 'alert',
            default    => 'soft',
        };
    }

    public static function vibrationPatternForSeverity(string $severity): array
    {
        return match ($severity) {
            'critical' => [400, 150, 400, 150, 700],
            'high'     => [250, 120, 250, 120, 250],
            default    => [160, 80, 160],
        };
    }

    public static function webUrgencyForSeverity(string $severity): string
    {
        return match ($severity) {
            'critical', 'high' => 'high',
            'medium' => 'normal',
            default  => 'low',
        };
    }

    public static function androidMessagePriorityForSeverity(string $severity): string
    {
        return in_array($severity, ['critical', 'high'], true) ? 'high' : 'normal';
    }

    public static function androidNotificationPriorityForSeverity(string $severity): string
    {
        return match ($severity) {
            'critical' => 'PRIORITY_MAX',
            'high'     => 'PRIORITY_HIGH',
            default    => 'PRIORITY_DEFAULT',
        };
    }
}
