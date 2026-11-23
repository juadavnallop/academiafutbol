<?php
declare(strict_types=1);
namespace App\Services;
final class HealthVault {
    private static function key(): string {
        $path=ROOT.'/var/keys/health.key';
        if (!is_file($path) || strlen($key=file_get_contents($path)) !== 32) throw new \RuntimeException('Health key unavailable');
        return $key;
    }
    public static function encrypt(array $data, int $player): string {
        $iv=random_bytes(12); $tag='';
        $cipher=openssl_encrypt(json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,$iv,$tag,'player:'.$player);
        if ($cipher === false) throw new \RuntimeException('Encryption failed');
        return base64_encode($iv.$tag.$cipher);
    }
    public static function decrypt(string $data, int $player): array {
        $raw=base64_decode($data,true);
        if ($raw === false || strlen($raw)<29) throw new \RuntimeException('Invalid ciphertext');
        $plain=openssl_decrypt(substr($raw,28),'aes-256-gcm',self::key(),OPENSSL_RAW_DATA,substr($raw,0,12),substr($raw,12,16),'player:'.$player);
        if ($plain === false) throw new \RuntimeException('Decryption failed');
        return json_decode($plain,true,512,JSON_THROW_ON_ERROR);
    }
}
