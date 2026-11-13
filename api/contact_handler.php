<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $fullName = $data['full_name'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $subject = $data['subject'] ?? 'Góp ý dịch vụ';
    $message = $data['message'] ?? '';
    
    // Lấy user_id nếu khách đã đăng nhập
    $userId = $_SESSION['user_id'] ?? null;

    if (empty($fullName) || empty($email) || empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Vui lòng điền đầy đủ thông tin bắt buộc']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO contacts (user_id, full_name, email, phone, subject, message, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$userId, $fullName, $email, $phone, $subject, $message]);
        
        echo json_encode(['success' => true, 'message' => 'Gửi góp ý thành công']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Lỗi database: ' . $e->getMessage()]);
    }
}
?>