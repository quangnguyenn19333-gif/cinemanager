<?php
// [BẢO MẬT 1] Tắt hiển thị lỗi PHP ra màn hình để bảo vệ cấu trúc JSON và DB
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// [BẢO MẬT 2] Chặn các phương thức không hợp lệ (Chỉ cho phép GET)
if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Phương thức không được hỗ trợ.']);
    exit;
}

try {
    // [TỐI ƯU] Sử dụng PDO::FETCH_ASSOC để mảng JSON gọn nhẹ, tiết kiệm băng thông
    $stmt = $pdo->query("SELECT * FROM rooms ORDER BY id");
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Trả về trực tiếp mảng danh sách
    echo json_encode($rooms ?: []);
} catch (PDOException $e) {
    http_response_code(500);
    // [BẢO MẬT 3] Báo lỗi chung chung, không phơi bày lỗi SQL thực tế
    echo json_encode(['error' => 'Lỗi hệ thống: Không thể tải danh sách phòng chiếu.']);
}
?>