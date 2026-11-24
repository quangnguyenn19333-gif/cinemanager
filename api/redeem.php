<?php
// [BẢO MẬT 1] Chặn hiển thị lỗi HTML ra ngoài làm hỏng cấu trúc JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// Khởi tạo session an toàn
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Vui lòng đăng nhập để đổi quà.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// ===================================================================
// LUỒNG 1: XEM LỊCH SỬ ĐỔI QUÀ
// ===================================================================
if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM reward_redemptions WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        echo json_encode([
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Không thể tải lịch sử đổi quà.']);
    }
    exit;
}

// ===================================================================
// LUỒNG 2: XỬ LÝ ĐỔI QUÀ
// ===================================================================
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // [BẢO MẬT 2] Lột sạch thẻ HTML để chống XSS trên tên quà
    $reward_name = trim(strip_tags($data['reward_name'] ?? ''));
    
    // Ép chặt kiểu số nguyên để chống hack
    $points_needed = filter_var($data['points'] ?? 0, FILTER_VALIDATE_INT);

    if (empty($reward_name) || $points_needed === false || $points_needed <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Thông tin quà tặng hoặc số điểm không hợp lệ.']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        // [TỐI ƯU KẾ THỪA] Rất tốt: Dùng FOR UPDATE để chống Race Condition
        $stmt = $pdo->prepare("SELECT points FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $current_points = (int) $stmt->fetchColumn();

        if ($current_points < $points_needed) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Bạn không đủ điểm khả dụng để đổi quà này!']);
            exit;
        }

        // Trừ điểm
        $new_points = $current_points - $points_needed;
        $stmtUpd = $pdo->prepare("UPDATE users SET points = ? WHERE id = ?");
        $stmtUpd->execute([$new_points, $user_id]);

        // Tạo mã quà và lưu lịch sử
        $voucher_code = 'GIFT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        $stmtIns = $pdo->prepare("INSERT INTO reward_redemptions (user_id, reward_name, points_spent, voucher_code) VALUES (?, ?, ?, ?)");
        $stmtIns->execute([$user_id, $reward_name, $points_needed, $voucher_code]);

        $pdo->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Đổi quà thành công! Mã nhận quà của bạn là: ' . $voucher_code,
            'voucher_code' => $voucher_code,
            'new_points' => $new_points
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        // [BẢO MẬT 3] Che giấu cấu trúc DB
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Không thể xử lý giao dịch đổi quà lúc này.']);
    }
}
?>