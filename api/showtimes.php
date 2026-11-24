<?php
header('Content-Type: application/json');
require_once 'db.php';

// [BẢO MẬT 1] Bắt buộc khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];

// Kiểm tra quyền Admin
function checkAdminAuth(): void
{
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Từ chối truy cập: Bạn không phải Quản trị viên!']);
        exit;
    }
}

// Kiểm tra suất chiếu đã có vé đặt hoặc ghế đang giữ
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

// Cấu hình phòng chiếu theo roomId
function getRoomConfigById(int $roomId): array
{
    $rem = $roomId % 100;
    if ($rem >= 1 && $rem <= 10) {
        return [
            'name' => 'Phòng Standard ' . sprintf('%02d', $rem),
            'type' => 'Standard',
            'rows' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'],
            'cols' => 10
        ];
    }
    if ($rem >= 11 && $rem <= 20) {
        return [
            'name' => 'Phòng VIP ' . sprintf('%02d', $rem - 10),
            'type' => 'VIP',
            'rows' => ['A', 'B', 'C', 'D', 'E', 'F'],
            'cols' => 8
        ];
    }
    $imaxNum = ($rem > 20) ? ($rem - 20) : 1;
    return [
        'name' => 'Phòng IMAX ' . sprintf('%02d', $imaxNum),
        'type' => 'IMAX',
        'rows' => ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'],
        'cols' => 12
    ];
}

// Xác định loại ghế theo loại phòng và hàng ghế
function resolveSeatType(string $roomType, string $row, string $lastRow): string
{
    if ($roomType === 'VIP') {
        return ($row === $lastRow) ? 'couple' : (($row === 'A') ? 'standard' : 'vip');
    }
    if ($roomType === 'IMAX') {
        return in_array($row, ['I', 'J'], true)
            ? 'couple'
            : (in_array($row, ['A', 'B', 'C'], true) ? 'standard' : 'vip');
    }
    return in_array($row, ['I', 'J'], true)
        ? 'couple'
        : (in_array($row, ['D', 'E', 'F', 'G', 'H'], true) ? 'vip' : 'standard');
}

