<?php
$url = 'http://localhost/unibook%20-%20Copy/user/toyyibpay_redirect.php?booking_id=UB1013&bank=';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP_CODE=" . $info['http_code'] . "\n";
echo "REDIRECT_URL=" . ($info['redirect_url'] ?? '') . "\n";
echo "HEADER_SIZE=" . $info['header_size'] . "\n";
echo "RESPONSE_HEADERS:\n" . substr($response, 0, $info['header_size']) . "\n";
