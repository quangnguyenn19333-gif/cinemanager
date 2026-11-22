<?php
header('Content-Type: application/json');
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];

/**
 * Kiểm tra suất chiếu đã có vé đặt hoặc ghế đang giữ tạm hay chưa
 */
function hasActiveTicketsOrHolds(PDO $pdo, int $showtimeId): bool
{
    $stmtBookings = $pdo->prepare("
        SELECT COUNT(*)
        FROM booking_seats bs
        JOIN bookings b ON bs.booking_id = b.id
        WHERE b.showtime_id = ?
    ");
    $stmtBookings->execute([$showtimeId]);
    if ((int)$stmtBookings->fetchColumn() > 0) {
        return true;
    }

    $stmtHolds = $pdo->prepare("SELECT COUNT(*) FROM seat_holds WHERE showtime_id = ?");
    $stmtHolds->execute([$showtimeId]);
    return (int)$stmtHolds->fetchColumn() > 0;
}

/**
 * Xác định loại phòng và cấu hình sơ đồ ghế theo room_id
 * - Standard: 100 ghế (10 hàng A-J x 10 cột)
 * - VIP: 48 ghế (6 hàng A-F x 8 cột)
 * - IMAX: 120 ghế (10 hàng A-J x 12 cột)
 */
function getRoomConfigById(int $roomId): array
{
    $rem = $roomId % 100;

    if ($rem >= 1 && $rem <= 10) {
        return [
            'name' => 'Phòng Standard ' . sprintf('%02d', $rem),
            'type' => 'Standard',
            'rows' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'],
            'cols' => 10,
        ];
    }

    if ($rem >= 11 && $rem <= 20) {
        return [
            'name' => 'Phòng VIP ' . sprintf('%02d', $rem - 10),
            'type' => 'VIP',
            'rows' => ['A', 'B', 'C', 'D', 'E', 'F'],
            'cols' => 8,
        ];
    }

    $imaxNum = ($rem > 20) ? ($rem - 20) : 1;
    return [
        'name' => 'Phòng IMAX ' . sprintf('%02d', $imaxNum),
        'type' => 'IMAX',
        'rows' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'],
        'cols' => 12,
    ];
}

/**
 * Xác định seat_type ('standard' | 'vip' | 'couple') theo loại phòng và hàng ghế
 */
function resolveSeatType(string $roomType, string $row, string $lastRow): string
{
    if ($roomType === 'VIP') {
        if ($row === $lastRow) {
            return 'couple';
        }
        return ($row === 'A') ? 'standard' : 'vip';
    }

    if ($roomType === 'IMAX') {
        // IMAX: A-C Thường, D-H VIP, I-J Couple
        if (in_array($row, ['I', 'J'], true)) {
            return 'couple';
        }
        return in_array($row, ['A', 'B', 'C'], true) ? 'standard' : 'vip';
    }

    // Standard: A-C Thường, D-H VIP, I-J Couple
    if (in_array($row, ['I', 'J'], true)) {
        return 'couple';
    }
    if (in_array($row, ['D', 'E', 'F', 'G', 'H'], true)) {
        return 'vip';
    }
    return 'standard';
}

/**
 * Tự động tạo phòng và sơ đồ ghế nếu phòng chưa tồn tại trong DB
 */
function ensureRoomAndSeatsExist(PDO $pdo, int $roomId, int $cinemaId): void
{
    $config = getRoomConfigById($roomId);

    $checkRoom = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE id = ?");
    $checkRoom->execute([$roomId]);

    if ((int)$checkRoom->fetchColumn() === 0) {
        try {
            $stmtRoom = $pdo->prepare("INSERT INTO rooms (id, cinema_id, name) VALUES (?, ?, ?)");
            $stmtRoom->execute([$roomId, $cinemaId, $config['name']]);
        } catch (PDOException $e) {
            $stmtRoomFallback = $pdo->prepare("INSERT INTO rooms (id, name) VALUES (?, ?)");
            $stmtRoomFallback->execute([$roomId, $config['name']]);
        }
    }

    $checkSeats = $pdo->prepare("SELECT COUNT(*) FROM seats WHERE room_id = ?");
    $checkSeats->execute([$roomId]);

    if ((int)$checkSeats->fetchColumn() === 0) {
        $rows = $config['rows'];
        $cols = $config['cols'];
        $lastRow = end($rows);

        $insertSeat = $pdo->prepare("
            INSERT IGNORE INTO seats (room_id, row_label, seat_number, seat_type)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($rows as $row) {
            for ($col = 1; $col <= $cols; $col++) {
                $seatType = resolveSeatType($config['type'], $row, $lastRow);
                $insertSeat->execute([$roomId, $row, $col, $seatType]);
            }
        }
    }
}

switch ($method) {
    case 'GET':
        $movieId = $_GET['movie_id'] ?? null;
        $showAll = isset($_GET['all']) && $_GET['all'] === '1';

        try {
            $ticketCountSql = "
                (
                    (SELECT COUNT(bs.seat_id) FROM booking_seats bs JOIN bookings b ON bs.booking_id = b.id WHERE b.showtime_id = st.id)
                    +
                    (SELECT COUNT(sh.seat_id) FROM seat_holds sh WHERE sh.showtime_id = st.id)
                ) AS tickets_sold
            ";

            if ($movieId !== null && $movieId !== '') {
                $dateFilter = $showAll ? '' : 'AND st.show_date >= CURDATE()';
                $stmt = $pdo->prepare("
                    SELECT st.*, r.name AS room_name, m.title AS movie_title, m.duration, m.poster_url, c.name AS cinema_name,
                           $ticketCountSql
                    FROM showtimes st
                    JOIN rooms r ON st.room_id = r.id
                    JOIN movies m ON st.movie_id = m.id
                    LEFT JOIN cinemas c ON st.cinema_id = c.id
                    WHERE st.movie_id = ? $dateFilter
                    ORDER BY st.show_date, st.show_time
                ");
                $stmt->execute([intval($movieId)]);
                echo json_encode($stmt->fetchAll());
            } else {
                $stmt = $pdo->query("
                    SELECT st.*, r.name AS room_name, m.title AS movie_title, m.duration, m.poster_url, c.name AS cinema_name,
                           $ticketCountSql
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
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Từ chối truy cập: Bạn không phải Quản trị viên!']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);

        if (
            empty($data['movie_id']) ||
            empty($data['cinema_id']) ||
            empty($data['room_id']) ||
            empty($data['show_date']) ||
            empty($data['show_time'])
        ) {
            http_response_code(400);
            echo json_encode(['error' => 'Vui lòng điền đầy đủ: Phim, Cơ sở rạp, Phòng chiếu, Ngày và Giờ']);
            exit;
        }

        $id            = !empty($data['id']) ? intval($data['id']) : null;
        $movieId       = intval($data['movie_id']);
        $cinemaId      = intval($data['cinema_id']);
        $roomId        = intval($data['room_id']);
        $priceStandard = isset($data['price_standard']) ? max(0, intval($data['price_standard'])) : 90000;
        $priceVip      = isset($data['price_vip']) ? max(0, intval($data['price_vip'])) : 130000;
        $priceCouple   = isset($data['price_couple']) ? max(0, intval($data['price_couple'])) : 220000;

        try {
            ensureRoomAndSeatsExist($pdo, $roomId, $cinemaId);

            if ($id !== null) {
                if (hasActiveTicketsOrHolds($pdo, $id)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Không thể sửa! Suất chiếu này đã có khách đặt vé hoặc giữ ghế.'
                    ]);
                    exit;
                }

                $stmt = $pdo->prepare("
                    UPDATE showtimes
                    SET movie_id = ?, cinema_id = ?, room_id = ?, show_date = ?, show_time = ?,
                        price_standard = ?, price_vip = ?, price_couple = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $movieId, $cinemaId, $roomId,
                    $data['show_date'], $data['show_time'],
                    $priceStandard, $priceVip, $priceCouple, $id
                ]);

                echo json_encode(['success' => true, 'message' => 'Cập nhật suất chiếu thành công']);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO showtimes (movie_id, cinema_id, room_id, show_date, show_time, price_standard, price_vip, price_couple)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $movieId, $cinemaId, $roomId,
                    $data['show_date'], $data['show_time'],
                    $priceStandard, $priceVip, $priceCouple
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Thêm suất chiếu thành công',
                    'id' => $pdo->lastInsertId()
                ]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Lỗi cơ sở dữ liệu: Không thể thao tác suất chiếu']);
        }
        break;

    case 'DELETE':
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Từ chối truy cập: Bạn không có quyền xóa!']);
            exit;
        }

        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id > 0) {
            try {
                if (hasActiveTicketsOrHolds($pdo, $id)) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Không thể xóa! Suất chiếu này đã có vé được bán hoặc ghế đang giữ.'
                    ]);
                    exit;
                }

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