// Tạo phòng và ghế nếu chưa có (khớp schema bảng rooms: id, name, total_rows, seats_per_row)
function ensureRoomAndSeatsExist(PDO $pdo, int $roomId): void
{
    $config = getRoomConfigById($roomId);
    $totalRows = count($config['rows']);
    $seatsPerRow = $config['cols'];

    $stmtRoom = $pdo->prepare("
        INSERT IGNORE INTO rooms (id, name, total_rows, seats_per_row)
        VALUES (?, ?, ?, ?)
    ");
    $stmtRoom->execute([$roomId, $config['name'], $totalRows, $seatsPerRow]);

    $checkSeats = $pdo->prepare("SELECT COUNT(*) FROM seats WHERE room_id = ?");
    $checkSeats->execute([$roomId]);

    if ((int)$checkSeats->fetchColumn() === 0) {
        $rows = $config['rows'];
        $cols = $config['cols'];
        $lastRow = end($rows);

        $pdo->beginTransaction();
        try {
            $insertSeat = $pdo->prepare("
                INSERT IGNORE INTO seats (room_id, row_label, seat_number, seat_type)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($rows as $row) {
                for ($col = 1; $col <= $cols; $col++) {
                    $insertSeat->execute([
                        $roomId,
                        $row,
                        $col,
                        resolveSeatType($config['type'], $row, $lastRow)
                    ]);
                }
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

// Chuẩn hóa giờ chiếu về HH:MM:SS
function normalizeShowTime(string $rawTime): ?string
{
    $t = trim($rawTime);
    if (preg_match('/^\d{2}:\d{2}$/', $t)) {
        return $t . ':00';
    }
    if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $t)) {
        return $t;
    }
    return null;
}

switch ($method) {
    case 'GET':
        $movieId = filter_input(INPUT_GET, 'movie_id', FILTER_VALIDATE_INT);
        $showAll = (isset($_GET['all']) && $_GET['all'] === '1');

        try {
            $ticketCountSql = "(
                (SELECT COUNT(bs.seat_id) FROM booking_seats bs JOIN bookings b ON bs.booking_id = b.id WHERE b.showtime_id = st.id AND (b.status = 'confirmed' OR b.status IS NULL OR b.status = ''))
                + (SELECT COUNT(sh.seat_id) FROM seat_holds sh WHERE sh.showtime_id = st.id AND sh.expires_at > NOW())
            ) AS tickets_sold";

            if ($movieId) {
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
                $stmt->execute([$movieId]);
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
            }
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            error_log('GET /api/showtimes.php error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Lỗi truy xuất dữ liệu!']);
        }
        break;

    case 'POST':
        checkAdminAuth();

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Dữ liệu JSON không hợp lệ!']);
            exit;
        }

        if (
            empty($data['movie_id']) || empty($data['cinema_id']) ||
            empty($data['room_id']) || empty($data['show_date']) || empty($data['show_time'])
        ) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Vui lòng điền đầy đủ: Phim, Cơ sở rạp, Phòng chiếu, Ngày và Giờ']);
            exit;
        }

        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $movieId = filter_var($data['movie_id'], FILTER_VALIDATE_INT);
        $cinemaId = filter_var($data['cinema_id'], FILTER_VALIDATE_INT);
        $roomId = filter_var($data['room_id'], FILTER_VALIDATE_INT);
        $showDate = trim((string)$data['show_date']);
        $showTime = normalizeShowTime((string)$data['show_time']);

        if (!$movieId || !$cinemaId || !$roomId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Thông tin Phim, Rạp hoặc Phòng chiếu không hợp lệ!']);
            exit;
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $showDate) || !$showTime) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Ngày hoặc giờ chiếu không đúng định dạng!']);
            exit;
        }

        $priceStandard = max(0, intval($data['price_standard'] ?? 90000));
        $priceVip      = max(0, intval($data['price_vip'] ?? 130000));
        $priceCouple   = max(0, intval($data['price_couple'] ?? 220000));

        try {
            ensureRoomAndSeatsExist($pdo, $roomId);

            if ($id) {
                if (hasActiveTicketsOrHolds($pdo, $id)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Không thể sửa! Suất chiếu này đã có khách đặt vé hoặc giữ ghế.']);
                    exit;
                }

                $stmt = $pdo->prepare("
                    UPDATE showtimes 
                    SET movie_id=?, cinema_id=?, room_id=?, show_date=?, show_time=?, price_standard=?, price_vip=?, price_couple=?
                    WHERE id=?
                ");
                $stmt->execute([$movieId, $cinemaId, $roomId, $showDate, $showTime, $priceStandard, $priceVip, $priceCouple, $id]);
                echo json_encode(['success' => true, 'message' => 'Cập nhật suất chiếu thành công']);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO showtimes (movie_id, cinema_id, room_id, show_date, show_time, price_standard, price_vip, price_couple) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$movieId, $cinemaId, $roomId, $showDate, $showTime, $priceStandard, $priceVip, $priceCouple]);
                echo json_encode(['success' => true, 'message' => 'Thêm suất chiếu thành công', 'id' => $pdo->lastInsertId()]);
            }
        } catch (PDOException $e) {
            error_log('POST /api/showtimes.php error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Lỗi cơ sở dữ liệu!']);
        }
        break;

    case 'DELETE':
        checkAdminAuth();

        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            try {
                if (hasActiveTicketsOrHolds($pdo, $id)) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'error' => 'Không thể xóa! Suất chiếu này đã có vé được bán hoặc ghế đang giữ.']);
                    exit;
                }
                $stmt = $pdo->prepare("DELETE FROM showtimes WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'Đã xóa suất chiếu thành công']);
            } catch (PDOException $e) {
                error_log('DELETE /api/showtimes.php error: ' . $e->getMessage());
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Không thể xóa do lỗi hệ thống!']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Thiếu ID suất chiếu hợp lệ']);
        }
        break;
}