<?php
header('Content-Type: application/json');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Không có quyền truy cập']);
    exit;
}

$action = $_GET['action'] ?? 'summary';
$selectedMonth = $_GET['month'] ?? '';
$selectedQuarter = $_GET['quarter'] ?? '';
$selectedDate = $_GET['date'] ?? '';
$currentYear = date('Y');

// Xây dựng điều kiện lọc thời gian linh hoạt (Ngày -> Tháng -> Quý)
$dateCondition = "";
if ($selectedDate !== '') {
    $safeDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) ? $selectedDate : date('Y-m-d');
    $dateCondition = "AND DATE(b.created_at) = '$safeDate'";
} elseif ($selectedMonth !== '') {
    $safeMonth = (int)$selectedMonth;
    $dateCondition = "AND MONTH(b.created_at) = $safeMonth AND YEAR(b.created_at) = $currentYear";
} elseif ($selectedQuarter !== '') {
    $safeQuarter = (int)$selectedQuarter;
    $dateCondition = "AND QUARTER(b.created_at) = $safeQuarter AND YEAR(b.created_at) = $currentYear";
}

switch ($action) {
    case 'summary':
        $stmtRev = $pdo->prepare("SELECT SUM(total_price) FROM bookings b WHERE status = 'confirmed' $dateCondition");
        $stmtRev->execute();
        $totalRevenue = $stmtRev->fetchColumn();

        $stmtTic = $pdo->prepare("SELECT COUNT(*) FROM bookings b WHERE status = 'confirmed' $dateCondition");
        $stmtTic->execute();
        $totalTickets = $stmtTic->fetchColumn();

        $totalMovies = $pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
        $totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();

        echo json_encode([
            'total_revenue' => (int)$totalRevenue,
            'total_tickets' => (int)$totalTickets,
            'total_movies'  => (int)$totalMovies,
            'total_users'   => (int)$totalUsers
        ]);
        break;

    case 'by_movie':
        $sql = "
            SELECT m.title, SUM(b.total_price) as revenue, COUNT(b.id) as tickets
            FROM movies m
            JOIN showtimes st ON m.id = st.movie_id
            JOIN bookings b ON st.id = b.showtime_id
            WHERE b.status = 'confirmed' $dateCondition
            GROUP BY m.id
            ORDER BY revenue DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;

    case 'recent':
        $stmt = $pdo->query("
            SELECT b.*, u.full_name, m.title as movie_title
            FROM bookings b
            JOIN users u ON b.user_id = u.id
            JOIN showtimes st ON b.showtime_id = st.id
            JOIN movies m ON st.movie_id = m.id
            ORDER BY b.created_at DESC
            LIMIT 10
        ");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        break;
}
?>