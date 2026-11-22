<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// 1. Khách hàng gửi hồ sơ ứng tuyển
if ($method === 'POST' && $action === 'submit') {
    $data = json_decode(file_get_contents('php://input'), true);

    $position = trim($data['position'] ?? '');
    $fullName = trim($data['full_name'] ?? '');
    $email    = trim($data['email'] ?? '');
    $phone    = trim($data['phone'] ?? '');
    $cvLink   = trim($data['cv_link'] ?? '');
    $message  = trim($data['message'] ?? '');

    if (empty($position) || empty($fullName) || empty($email) || empty($phone)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Vui lòng nhập đầy đủ thông tin bắt buộc']);
        exit;
    }

    // Kiểm tra định dạng Link CV phải bắt đầu bằng http:// hoặc https://
    if (!empty($cvLink) && !preg_match('/^https?:\/\/.+/i', $cvLink)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Link CV phải bắt đầu bằng http:// hoặc https://']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO job_applications (position, full_name, email, phone, cv_link, message) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$position, $fullName, $email, $phone, $cvLink, $message]);
        echo json_encode(['success' => true, 'message' => 'Đã gửi hồ sơ thành công!']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
    exit;
}

// Các thao tác bên dưới yêu cầu quyền Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Không có quyền truy cập']);
    exit;
}

// 2. Admin lấy danh sách hồ sơ
if ($method === 'GET' && ($action === 'list' || $action === '')) {
    try {
        $stmt = $pdo->query("SELECT * FROM job_applications ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        echo json_encode([]);
    }
    exit;
}

// 3. Admin cập nhật trạng thái hồ sơ
if ($method === 'POST' && $action === 'update_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    $status = $data['status'] ?? '';

    if (!$id || !in_array($status, ['pending', 'contacted', 'rejected'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dữ liệu không hợp lệ']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE job_applications SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>