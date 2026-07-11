<?php
$post = http_build_query([
    'userSecretKey' => 'zyygxm66-w0f9-i2dv-epwi-5ouy2n0pj5ll',
    'categoryCode' => '9cuthhyc',
    'billName' => 'Test Booking',
    'billDescription' => 'Test',
    'billPriceSetting' => 0,
    'billPayorInfo' => 1,
    'billAmount' => 1000,
    'billReturnUrl' => 'http://localhost/unibook%20-%20Copy/user/toyyibpay_redirect.php',
    'billTo' => 'Test User',
    'billEmail' => 'test@example.com',
    'billPhone' => '0123456789'
]);

$ch = curl_init('https://dev.toyyibpay.com/index.php/api/createBill');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP=$httpCode\n";
echo "CURL_ERROR=" . ($curlError ?: 'none') . "\n";
echo "RESPONSE=" . ($response ?: 'empty') . "\n";
