<?php
// [BẢO MẬT 1] Tắt hiển thị lỗi HTML để tránh lộ thông tin
error_reporting(0);
ini_set('display_errors', 0);

// [BẢO MẬT 2] Khởi tạo session an toàn (do file này không gọi db.php)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

$clientID = '782712357762-sa1lrjve0cjng6khlgfd6ftr4ejmol2f.apps.googleusercontent.com';
$redirectUri = 'http://localhost/nhap/api/google_callback.php';

// [BẢO MẬT 3] Tạo mã bảo vệ State ngẫu nhiên để chống giả mạo request (CSRF)
// Mã này sẽ được lưu vào Session và gửi sang Google. Google sẽ trả lại y hệt mã này.
$state = bin2hex(random_bytes(16));
$_SESSION['oauth2state'] = $state;

$authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => $clientID,
    'redirect_uri' => $redirectUri,
    'response_type' => 'code',
    'scope' => 'email profile',
    'prompt' => 'select_account',
    'state' => $state // Gắn chốt bảo vệ vào URL
]);

header('Location: ' . $authUrl);
exit;
?>