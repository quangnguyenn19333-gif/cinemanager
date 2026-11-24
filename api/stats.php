<?php
// Ngăn lỗi HTML in ra làm hỏng chuỗi JSON trả về
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// [BẢO MẬT 1] Bắt buộc khởi tạo session trước khi kiểm tra quyền
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// [BẢO MẬT 2] Chặn đứng người dùng thường và khách vãng lai
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Từ chối truy cập: Chỉ Quản trị viên mới được xem Thống kê!']);
    exit;
}

$action = $_GET['action'] ?? 'summary';
$selectedMonth = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
$selectedQuarter = filter_input(INPUT_GET, 'quarter', FILTER_VALIDATE_INT);
$selectedDate = trim($_GET['date'] ?? '');
$currentYear = (int)date('Y');

// [TỐI ƯU 1] Xây dựng điều kiện lọc an toàn bằng Prepared Statement thay vì nối chuỗi trực tiếp
$whereClause = "WHERE b.status = 'confirmed'";
$params = [];

if ($selectedDate !== '') {
    $safeDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) ? $selectedDate : date('Y-m-d');
    $whereClause .= " AND DATE(b.created_at) = ?";
    $params[] = $safeDate;
} elseif ($selectedMonth !== false && $selectedMonth !== null) {
    $whereClause .= " AND MONTH(b.created_at) = ? AND YEAR(b.created_at) = ?";
    $params[] = $selectedMonth;
    $params[] = $currentYear;
} elseif ($selectedQuarter !== false && $selectedQuarter !== null) {
    $whereClause .= " AND QUARTER(b.created_at) = ? AND YEAR(b.created_at) = ?";
    $params[] = $selectedQuarter;
    $params[] = $currentYear;
}

try {
    switch ($action) {
        case 'summary':
            // 1. Tổng doanh thu
            $stmtRev = $pdo->prepare("SELECT COALESCE(SUM(total_price), 0) FROM bookings b $whereClause");
            $stmtRev->execute($params);
            $totalRevenue = $stmtRev->fetchColumn();

            // 2. Tổng số vé
            $stmtTic = $pdo->prepare("SELECT COUNT(*) FROM bookings b $whereClause");
            $stmtTic->execute($params);
            $totalTickets = $stmtTic->fetchColumn();

            // 3. Tổng số phim & Tổng khách hàng (Không phụ thuộc bộ lọc ngày tháng)
            $totalMovies = $pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
            $totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

            echo json_encode([
                'success'       => true,
                'total_revenue' => (int)$totalRevenue,
                'total_tickets' => (int)$totalTickets,
                'total_movies'  => (int)$totalMovies,
                'total_users'   => (int)$totalUsers
            ]);
            break;

        case 'by_movie':
            // Báo cáo doanh thu theo từng phim
            $sql = "
                SELECT m.title, 
                       COALESCE(SUM(b.total_price), 0) as revenue, 
                       COUNT(b.id) as tickets
                FROM movies m
                JOIN showtimes st ON m.id = st.movie_id
                JOIN bookings b ON st.id = b.showtime_id
                $whereClause
                GROUP BY m.id
                ORDER BY revenue DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true, 
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;

        case 'recent':
            // 10 giao dịch gần nhất
            $stmt = $pdo->query("
                SELECT b.id, b.total_price, b.created_at, b.status, 
                       u.full_name, m.title as movie_title
                FROM bookings b
                JOIN users u ON b.user_id = u.id
                JOIN showtimes st ON b.showtime_id = st.id
                JOIN movies m ON st.movie_id = m.id
                ORDER BY b.created_at DESC
                LIMIT 10
            ");
            echo json_encode([
                'success' => true, 
                'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Hành động không hợp lệ.']);
            break;
    }
} catch (PDOException $e) {
    http_response_code(500);
    // [BẢO MẬT 3] Che lỗi SQL thực tế để tránh bị khai thác
    echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Không thể tính toán dữ liệu thống kê.']);
}
?>