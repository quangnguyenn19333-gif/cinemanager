<?php
header('Content-Type: application/json');
require_once 'db.php';

// Khởi tạo session để check role admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $movie_id = $_GET['movie_id'] ?? null;
        $showAll  = isset($_GET['all']) && $_GET['all'] === '1';

        try {
            if ($movie_id) {
                $dateFilter = $showAll ? '' : 'AND st.show_date >= CURDATE()';
                $stmt = $pdo->prepare("
                    SELECT st.*, r.name AS room_name, m.title AS movie_title, m.duration, m.poster_url, c.name AS cinema_name,
                           -- Đếm số ghế đã được đặt hoặc đang giữ của suất chiếu này
                           (
                               (SELECT COUNT(bs.seat_id) FROM booking_seats bs JOIN bookings b ON bs.booking_id = b.id WHERE b.showtime_id = st.id) 
                               + 
                               (SELECT COUNT(sh.seat_id) FROM seat_holds sh WHERE sh.showtime_id = st.id)
                           ) AS booked_seats
                    FROM showtimes st
                    JOIN rooms r ON st.room_id = r.id
                    JOIN movies m ON st.movie_id = m.id
                    LEFT JOIN cinemas c ON st.cinema_id = c.id
                    WHERE st.movie_id = ? $dateFilter
                    ORDER BY st.show_date, st.show_time
                ");
                $stmt->execute([$movie_id]);
                echo json_encode($stmt->fetchAll());
            } else {
                // ĐÃ SỬA: Đồng bộ cách đếm ghế bằng booking_seats và seat_holds thay vì dùng bảng showtime_seats không tồn tại
                $stmt = $pdo->query("
                    SELECT st.*, r.name AS room_name, m.title AS movie_title, m.duration, m.poster_url, c.name AS cinema_name,
                           (
                               (SELECT COUNT(bs.seat_id) FROM booking_seats bs JOIN bookings b ON bs.booking_id = b.id WHERE b.showtime_id = st.id) 
                               + 
                               (SELECT COUNT(sh.seat_id) FROM seat_holds sh WHERE sh.showtime_id = st.id)
                           ) AS booked_seats
                    FROM showtimes st
                    JOIN rooms r ON st.room_id = r.id
                    JOIN movies m ON st.movie_id = m.id
                    LEFT JOIN cinemas c ON st.cinema_id = c.id
                    ORDER BY st.show_date DESC, st.show_time
                ");
                echo json_encode($stmt->fetchAll());
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi truy xuất dữ liệu: ' . $e->getMessage()]);
        }
        break;

    case 'POST':
        // [CHUẨN ĐỒ ÁN] BẬT LẠI BẢO VỆ API: Bắt buộc phải là Admin mới được thêm suất chiếu
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Từ chối truy cập: Bạn không phải Quản trị viên!']);
            exit;
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['movie_id']) || empty($data['cinema_id']) || empty($data['room_id']) || empty($data['show_date']) || empty($data['show_time'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Vui lòng điền đầy đủ: Phim, Cơ sở rạp, Phòng chiếu, Ngày và Giờ']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO showtimes (movie_id, cinema_id, room_id, show_date, show_time, price_standard, price_vip, price_couple) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['movie_id'],
                $data['cinema_id'],
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
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi cơ sở dữ liệu: Không thể thêm suất chiếu']);
        }
        break;

    case 'DELETE':
        // [CHUẨN ĐỒ ÁN] BẬT LẠI BẢO VỆ API: Admin mới được quyền Xóa
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Từ chối truy cập: Bạn không có quyền xóa!']);
            exit;
        }

        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM showtimes WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['message' => 'Đã xóa suất chiếu thành công']);
            } catch (PDOException $e) {
                http_response_code(400);
                echo json_encode(['error' => 'Không thể xóa! Suất chiếu này đã có khách hàng đặt vé.']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu ID suất chiếu cần xóa']);
        }
        break;
}
?>