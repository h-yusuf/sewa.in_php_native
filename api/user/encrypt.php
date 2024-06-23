<?php
ini_set('display_errors', 0);
function encrypt($data, $key, $iv) {
    $cipher = "aes-256-cbc";
    $options = 0;
    $encryptedData = openssl_encrypt($data, $cipher, $key, $options, $iv);
    return base64_encode($encryptedData);
}
function decrypt($encryptedData, $key, $iv) {
    $cipher = "aes-256-cbc";
    $options = 0;
    $decryptedData = openssl_decrypt(base64_decode($encryptedData), $cipher, $key, $options, $iv);
    return $decryptedData;
}
$key = "SecretKey1234567890";
$iv = "RandomIV987654321";