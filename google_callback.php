<?php
require_once 'db.php';

$clientID = '782712357762-sa1lrjve0cjng6khlgfd6ftr4ejmol2f.apps.googleusercontent.com';
$clientSecret = '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'; 
$redirectUri = 'http://localhost/nhap/api/google_callback.php';

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

            if ($existingUser) {
                $updateStmt = $pdo->prepare("UPDATE users SET google_id = ?, avatar = ? WHERE id = ?");
                $updateStmt->execute([$googleUser['id'], $googleUser['picture'], $existingUser['id']]);
                
                $_SESSION['user_id'] = $existingUser['id'];
                $_SESSION['username'] = $existingUser['username'];
                $_SESSION['full_name'] = $existingUser['full_name']; // Lưu thêm Tên thật vào Session
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
                $_SESSION['full_name'] = $googleUser['name']; // Lấy Tên thật từ Google
                $_SESSION['role'] = 'user';
            }

            header('Location: ../index.html');
            exit;
            
        } catch (PDOException $e) {
            die("Lỗi Database: " . $e->getMessage());
        }
    }
}

header('Location: ../login.html?error=google_failed');
exit;
?>