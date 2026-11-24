<?php
// Ngăn chặn hiển thị lỗi HTML ra ngoài làm hỏng JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// [BẢO MẬT 1] Bắt buộc khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];

// [BẢO MẬT 2] Khóa chặt: Yêu cầu đăng nhập cho tất cả các luồng trong file này
if (!isset($_SESSION['user_id'])) { 
    http_response_code(401); 
    echo json_encode(['success' => false, 'error' => 'Vui lòng đăng nhập để tiếp tục.']); 
    exit; 
}
$user_id = $_SESSION['user_id'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $showtime_id = filter_var($data['showtime_id'] ?? 0, FILTER_VALIDATE_INT);
    $seat_ids = $data['seat_ids'] ?? [];
    $combos = $data['combos'] ?? [];

    if (!$showtime_id || !is_array($seat_ids) || empty($seat_ids)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dữ liệu đặt vé không hợp lệ (thiếu suất chiếu hoặc chưa chọn ghế).']);
        exit;
    }

    $seat_ids = array_map('intval', $seat_ids);
    $placeholders = implode(',', array_fill(0, count($seat_ids), '?'));
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM seat_holds WHERE showtime_id = ? AND user_id = ? AND seat_id IN ($placeholders) AND expires_at > NOW()");
    $stmt->execute(array_merge([$showtime_id, $user_id], $seat_ids));
    if ((int)$stmt->fetchColumn() !== count($seat_ids)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Ghế giữ đã hết hạn hoặc không hợp lệ. Vui lòng tải lại trang và chọn lại.']);
        exit;
    }

    $stmtShow = $pdo->prepare("SELECT price_standard, price_vip, price_couple FROM showtimes WHERE id = ?");
    $stmtShow->execute([$showtime_id]);
    $prices = $stmtShow->fetch(PDO::FETCH_ASSOC);
    
    if (!$prices) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Suất chiếu không tồn tại.']);
        exit;
    }

    $stmtSeats = $pdo->prepare("SELECT id, seat_type FROM seats WHERE id IN ($placeholders)");
    $stmtSeats->execute($seat_ids);
    $seatsData = $stmtSeats->fetchAll(PDO::FETCH_KEY_PAIR);

    $total_seat_price = 0;
    $seat_details = [];
    foreach ($seat_ids as $seat_id) {
        $seat_type = $seatsData[$seat_id] ?? 'standard';
        $price = match($seat_type) {
            'vip' => (int)$prices['price_vip'],
            'couple' => (int)$prices['price_couple'],
            default => (int)$prices['price_standard'],
        };
        $total_seat_price += $price;
        $seat_details[] = ['seat_id' => $seat_id, 'price' => $price];
    }

    $combo_price = 0;
    $valid_combos = [];
    if (!empty($combos) && is_array($combos)) {
        foreach ($combos as $combo) {
            $qty = max(1, (int)($combo['qty'] ?? 1));
            $price = max(0, (int)($combo['price'] ?? 0)); 
            
            $combo_price += ($price * $qty);
            $valid_combos[] = [
                'id' => $combo['id'] ?? null,
                'name' => trim($combo['name'] ?? 'Combo'),
                'price' => $price,
                'qty' => $qty
            ];
        }
    }

    $total_price = $total_seat_price + $combo_price;

    $pdo->beginTransaction();
    try {
        try {
            $stmtBook = $pdo->prepare("INSERT INTO bookings (user_id, showtime_id, combo_json, total_price, status) VALUES (?, ?, ?, ?, 'confirmed')");
            $stmtBook->execute([$user_id, $showtime_id, json_encode($valid_combos, JSON_UNESCAPED_UNICODE), $total_price]);
        } catch (PDOException $e) {
            $stmtBook = $pdo->prepare("INSERT INTO bookings (user_id, showtime_id, combo_json, total_price) VALUES (?, ?, ?, ?)");
            $stmtBook->execute([$user_id, $showtime_id, json_encode($valid_combos, JSON_UNESCAPED_UNICODE), $total_price]);
        }
        $booking_id = $pdo->lastInsertId();

        $stmtSeat = $pdo->prepare("INSERT INTO booking_seats (booking_id, seat_id, seat_price) VALUES (?, ?, ?)");
        foreach ($seat_details as $sd) {
            $stmtSeat->execute([$booking_id, $sd['seat_id'], $sd['price']]);
        }

        $stmtDel = $pdo->prepare("DELETE FROM seat_holds WHERE showtime_id = ? AND user_id = ?");
        $stmtDel->execute([$showtime_id, $user_id]);

        $points_earned = cm_calc_points($total_price);
        $stmtPts = $pdo->prepare("UPDATE users SET points = COALESCE(points, 0) + ? WHERE id = ?");
        $stmtPts->execute([$points_earned, $user_id]);

        $stmtGet = $pdo->prepare("SELECT points FROM users WHERE id = ?");
        $stmtGet->execute([$user_id]);
        $new_points = (int) $stmtGet->fetchColumn();

        $stmtSpent = $pdo->prepare("SELECT COALESCE(SUM(points_spent), 0) FROM reward_redemptions WHERE user_id = ?");
        $stmtSpent->execute([$user_id]);
        $tier_points = $new_points + (int) $stmtSpent->fetchColumn();

        $new_level = cm_member_level($tier_points);

        $stmtLevel = $pdo->prepare("UPDATE users SET member_level = ? WHERE id = ?");
        $stmtLevel->execute([$new_level, $user_id]);

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Đặt vé thành công!',
            'booking_id' => $booking_id,
            'total_price' => $total_price,
            'points_earned' => $points_earned,
            'points' => $new_points,
            'tier_points' => $tier_points,
            'member_level' => $new_level
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Không thể hoàn tất quá trình đặt vé.']);
    }
    exit;

} elseif ($method === 'GET') {
    try {
        $stmt = $pdo->prepare("
            SELECT b.*, m.title AS movie_title, m.poster_url,
                   r.name AS room_name, st.show_date, st.show_time,
                   GROUP_CONCAT(s.row_label, s.seat_number ORDER BY s.row_label, s.seat_number SEPARATOR ', ') AS seats
            FROM bookings b
            JOIN showtimes st ON b.showtime_id = st.id
            JOIN movies m ON st.movie_id = m.id
            JOIN rooms r ON st.room_id = r.id
            LEFT JOIN booking_seats bs ON bs.booking_id = b.id
            LEFT JOIN seats s ON bs.seat_id = s.id
            WHERE b.user_id = ?
            GROUP BY b.id
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống khi tải lịch sử đặt vé.']);
    }
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Endpoint không tồn tại.']);
?>