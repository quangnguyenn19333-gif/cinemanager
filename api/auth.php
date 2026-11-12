<?php
header('Content-Type: application/json');
require_once 'db.php';

// Khởi tạo session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($action === 'login') {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name']; 
            $_SESSION['role'] = $user['role']; 
            echo json_encode([
                'message' => 'Đăng nhập thành công', 
                'username' => $user['full_name'] ?: $user['username'],
                'role' => $user['role']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Tài khoản hoặc mật khẩu không đúng']);
        }
    } 
    
    elseif ($action === 'register') {
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $full_name = $data['full_name'] ?? '';
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');

        if (empty($username) || empty($password) || empty($email) || empty($phone)) {
            http_response_code(400);
            echo json_encode(['error' => 'Vui lòng cung cấp đầy đủ thông tin: Email và Số điện thoại là bắt buộc!']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Tên đăng nhập hoặc Email này đã tồn tại trên hệ thống']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, 'user')");
        $stmt->execute([$username, $password, $full_name, $email, $phone]);
        echo json_encode(['message' => 'Đăng ký thành công']);
    }

    elseif ($action === 'change_password') {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Vui lòng đăng nhập']);
            exit;
        }

        $old_pass = $data['old_password'] ?? '';
        $new_pass = $data['new_password'] ?? '';

        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user && $old_pass === $user['password']) {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$new_pass, $_SESSION['user_id']]);
            echo json_encode(['message' => 'Đổi mật khẩu thành công']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Mật khẩu cũ không chính xác']);
        }
    }
    
    elseif ($action === 'forgot_password') {
        $username = $data['username'] ?? '';
        $full_name = $data['full_name'] ?? '';
        $new_password = $data['new_password'] ?? '';

        $stmt = $pdo->prepare("SELECT id, google_id FROM users WHERE username = ? AND full_name = ?");
        $stmt->execute([$username, $full_name]);
        $user = $stmt->fetch();

        if ($user) {
            if (!empty($user['google_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Tài khoản đăng nhập bằng Google không cần đổi mật khẩu tại đây!']);
                exit;
            }

            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $updateStmt->execute([$new_password, $user['id']]);
            
            echo json_encode(['message' => 'Khôi phục mật khẩu thành công!']);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Tên đăng nhập hoặc Họ và tên không khớp!']);
        }
    }
} 

elseif ($method === 'GET') {
    if ($action === 'check') {
        if (isset($_SESSION['user_id'])) {
            echo json_encode([
                'isLoggedIn' => true, 
                'username' => $_SESSION['full_name'] ?? $_SESSION['username'],
                'role' => $_SESSION['role']
            ]);
        } else {
            echo json_encode(['isLoggedIn' => false]);
        }
    } 
    
    elseif ($action === 'logout') {
        session_destroy();
        echo json_encode(['message' => 'Đã đăng xuất']);
    }
}
?>