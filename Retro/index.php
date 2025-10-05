<?php

require_once '../encrypt/vendor/autoload.php';

use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\AES;
use phpseclib3\Crypt\PublicKeyLoader;

// === Configuration ===
$expectedHauth = '--Enter your Domain HAUTH Here--';
$defaultHtml = "../index.html";
$baseUrl = "https://your.domain.com/Retro/";
$allowedExtensions = ['gba', 'gbc', 'gb', 'nds', 'nes', 'nez', 'fds', 'fam', 'sfc', 'srm', 'swc', 'smc', 'fig', '32x', 'sms', 'smd', 'v64', 'z64', 'n64', 'pbp', 'psp', 'cso', 'prx', 'cdi', 'chd', 'gdi'];

// === Validate HAUTH header ===
$hauth = $_SERVER['HTTP_HAUTH'] ?? null;
if ($hauth !== $expectedHauth) {
    header("Location: $defaultHtml");
    exit();
}

// === Public Key for Tinfoil Encryption ===
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

$files = [];

// === Recursive file scanner ===
function scanDirectory($dir, $baseUrl, $allowedExtensions, &$files) {
    $items = scandir($dir);
    foreach ($items as $entry) {
        if ($entry === '.' || $entry === '..') continue;

        $path = $dir . '/' . $entry;
        $relativeUrl = str_replace(__DIR__ . '/', '', $path);
        $relativeUrl = ltrim(str_replace('\\', '/', $relativeUrl), '/');

        if (is_dir($path)) {
            scanDirectory($path, $baseUrl, $allowedExtensions, $files);
        } elseif (is_file($path)) {
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExtensions)) {
                $files[] = [
                    'url' => $baseUrl . implode('/', array_map('rawurlencode', explode('/', $relativeUrl))),
                    'size' => filesize($path)
                ];
            }
        }
    }
}

// === Start scanning ===
scanDirectory(__DIR__, $baseUrl, $allowedExtensions, $files);

// === Build JSON data ===
$jsonData = [
    'files' => $files
];
$json = json_encode($jsonData, JSON_UNESCAPED_SLASHES);

// === Generate AES key ===
$aesKey = random_bytes(16);

// === Encrypt AES key with RSA ===
$rsa = PublicKeyLoader::load($publicPem)->withPadding(RSA::ENCRYPTION_OAEP);
$sealedKey = $rsa->encrypt($aesKey);

// === Encrypt JSON with AES-128-ECB (PKCS7 padding) ===
$aes = new AES('ecb');
$aes->setKey($aesKey);
$padLen = 16 - (strlen($json) % 16);
$padding = str_repeat("\x00", $padLen);
$encrypted = $aes->encrypt($json . $padding);

// === Output in Tinfoil format ===
header('Content-Type: application/octet-stream');
echo "TINFOIL\xF0";                         // Magic header + flags
echo $sealedKey;                           // Encrypted AES key (256 bytes)
echo pack('P', strlen($json));             // JSON length (8-byte LE)
echo $encrypted;                           // AES-encrypted JSON
#echo $json;