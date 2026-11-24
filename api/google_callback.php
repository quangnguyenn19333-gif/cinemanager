<?php
// [BẢO MẬT 1] Tắt hiển thị lỗi PHP
error_reporting(0);
ini_set('display_errors', 0);

require_once 'db.php';

// Đảm bảo session đã chạy để lấy biến state
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$clientID = '782712357762-sa1lrjve0cjng6khlgfd6ftr4ejmol2f.apps.googleusercontent.com';
$clientSecret = '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'; 
$redirectUri = 'http://localhost/nhap/api/google_callback.php';

// [BẢO MẬT 2] Chốt chặn CSRF: So sánh mã state gửi về từ Google với mã state đã lưu trong Session
if (empty($_GET['state']) || empty($_SESSION['oauth2state']) || $_GET['state'] !== $_SESSION['oauth2state']) {
    unset($_SESSION['oauth2state']); // Xóa state cũ
    header('Location: ../login.html?error=invalid_state');
    exit;
}
// Xác thực xong thì xóa chìa khóa đi để không bị dùng lại (Replay Attack)
unset($_SESSION['oauth2state']);

if (!isset($_GET['code'])) {
    header('Location: ../login.html?error=no_code');
    exit;
}

$tokenUrl = 'https://oauth2.googleapis.com/token';
$postData = [
    'client_id' => $clientID,
    'client_secret' => $clientSecret,
    'redirect_uri' => $redirectUri,
    'grant_type' => 'authorization_code',
    'code' => $_GET['code']
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// [LƯU Ý]: Ở môi trường Local XAMPP thì để false, nhưng khi public lên hosting thực tế, 
// bạn NÊN đổi CURLOPT_SSL_VERIFYPEER thành true để chống tấn công Man-in-the-Middle.
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);

if (isset($tokenData['access_token'])) {
    $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $userInfoUrl);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
    $userInfoResponse = curl_exec($ch2);
    curl_close($ch2);

    $googleUser = json_decode($userInfoResponse, true);

    if (!empty($googleUser['id'])) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE google_id = ? OR email = ?");
            $stmt->execute([$googleUser['id'], $googleUser['email']]);
            $existingUser = $stmt->fetch();

            // [BẢO MẬT 3] Reset Session ID ngay khi đăng nhập thành công (Chống cướp phiên - Session Fixation)
            session_regenerate_id(true);

            if ($existingUser) {
                $updateStmt = $pdo->prepare("UPDATE users SET google_id = ?, avatar = ? WHERE id = ?");
                $updateStmt->execute([$googleUser['id'], $googleUser['picture'], $existingUser['id']]);
                
                $_SESSION['user_id'] = $existingUser['id'];
                $_SESSION['username'] = $existingUser['username'];
                $_SESSION['full_name'] = $existingUser['full_name']; 
                $_SESSION['role'] = $existingUser['role'];
            } else {
                $baseUsername = explode('@', $googleUser['email'])[0];
                $username = $baseUsername . '_' . rand(1000, 9999);

                $insertStmt = $pdo->prepare("INSERT INTO users (username, full_name, email, google_id, avatar, role) VALUES (?, ?, ?, ?, ?, 'user')");
                $insertStmt->execute([
                    $username, 
                    $googleUser['name'], 
                    $googleUser['email'], 
                    $googleUser['id'], 
                    $googleUser['picture']
                ]);

                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['full_name'] = $googleUser['name']; 
                $_SESSION['role'] = 'user';
            }

            header('Location: ../index.html');
            exit;
            
        } catch (PDOException $e) {
            // [BẢO MẬT 4] Che lỗi SQL thực tế, chỉ điều hướng về trang login kèm thông số lỗi chung
            header('Location: ../login.html?error=system_error');
            exit;
        }
    }
}

header('Location: ../login.html?error=google_failed');
exit;
?>