<?php
header('Content-Type: application/json');
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Không có quyền truy cập.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Lấy tất cả tài khoản có email hợp lệ, tính tổng tiền (nếu chưa mua vé thì mặc định là 0)
        // Sắp xếp ai chi tiêu nhiều nhất lên đầu, khách mới đăng ký 0đ sẽ nằm ở các hàng dưới
        $stmt = $pdo->query("
            SELECT u.id, u.username, u.full_name, u.email, u.phone, u.role, u.created_at, 
                   COALESCE(SUM(b.total_price), 0) AS total_spent
            FROM users u
            LEFT JOIN bookings b ON u.id = b.user_id
            WHERE u.email IS NOT NULL AND u.email != ''
            GROUP BY u.id, u.username, u.full_name, u.email, u.phone, u.role, u.created_at
            ORDER BY total_spent DESC, u.created_at DESC
        ");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
        break;

    case 'POST':
        http_response_code(400);
        echo json_encode(['error' => 'Chức năng này đã bị ẩn trên giao diện']);
        break;
}
?>