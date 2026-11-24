<?php
// [BẢO MẬT 1] Tắt hiển thị lỗi HTML để bảo vệ cấu trúc JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// [BẢO MẬT 2] Khởi tạo Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'map';

// Tự động dọn dẹp các ghế giữ đã quá 5 phút
try {
    $pdo->exec("DELETE FROM seat_holds WHERE expires_at < NOW()");
} catch (PDOException $e) {
    // Im lặng bỏ qua lỗi dọn dẹp để không gián đoạn luồng chính
}

function cm_build_default_seats(int $room_id): array
{
    $rows = ['A', 'B', 'C', 'D', 'E', 'F'];
    $seats = [];
    $baseId = $room_id * 100;

    foreach ($rows as $rIdx => $rowLabel) {
        $seatType = ($rIdx >= 4) ? 'vip' : 'standard';
        for ($num = 1; $num <= 10; $num++) {
            $seats[] = [
                'id' => $baseId + ($rIdx * 10) + $num,
                'room_id' => $room_id,
                'row_label' => $rowLabel,
                'seat_number' => $num,
                'seat_type' => $seatType,
                'status' => 'available',
                'is_my_hold' => false,
            ];
        }
    }

    return $seats;
}

// ===================================================================
// LUỒNG 1: LẤY SƠ ĐỒ GHẾ (Dành cho mọi người)
// ===================================================================
if ($method === 'GET' && $action === 'map') {
    $showtime_id = filter_input(INPUT_GET, 'showtime_id', FILTER_VALIDATE_INT);
    if (!$showtime_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Thiếu ID suất chiếu hợp lệ.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT room_id FROM showtimes WHERE id = ?");
        $stmt->execute([$showtime_id]);
        $room_id = $stmt->fetchColumn();

        if (!$room_id) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Không tìm thấy suất chiếu.']);
            exit;
        }
        $room_id = (int) $room_id;

        // [TỐI ƯU 1] Fetch bằng FETCH_ASSOC cho gọn nhẹ
        $stmt = $pdo->prepare("
            SELECT s.id, s.room_id, s.row_label, s.seat_number, s.seat_type,
                CASE
                    WHEN bs.seat_id IS NOT NULL THEN 'booked'
                    WHEN sh.seat_id IS NOT NULL THEN 'held'
                    ELSE 'available'
                END AS status,
                sh.user_id AS held_by_user
            FROM seats s
            LEFT JOIN booking_seats bs ON bs.seat_id = s.id
                AND bs.booking_id IN (SELECT id FROM bookings WHERE showtime_id = ? AND status = 'confirmed')
            LEFT JOIN seat_holds sh ON sh.seat_id = s.id AND sh.showtime_id = ?
            WHERE s.room_id = ?
            ORDER BY s.row_label, s.seat_number
        ");
        $stmt->execute([$showtime_id, $showtime_id, $room_id]);
        $seats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $current_user = $_SESSION['user_id'] ?? null;

        // Nếu bảng seats chưa có cấu hình riêng cho room_id này, sinh sơ đồ mặc định và đồng bộ ghế đã đặt/giữ
        if (empty($seats)) {
            $seats = cm_build_default_seats($room_id);

            $stmtBooked = $pdo->prepare("
                SELECT bs.seat_id, s.row_label, s.seat_number
                FROM booking_seats bs
                JOIN bookings b ON bs.booking_id = b.id
                LEFT JOIN seats s ON bs.seat_id = s.id
                WHERE b.showtime_id = ? AND b.status = 'confirmed'
            ");
            $stmtBooked->execute([$showtime_id]);
            $bookedRows = $stmtBooked->fetchAll(PDO::FETCH_ASSOC);

            $stmtHeld = $pdo->prepare("
                SELECT sh.seat_id, sh.user_id, s.row_label, s.seat_number
                FROM seat_holds sh
                LEFT JOIN seats s ON sh.seat_id = s.id
                WHERE sh.showtime_id = ?
            ");
            $stmtHeld->execute([$showtime_id]);
            $heldRows = $stmtHeld->fetchAll(PDO::FETCH_ASSOC);

            foreach ($seats as &$seat) {
                $code = $seat['row_label'] . $seat['seat_number'];
                foreach ($bookedRows as $b) {
                    $bCode = ($b['row_label'] ?? '') . ($b['seat_number'] ?? '');
                    if ((int) $b['seat_id'] === (int) $seat['id'] || ($bCode !== '' && $bCode === $code)) {
                        $seat['status'] = 'booked';
                        break;
                    }
                }
                if ($seat['status'] === 'available') {
                    foreach ($heldRows as $h) {
                        $hCode = ($h['row_label'] ?? '') . ($h['seat_number'] ?? '');
                        if ((int) $h['seat_id'] === (int) $seat['id'] || ($hCode !== '' && $hCode === $code)) {
                            $seat['status'] = 'held';
                            $seat['is_my_hold'] = ($current_user !== null && (string) $h['user_id'] === (string) $current_user);
                            break;
                        }
                    }
                }
            }
            unset($seat);
        } else {
            foreach ($seats as &$seat) {
                $seat['is_my_hold'] = ($current_user !== null && (string) ($seat['held_by_user'] ?? '') === (string) $current_user);
                unset($seat['held_by_user']); // [BẢO MẬT 3] Xóa ID user thật để ẩn danh sách người đang giữ ghế
            }
            unset($seat);
        }

        echo json_encode(['success' => true, 'data' => $seats]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Không thể tải sơ đồ ghế.']);
    }
    exit;
}

// ===================================================================
// LUỒNG 2: GIỮ GHẾ (BẮT BUỘC ĐĂNG NHẬP)
// ===================================================================
elseif ($method === 'POST' && $action === 'hold') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Vui lòng đăng nhập để chọn ghế.']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $seat_ids = $data['seat_ids'] ?? [];
    $showtime_id = filter_var($data['showtime_id'] ?? null, FILTER_VALIDATE_INT);
    $user_id = $_SESSION['user_id'];

    if (!$showtime_id || !is_array($seat_ids) || empty($seat_ids)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dữ liệu giữ ghế không hợp lệ.']);
        exit;
    }

    // Ép mảng về số nguyên và tạo Placeholders
    $seat_ids = array_map('intval', $seat_ids);
    $placeholders = implode(',', array_fill(0, count($seat_ids), '?'));

    try {
        $pdo->beginTransaction();

        // [TỐI ƯU 2] Chống N+1 Query: Gộp check ghế đã bán vào 1 câu SQL duy nhất
        $checkBooked = $pdo->prepare("
            SELECT s.row_label, s.seat_number
            FROM booking_seats bs
            JOIN bookings b ON bs.booking_id = b.id
            JOIN seats s ON bs.seat_id = s.id
            WHERE bs.seat_id IN ($placeholders) AND b.showtime_id = ? AND b.status = 'confirmed'
        ");
        $checkBooked->execute(array_merge($seat_ids, [$showtime_id]));
        $bookedSeats = $checkBooked->fetchAll(PDO::FETCH_ASSOC);

        if (count($bookedSeats) > 0) {
            $pdo->rollBack();
            $names = array_map(fn($s) => $s['row_label'] . $s['seat_number'], $bookedSeats);
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Ghế ' . implode(', ', $names) . ' vừa bị người khác mua.']);
            exit;
        }

        // [BẢO MẬT 4] FOR UPDATE chống Race Condition khi kiểm tra ghế đang được ai đó giữ
        $checkHeld = $pdo->prepare("
            SELECT s.row_label, s.seat_number
            FROM seat_holds sh
            JOIN seats s ON sh.seat_id = s.id
            WHERE sh.seat_id IN ($placeholders) AND sh.showtime_id = ? AND sh.user_id != ?
            FOR UPDATE
        ");
        $checkHeld->execute(array_merge($seat_ids, [$showtime_id, $user_id]));
        $heldSeats = $checkHeld->fetchAll(PDO::FETCH_ASSOC);

        if (count($heldSeats) > 0) {
            $pdo->rollBack();
            $names = array_map(fn($s) => $s['row_label'] . $s['seat_number'], $heldSeats);
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'Ghế ' . implode(', ', $names) . ' đang được người khác chọn.']);
            exit;
        }

        // [TỐI ƯU 3] Bulk Insert: Gộp tất cả các thao tác giữ ghế vào 1 lần chèn duy nhất
        $insertValues = [];
        $insertParams = [];
        foreach ($seat_ids as $seat_id) {
            $insertValues[] = "(?, ?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE))";
            array_push($insertParams, $showtime_id, $seat_id, $user_id);
        }
        $insertSql = "INSERT INTO seat_holds (showtime_id, seat_id, user_id, expires_at) VALUES " . implode(', ', $insertValues) . " ON DUPLICATE KEY UPDATE expires_at = VALUES(expires_at)";

        $stmtInsert = $pdo->prepare($insertSql);
        $stmtInsert->execute($insertParams);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Giữ ghế thành công',
            'expires_in' => 300 // 5 phút
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Không thể giữ ghế.']);
    }
    exit;
}

// ===================================================================
// LUỒNG 3: NHẢ GHẾ (BẮT BUỘC ĐĂNG NHẬP)
// ===================================================================
elseif ($method === 'DELETE' && $action === 'release') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Chưa đăng nhập.']);
        exit;
    }

    $showtime_id = filter_input(INPUT_GET, 'showtime_id', FILTER_VALIDATE_INT);
    if ($showtime_id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM seat_holds WHERE showtime_id = ? AND user_id = ?");
            $stmt->execute([$showtime_id, $_SESSION['user_id']]);
            echo json_encode(['success' => true, 'message' => 'Đã giải phóng ghế.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Không thể nhả ghế.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Thiếu ID suất chiếu.']);
    }
    exit;
}

// Nếu gọi sai Endpoint
http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint không tồn tại.']);
?>