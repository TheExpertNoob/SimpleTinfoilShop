<?php

require_once 'encrypt/vendor/autoload.php';

use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\PublicKeyLoader;

// === Configuration ===
$expectedHauth = '--Enter your Domain HAUTH Here--';
$defaultHtml = 'index.html';
$defautDomain = 'https://your.domain.com';

// === Begin Request ===
$hauth = $_SERVER['HTTP_HAUTH'] ?? null;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$uauth = $_SERVER['HTTP_UAUTH'] ?? null;
$theme = $_SERVER['HTTP_THEME'] ?? null;
$uid = $_SERVER['HTTP_UID'] ?? null;

// === If HAUTH is valid, encrypt hardcoded JSON ===
if (
    ($hauth === $expectedHauth) &&
    isset($uauth, $theme, $uid)
) {
    $jsonData = [
        "success" => "Welcome to Tinfoil Shop",
        "directories" => [
            "$defaultDomain/Retro",
			"$defaultDomain/SXRoms"
        ]
    ];
    $json = json_encode($jsonData, JSON_UNESCAPED_SLASHES);

    // === Public RSA key for Tinfoil Encryption ===
    $publicPem = <<<EOD
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAvPdrJigQ0rZAy+jla7hS
jwen8gkF0gjtl+lZGY59KatNd9Kj2gfY7dTMM+5M2tU4Wr3nk8KWr5qKm3hzo/2C
Gbc55im3tlRl6yuFxWQ+c/I2SM5L3xp6eiLUcumMsEo0B7ELmtnHTGCCNAIzTFzV
4XcWGVbkZj83rTFxpLsa1oArTdcz5CG6qgyVe7KbPsft76DAEkV8KaWgnQiG0Dps
INFy4vISmf6L1TgAryJ8l2K4y8QbymyLeMsABdlEI3yRHAm78PSezU57XtQpHW5I
aupup8Es6bcDZQKkRsbOeR9T74tkj+k44QrjZo8xpX9tlJAKEEmwDlyAg0O5CLX3
CQIDAQAB
-----END PUBLIC KEY-----
EOD;

    // === Encrypt ===
    $aesKey = random_bytes(16);
    $rsa = PublicKeyLoader::load($publicPem)->withPadding(RSA::ENCRYPTION_OAEP);
    $sealedKey = $rsa->encrypt($aesKey);

    $aes = new AES('ecb');
    $aes->setKey($aesKey);
    $padLen = 16 - (strlen($json) % 16);
    $padding = str_repeat("\x00", $padLen);
    $encrypted = $aes->encrypt($json . $padding);

    header('Content-Type: application/octet-stream');
    echo "TINFOIL\xF0";
    echo $sealedKey;
    echo pack('P', strlen($json));  // 8-byte LE unencrypted JSON length
    echo $encrypted;
    exit();
}

// === Catchall Fallback ===
if (file_exists($defaultHtml)) {
    readfile($defaultHtml);
} else {
    echo "Error: Page not found.";
}
exit();