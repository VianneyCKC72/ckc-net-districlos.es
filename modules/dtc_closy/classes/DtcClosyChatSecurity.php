<?php
/**
 * Jeton front + limitation de débit pour le contrôleur chat AJAX.
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class DtcClosyChatSecurity
{
    private const RATE_LIMIT_MAX = 10;
    private const RATE_LIMIT_WINDOW_SEC = 60;

    public static function ensureToken(): string
    {
        $token = trim((string) DtcClosyConfig::get('CHAT_TOKEN', ''));
        if ($token !== '') {
            return $token;
        }

        $token = Tools::passwdGen(40);
        DtcClosyConfig::update('CHAT_TOKEN', $token);

        return $token;
    }

    public static function validateToken(?string $submitted): bool
    {
        $expected = self::ensureToken();
        if ($expected === '' || $submitted === null || $submitted === '') {
            return false;
        }

        return hash_equals($expected, (string) $submitted);
    }

    public static function checkRateLimit(): bool
    {
        $ip = self::resolveClientIp();
        $cacheKey = 'dtc_closy_rl_' . md5($ip);
        $now = time();
        $bucket = ['count' => 0, 'start' => $now];

        if (class_exists('Cache') && Cache::isStored($cacheKey)) {
            $stored = Cache::retrieve($cacheKey);
            if (is_array($stored)) {
                $bucket = $stored;
            }
        }

        if ($now - (int) $bucket['start'] >= self::RATE_LIMIT_WINDOW_SEC) {
            $bucket = ['count' => 0, 'start' => $now];
        }

        ++$bucket['count'];

        if (class_exists('Cache')) {
            Cache::store($cacheKey, $bucket);
        }

        return $bucket['count'] <= self::RATE_LIMIT_MAX;
    }

    private static function resolveClientIp(): string
    {
        $candidates = [];
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', (string) $_SERVER['HTTP_X_FORWARDED_FOR']);
            $candidates[] = trim($parts[0]);
        }
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $candidates[] = (string) $_SERVER['REMOTE_ADDR'];
        }

        foreach ($candidates as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        return '0.0.0.0';
    }
}
