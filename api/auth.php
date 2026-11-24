<?php
// [BẢO MẬT 1] Tắt hiển thị lỗi HTML/Warning để không bao giờ phá vỡ chuỗi JSON
error_reporting(0);
ini_set('display_errors', 0);

// [BẢO MẬT 2] Cấu hình cookie session trước khi db.php khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
}

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            $data = [];
        }

        if ($action === 'login') {
            $username = trim((string) ($data['username'] ?? ''));
            $password = (string) ($data['password'] ?? '');

            if ($username === '' || $password === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            $loginSuccess = false;

            if ($user) {
                $storedPassword = (string) ($user['password'] ?? '');
                // [BẢO MẬT 3.1] Kiểm tra mật khẩu bằng hàm băm/giải mã chuẩn của PHP
                if (password_verify($password, $storedPassword)) {
                    $loginSuccess = true;
                }
                // [TỰ ĐỘNG NÂNG CẤP] Nếu DB đang lưu chữ thường (chưa hash), tự động nâng cấp sang password_hash
                elseif ($storedPassword !== '' && hash_equals($storedPassword, $password)) {
                    $loginSuccess = true;
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $updateStmt->execute([$newHash, $user['id']]);
                }
            }

            if ($loginSuccess) {
                // [BẢO MẬT 4] Tạo Session ID mới ngay sau khi đăng nhập thành công (Chống Session Fixation)
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                echo json_encode([
                    'success' => true,
                    'message' => 'Đăng nhập thành công',
                    'username' => $user['full_name'] ?: $user['username'],
                    'role' => $user['role']
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Tài khoản hoặc mật khẩu không đúng']);
            }
            exit;
        }

        elseif ($action === 'register') {
            $username = trim((string) ($data['username'] ?? ''));
            $password = (string) ($data['password'] ?? '');
            $full_name = trim(strip_tags((string) ($data['full_name'] ?? '')));
            $email = trim((string) ($data['email'] ?? ''));
            $phone = trim(strip_tags((string) ($data['phone'] ?? '')));

            if ($username === '' || $password === '' || $email === '' || $phone === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Vui lòng cung cấp đầy đủ thông tin: Email và Số điện thoại là bắt buộc!']);
                exit;
            }

            // KIỂM TRA SỐ ĐIỆN THOẠI ĐÚNG 10 SỐ VÀ BẮT ĐẦU BẰNG 0
            if (!preg_match('/^0[0-9]{9}$/', $phone)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Số điện thoại không hợp lệ. Vui lòng nhập đúng 10 số và bắt đầu bằng số 0.']);
                exit;
            }

            // KIỂM TRA ĐỘ DÀI MẬT KHẨU 8-16 KÝ TỰ
            if (strlen($password) < 8 || strlen($password) > 16) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Mật khẩu phải từ 8 đến 16 ký tự.']);
                exit;
            }

            // KIỂM TRA MẬT KHẨU: CHỮ HOA, CHỮ THƯỜNG, SỐ
            if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Mật khẩu phải bao gồm ít nhất 1 chữ hoa, 1 chữ thường và 1 chữ số.']);
                exit;
            }

            if (
                !filter_var($email, FILTER_VALIDATE_EMAIL) ||
                !preg_match('/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/', $email)
            ) {
                http_response_code(400);
                echo json_encode(['error' => 'Email không hợp lệ. Vui lòng nhập đúng định dạng (ví dụ: email@gmail.com).']);
                exit;
            }

            $typoDomains = [
                'gmail.co', 'gmail.con', 'gmail.cm', 'gmial.com', 'gmai.com',
                'yaho.com', 'yahoo.con', 'hotmal.com', 'hotmail.con', 'outlok.com'
            ];
            $emailParts = explode('@', strtolower($email), 2);
            $localPart = $emailParts[0] ?? '';
            $domainPart = $emailParts[1] ?? '';

            if (
                $localPart === '' ||
                $domainPart === '' ||
                str_starts_with($localPart, '.') ||
                str_ends_with($localPart, '.') ||
                str_contains($localPart, '..') ||
                str_starts_with($domainPart, '.') ||
                str_ends_with($domainPart, '.') ||
                str_contains($domainPart, '..')
            ) {
                http_response_code(400);
                echo json_encode(['error' => 'Định dạng Email không hợp lệ. Vui lòng kiểm tra lại.']);
                exit;
            }

            if (in_array($domainPart, $typoDomains, true)) {
                http_response_code(400);
                echo json_encode(['error' => 'Tên miền email có vẻ bị gõ nhầm (' . $domainPart . '). Vui lòng kiểm tra lại.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                http_response_code(400);
                echo json_encode(['error' => 'Tên đăng nhập hoặc Email này đã tồn tại trên hệ thống']);
                exit;
            }

            // [BẢO MẬT 3.2] Băm mật khẩu trước khi lưu tài khoản mới
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, 'user')");
            $stmt->execute([$username, $hashed_password, $full_name, $email, $phone]);
            echo json_encode(['success' => true, 'message' => 'Đăng ký thành công']);
            exit;
        }

        elseif ($action === 'change_password') {
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['error' => 'Vui lòng đăng nhập']);
                exit;
            }

            $old_pass = (string) ($data['old_password'] ?? '');
            $new_pass = (string) ($data['new_password'] ?? '');

            if ($new_pass === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Mật khẩu mới không được để trống']);
                exit;
            }

            if (strlen($new_pass) < 8 || strlen($new_pass) > 16) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Mật khẩu mới phải từ 8 đến 16 ký tự.']);
                exit;
            }

            if (!preg_match('/[A-Z]/', $new_pass) || !preg_match('/[a-z]/', $new_pass) || !preg_match('/[0-9]/', $new_pass)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Mật khẩu mới phải bao gồm ít nhất 1 chữ hoa, 1 chữ thường và 1 chữ số.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            $isOldPassValid = false;
            if ($user) {
                $storedPassword = (string) ($user['password'] ?? '');
                if (password_verify($old_pass, $storedPassword)) {
                    $isOldPassValid = true;
                } elseif ($storedPassword !== '' && hash_equals($storedPassword, $old_pass)) {
                    $isOldPassValid = true;
                }
            }

            if ($isOldPassValid) {
                $hashed_new_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_new_pass, $_SESSION['user_id']]);
                echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công']);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Mật khẩu cũ không chính xác']);
            }
            exit;
        }

        elseif ($action === 'forgot_password') {
            $username = trim((string) ($data['username'] ?? ''));
            $full_name = trim((string) ($data['full_name'] ?? ''));
            $new_password = (string) ($data['new_password'] ?? '');

            if ($username === '' || $full_name === '' || $new_password === '') {
                http_response_code(400);
                echo json_encode(['error' => 'Vui lòng nhập đầy đủ thông tin khôi phục mật khẩu']);
                exit;
            }

            if (strlen($new_password) < 8 || strlen($new_password) > 16) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Mật khẩu mới phải từ 8 đến 16 ký tự.']);
                exit;
            }

            if (!preg_match('/[A-Z]/', $new_password) || !preg_match('/[a-z]/', $new_password) || !preg_match('/[0-9]/', $new_password)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Mật khẩu mới phải bao gồm ít nhất 1 chữ hoa, 1 chữ thường và 1 chữ số.']);
                exit;
            }

            $stmt = $pdo->prepare("SELECT id, google_id FROM users WHERE username = ? AND full_name = ? LIMIT 1");
            $stmt->execute([$username, $full_name]);
            $user = $stmt->fetch();

            if ($user) {
                if (!empty($user['google_id'])) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Tài khoản đăng nhập bằng Google không cần đổi mật khẩu tại đây!']);
                    exit;
                }

                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $updateStmt->execute([$hashed_new_password, $user['id']]);

                echo json_encode(['success' => true, 'message' => 'Khôi phục mật khẩu thành công!']);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Tên đăng nhập hoặc Họ và tên không khớp!']);
            }
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Hành động POST không hợp lệ']);
        exit;
    }

    elseif ($method === 'GET') {
        if ($action === 'check') {
            if (isset($_SESSION['user_id'])) {
                $stmtPts = $pdo->prepare("SELECT points, member_level FROM users WHERE id = ? LIMIT 1");
                $stmtPts->execute([$_SESSION['user_id']]);
                $row = $stmtPts->fetch();
                $points = (int) ($row['points'] ?? 0);

                $stmtSpent = $pdo->prepare("SELECT COALESCE(SUM(points_spent), 0) FROM reward_redemptions WHERE user_id = ?");
                $stmtSpent->execute([$_SESSION['user_id']]);
                $tier_points = $points + (int) $stmtSpent->fetchColumn();

                echo json_encode([
                    'isLoggedIn' => true,
                    'username' => $_SESSION['full_name'] ?? $_SESSION['username'],
                    'role' => $_SESSION['role'],
                    'points' => $points,
                    'tier_points' => $tier_points,
                    'member_level' => cm_member_level($tier_points),
                    'member_code' => 'CM-' . str_pad((string) $_SESSION['user_id'], 6, '0', STR_PAD_LEFT)
                ]);
            } else {
                echo json_encode(['isLoggedIn' => false]);
            }
            exit;
        }

        elseif ($action === 'logout') {
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Đã đăng xuất']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['error' => 'Hành động GET không hợp lệ']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Phương thức không được hỗ trợ']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi hệ thống xác thực']);
}
?>