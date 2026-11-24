<?php
// [BẢO MẬT 1] Khởi tạo session an toàn, tránh lỗi "Session already started"
if (session_status() === PHP_SESSION_NONE) {
    // Ép trình duyệt bảo vệ Cookie Session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

$host = 'localhost';
$db   = 'quan_ly_rap_phim';
$user = 'root';
$pass = ''; // Mặc định của XAMPP là trống
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // [BẢO MẬT 2] Che giấu thông tin lỗi máy chủ và trả về định dạng JSON chuẩn
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Lỗi máy chủ: Không thể kết nối cơ sở dữ liệu.']);
    exit; // Lệnh exit để chặn đứng việc chạy tiếp các file API bên dưới
}

// ==========================================
// TÍCH ĐIỂM & HẠNG THÀNH VIÊN
// ==========================================
const POINT_RATE = 10000; // 10.000đ = 1 điểm

// Tính số điểm nhận được từ một giao dịch (làm tròn xuống)
function cm_calc_points($total_price) {
    return (int) floor($total_price / POINT_RATE);
}

// Xác định hạng theo tổng điểm.
function cm_member_level($points) {
    if ($points >= 3000) return 'Platinum'; // Bạch kim
    if ($points >= 1500) return 'Gold';     // Vàng
    if ($points >= 500)  return 'Silver';   // Bạc
    return 'Bronze';                        // Thường
}
?>