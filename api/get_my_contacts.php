<?php
// [BẢO MẬT 1] Chặn hiển thị lỗi HTML ra ngoài làm hỏng cấu trúc JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// [BẢO MẬT 2] Khởi tạo Session bắt buộc để lấy được ID khách hàng
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// [BẢO MẬT 3] Trả về mã lỗi 401 đàng hoàng nếu khách chưa đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Vui lòng đăng nhập để xem lịch sử góp ý.']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    // Lấy danh sách góp ý của đúng người dùng đang đăng nhập
    $stmt = $pdo->prepare("SELECT * FROM contacts WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => $contacts ?: []
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    // [BẢO MẬT 4] Bọc Try-Catch và che lỗi SQL thực tế
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Không thể tải lịch sử góp ý.']);
}
?>