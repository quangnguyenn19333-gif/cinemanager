<?php
session_start();
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
     die("Kết nối database thất bại: " . $e->getMessage());
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
