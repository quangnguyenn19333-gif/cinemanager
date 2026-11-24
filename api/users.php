<?php
// [BẢO MẬT 1] Ngăn chặn in lỗi PHP/HTML ra ngoài làm hỏng cấu trúc JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// Khởi tạo session an toàn
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// [BẢO MẬT 2] Khóa chặt cửa: Chỉ Admin mới được qua
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Từ chối truy cập: Chỉ Quản trị viên mới có quyền xem danh sách.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        try {
            // Lấy tất cả tài khoản có email hợp lệ, bao gồm đầy đủ điểm và hạng
            $stmt = $pdo->query("
                SELECT u.id, u.username, u.full_name, u.email, u.phone, u.role, 
                       u.points, u.tier_points, u.member_level, u.created_at, 
                       COALESCE(SUM(b.total_price), 0) AS total_spent
                FROM users u
                LEFT JOIN bookings b ON u.id = b.user_id
                WHERE u.email IS NOT NULL AND u.email != ''
                GROUP BY u.id, u.username, u.full_name, u.email, u.phone, u.role, 
                         u.points, u.tier_points, u.member_level, u.created_at
                ORDER BY total_spent DESC, u.created_at DESC
            ");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Trả về chuẩn format JSON có bọc mảng data
            echo json_encode([
                'success' => true,
                'data' => $users ?: []
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            // [BẢO MẬT 3] Báo lỗi chung chung, che giấu lỗi SQL thực tế
            echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Không thể tải danh sách khách hàng.']);
        }
        break;

    case 'POST':
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Chức năng này đã bị khóa.']);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Phương thức không được hỗ trợ.']);
        break;
}
?>