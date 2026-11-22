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
                           -- ĐỔI TÊN BIẾN THÀNH tickets_sold ĐỂ JS TỰ ĐỘNG KHÓA NÚT SỬA/XÓA
                           (
                               (SELECT COUNT(bs.seat_id) FROM booking_seats bs JOIN bookings b ON bs.booking_id = b.id WHERE b.showtime_id = st.id) 
                               + 
                               (SELECT COUNT(sh.seat_id) FROM seat_holds sh WHERE sh.showtime_id = st.id)
                           ) AS tickets_sold
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
                $stmt = $pdo->query("
                    SELECT st.*, r.name AS room_name, m.title AS movie_title, m.duration, m.poster_url, c.name AS cinema_name,
                           (
                               (SELECT COUNT(bs.seat_id) FROM booking_seats bs JOIN bookings b ON bs.booking_id = b.id WHERE b.showtime_id = st.id) 
                               + 
                               (SELECT COUNT(sh.seat_id) FROM seat_holds sh WHERE sh.showtime_id = st.id)
                           ) AS tickets_sold
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
        // [CHUẨN ĐỒ ÁN] BẬT LẠI BẢO VỆ API
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

        // TÁCH LOGIC THÊM MỚI (INSERT) VÀ CẬP NHẬT (UPDATE)
        $id = !empty($data['id']) ? intval($data['id']) : null;
        $price_standard = $data['price_standard'] ?? 90000;
        $price_vip = $data['price_vip'] ?? 130000;
        $price_couple = $data['price_couple'] ?? 220000;

        try {
            if ($id) {
                // Nếu là cập nhật: Phải kiểm tra xem suất chiếu này đã bán được vé nào chưa
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE showtime_id = ?");
                $checkStmt->execute([$id]);
                $hasBookings = $checkStmt->fetchColumn();

                if ($hasBookings > 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Không thể sửa! Suất chiếu này đã có khách hàng đặt vé.']);
                    exit;
                }

                // Thực hiện lệnh UPDATE
                $stmt = $pdo->prepare("
                    UPDATE showtimes 
                    SET movie_id = ?, cinema_id = ?, room_id = ?, show_date = ?, show_time = ?, price_standard = ?, price_vip = ?, price_couple = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['movie_id'], $data['cinema_id'], $data['room_id'], 
                    $data['show_date'], $data['show_time'], $price_standard, $price_vip, $price_couple, $id
                ]);

                echo json_encode(['success' => true, 'message' => 'Cập nhật suất chiếu thành công']);
            } else {
                // Thực hiện lệnh INSERT
                $stmt = $pdo->prepare("
                    INSERT INTO showtimes (movie_id, cinema_id, room_id, show_date, show_time, price_standard, price_vip, price_couple) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['movie_id'], $data['cinema_id'], $data['room_id'], 
                    $data['show_date'], $data['show_time'], $price_standard, $price_vip, $price_couple
                ]);

                echo json_encode(['success' => true, 'message' => 'Thêm suất chiếu thành công', 'id' => $pdo->lastInsertId()]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Lỗi cơ sở dữ liệu: Không thể thao tác suất chiếu']);
        }
        break;

    case 'DELETE':
        // [CHUẨN ĐỒ ÁN] BẬT LẠI BẢO VỆ API
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Từ chối truy cập: Bạn không có quyền xóa!']);
            exit;
        }

        $id = $_GET['id'] ?? null;
        if ($id) {
            try {
                // Kiểm tra nếu đã bán vé thì CẤM XÓA tuyệt đối
                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE showtime_id = ?");
                $checkStmt->execute([$id]);
                if ($checkStmt->fetchColumn() > 0) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Không thể xóa! Suất chiếu này đã có vé được bán.']);
                    exit;
                }

                // Thực hiện lệnh XÓA
                $stmt = $pdo->prepare("DELETE FROM showtimes WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Đã xóa suất chiếu thành công']);
            } catch (PDOException $e) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Không thể xóa do ràng buộc dữ liệu!']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Thiếu ID suất chiếu cần xóa']);
        }
        break;
}
?>