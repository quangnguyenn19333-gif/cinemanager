<?php
header('Content-Type: application/json');
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Vui lòng đăng nhập để đổi quà']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->prepare("SELECT * FROM reward_redemptions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $reward_name = trim($data['reward_name'] ?? '');
    $points_needed = (int) ($data['points'] ?? 0);

    if (empty($reward_name) || $points_needed <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Thông tin quà tặng không hợp lệ']);
        exit;
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("SELECT points FROM users WHERE id = ? FOR UPDATE");
        $stmt->execute([$user_id]);
        $current_points = (int) $stmt->fetchColumn();

        if ($current_points < $points_needed) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(['error' => 'Bạn không đủ điểm để đổi quà này!']);
            exit;
        }

        $new_points = $current_points - $points_needed;
        $stmtUpd = $pdo->prepare("UPDATE users SET points = ? WHERE id = ?");
        $stmtUpd->execute([$new_points, $user_id]);


        $voucher_code = 'GIFT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        $stmtIns = $pdo->prepare("INSERT INTO reward_redemptions (user_id, reward_name, points_spent, voucher_code) VALUES (?, ?, ?, ?)");
        $stmtIns->execute([$user_id, $reward_name, $points_needed, $voucher_code]);

        $pdo->commit();
        echo json_encode([
            'message' => 'Đổi quà thành công! Mã nhận quà của bạn là: ' . $voucher_code,
            'voucher_code' => $voucher_code,
            'new_points' => $new_points
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
}
?>