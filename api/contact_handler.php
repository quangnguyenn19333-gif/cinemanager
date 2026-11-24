<?php
// [BẢO MẬT 1] Ngăn chặn lỗi PHP in ra làm hỏng chuỗi JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// [BẢO MẬT 2] Khởi tạo session ĐỂ LẤY ĐƯỢC user_id CỦA KHÁCH
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Phương thức không hợp lệ']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

// [BẢO MẬT 3] Dùng strip_tags để lột bỏ mọi thẻ HTML/JavaScript (Chống XSS)
$fullName = trim(strip_tags($data['full_name'] ?? ''));
$email    = trim($data['email'] ?? '');
$rawPhone = trim($data['phone'] ?? '');
$subject  = trim(strip_tags($data['subject'] ?? 'Góp ý dịch vụ'));
$message  = trim(strip_tags($data['message'] ?? ''));

// Chỉ giữ lại chữ số để kiểm tra đúng 10 số Việt Nam
$phone = preg_replace('/\D+/', '', $rawPhone);

// Lấy user_id nếu khách đã đăng nhập (Giờ thì biến này mới hoạt động)
$userId = $_SESSION['user_id'] ?? null;

if ($fullName === '' || $email === '' || $message === '') {
    http_response_code(400);
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
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Địa chỉ email không hợp lệ']);
    exit;
}

// Kiểm tra chặt số điện thoại: đúng 10 số, bắt đầu bằng 03, 05, 07, 08, 09
if (strlen($phone) !== 10 || !preg_match('/^(03|05|07|08|09)\d{8}$/', $phone)) {
    http_response_code(400);
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
    http_response_code(500);
    // [BẢO MẬT 4] Báo lỗi chung chung, không phơi bày cấu trúc Database
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Không thể gửi góp ý lúc này.']);
}
?>