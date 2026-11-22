<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Phương thức không hợp lệ']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

$fullName = trim($data['full_name'] ?? '');
$email    = trim($data['email'] ?? '');
$rawPhone = trim($data['phone'] ?? '');
$subject  = trim($data['subject'] ?? 'Góp ý dịch vụ');
$message  = trim($data['message'] ?? '');

// Chỉ giữ lại chữ số để kiểm tra đúng 10 số Việt Nam
$phone = preg_replace('/\D+/', '', $rawPhone);

// Lấy user_id nếu khách đã đăng nhập
$userId = $_SESSION['user_id'] ?? null;

if ($fullName === '' || $email === '' || $message === '') {
    echo json_encode(['success' => false, 'error' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
    exit;
}

// Kiểm tra chặt định dạng email và đuôi tên miền hợp lệ
$emailLower = strtolower($email);
if (
    strlen($email) > 255 ||
    !filter_var($email, FILTER_VALIDATE_EMAIL) ||
    !preg_match('/^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/', $emailLower) ||
    str_ends_with($emailLower, '.co') ||
    !preg_match('/\.(com|vn|com\.vn|net|org|edu\.vn|io|co\.uk)$/', $emailLower)
) {
    echo json_encode(['success' => false, 'error' => 'Địa chỉ email không hợp lệ']);
    exit;
}

// Kiểm tra chặt số điện thoại: đúng 10 số, bắt đầu bằng 03, 05, 07, 08, 09
if (strlen($phone) !== 10 || !preg_match('/^(03|05|07|08|09)\d{8}$/', $phone)) {
    echo json_encode([
        'success' => false,
        'error' => 'Số điện thoại không hợp lệ! Vui lòng nhập đúng 10 số (vd: 0912345678)'
    ]);
    exit;
}

if ($subject === '') {
    $subject = 'Góp ý dịch vụ';
}

try {
    $stmt = $pdo->prepare("INSERT INTO contacts (user_id, full_name, email, phone, subject, message, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([$userId, $fullName, $email, $phone, $subject, $message]);

    echo json_encode(['success' => true, 'message' => 'Gửi góp ý thành công']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
}