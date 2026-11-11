<?php
header('Content-Type: application/json');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $movie_id = $_GET['movie_id'] ?? null;
        $showAll  = isset($_GET['all']) && $_GET['all'] === '1';

        if ($movie_id) {
            $dateFilter = $showAll ? '' : 'AND st.show_date >= CURDATE()';
            $stmt = $pdo->prepare("
                SELECT st.*, r.name AS room_name, m.title AS movie_title, m.duration, m.poster_url
                FROM showtimes st
                JOIN rooms r ON st.room_id = r.id
                JOIN movies m ON st.movie_id = m.id
                WHERE st.movie_id = ? $dateFilter
                ORDER BY st.show_date, st.show_time
            ");
            $stmt->execute([$movie_id]);
            echo json_encode($stmt->fetchAll());
        } else {
            // Lấy toàn bộ suất chiếu kèm thông tin phim và phòng để Admin quản lý
            $stmt = $pdo->query("
                SELECT st.*, r.name AS room_name, m.title AS movie_title, m.duration, m.poster_url
                FROM showtimes st
                JOIN rooms r ON st.room_id = r.id
                JOIN movies m ON st.movie_id = m.id
                ORDER BY st.show_date DESC, st.show_time
            ");
            echo json_encode($stmt->fetchAll());
        }
        break;

    case 'POST':
        // Bảo vệ API: Chỉ Admin mới được thêm suất chiếu
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Không có quyền truy cập']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['movie_id']) || empty($data['room_id']) || empty($data['show_date']) || empty($data['show_time'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu thông tin suất chiếu bắt buộc']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO showtimes (movie_id, room_id, show_date, show_time, price_standard, price_vip, price_couple) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['movie_id'],
            $data['room_id'],
            $data['show_date'],
            $data['show_time'],
            $data['price_standard'] ?? 90000,
            $data['price_vip'] ?? 130000,
            $data['price_couple'] ?? 220000
        ]);

        echo json_encode([
            'message' => 'Thêm suất chiếu thành công', 
            'id' => $pdo->lastInsertId()
        ]);
        break;

    case 'DELETE':
        // Bảo vệ API: Chỉ Admin mới được xóa suất chiếu
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Không có quyền truy cập']);
            exit;
        }

        $id = $_GET['id'] ?? null;
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM showtimes WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['message' => 'Đã xóa suất chiếu thành công']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu ID suất chiếu cần xóa']);
        }
        break;
}
?>