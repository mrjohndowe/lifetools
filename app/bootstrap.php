<?php
declare(strict_types=1);

require_once __DIR__ . '/lang.php';

define('LT_ROOT', dirname(__DIR__));
ini_set('display_errors', '0');
date_default_timezone_set('UTC');
umask(0077);

foreach (['Config', 'Store', 'Catalog', 'Planner', 'Mirror', 'Maintenance', 'Install'] as $file) {
    require_once __DIR__ . "/$file.php";
}

final class Problem extends RuntimeException {}

function ltJson(mixed $value): string
{
    return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function ltUuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 15) | 64);
    $bytes[8] = chr((ord($bytes[8]) & 63) | 128);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

function ltNow(): string
{
    return gmdate('Y-m-d\TH:i:s\Z');
}

function ltAtomic(string $path, string $content): void
{
    $tmp = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
    try {
        if (file_put_contents($tmp, $content, LOCK_EX) !== strlen($content)) {
            throw new Problem(ltText('could_not_write_the_protected_file'));
        }
        chmod($tmp, 0600);
        if (!rename($tmp, $path)) {
            throw new Problem(ltText('could_not_publish_the_protected_file'));
        }
    } finally {
        if (is_file($tmp)) {
            unlink($tmp);
        }
    }
}

function ltWritable(string $path): bool
{
    if (is_writable($path)) {
        return true;
    }
    if (PHP_OS_FAMILY !== 'Windows') {
        return false;
    }
    // Windows access checks can report false for inherited ACLs. Probe the actual operation.
    $directory = is_dir($path);
    $probe = $directory ? $path . '/.write-' . bin2hex(random_bytes(8)) : $path;
    $handle = @fopen($probe,$directory ? 'x+b' : 'r+b');
    if (!$handle) {
        return false;
    }
    fclose($handle);
    return !$directory || @unlink($probe);
}

function ltLock(string $path, callable $callback, bool $wait = true): mixed
{
    $lock = fopen($path, 'c+b');
    if (!$lock) {
        throw new Problem(ltText('could_not_open_the_installation_lock'));
    }
    try {
        if (!flock($lock, LOCK_EX | ($wait ? 0 : LOCK_NB))) {
            return null;
        }
        return $callback();
    } finally {
        fclose($lock);
    }
}

function ltSeal(string $plain, string $key, string $aad): string
{
    $nonce = random_bytes(12);
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, $aad, 16);
    if ($cipher === false) {
        throw new Problem(ltText('encryption_failed'));
    }
    return base64_encode($nonce . $tag . $cipher);
}

function ltOpen(string $encoded, string $key, string $aad): string
{
    $bytes = base64_decode($encoded, true);
    if ($bytes === false || strlen($bytes) < 28) {
        throw new Problem(ltText('encrypted_data_could_not_be_authenticated'));
    }
    $plain = openssl_decrypt(substr($bytes, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA,
        substr($bytes, 0, 12), substr($bytes, 12, 16), $aad);
    if ($plain === false) {
        throw new Problem(ltText('encrypted_data_could_not_be_authenticated'));
    }
    return $plain;
}

