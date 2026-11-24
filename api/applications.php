<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// [BẢO MẬT 1] Bắt buộc phải khởi tạo session mới check được quyền Admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Hàm khóa bảo vệ dành riêng cho Admin
function checkAdminAuth() {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Từ chối truy cập: Chỉ Quản trị viên mới được thao tác!']);
        exit;
    }
}

// ===================================================================
// LUỒNG 1: KHÁCH VÃNG LAI NỘP HỒ SƠ (Không yêu cầu đăng nhập)
// ===================================================================
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

    // [BẢO MẬT 2] Kiểm tra Email bằng Filter chuẩn của PHP
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Định dạng Email không hợp lệ']);
        exit;
    }

    // [BẢO MẬT 3] Kiểm tra URL bằng Filter chuẩn thay vì Regex cơ bản
    if (!empty($cvLink) && !filter_var($cvLink, FILTER_VALIDATE_URL)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Link CV không hợp lệ (phải bắt đầu bằng http:// hoặc https://)']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO job_applications (position, full_name, email, phone, cv_link, message) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$position, $fullName, $email, $phone, $cvLink, $message]);
        echo json_encode(['success' => true, 'message' => 'Đã gửi hồ sơ thành công!']);
    } catch (PDOException $e) {
        http_response_code(500);
        // [BẢO MẬT 4] Che thông báo lỗi thật, tránh lộ cấu trúc Database
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Không thể gửi hồ sơ lúc này.']);
    }
    exit; // Dừng luồng tại đây, không chạy xuống phần Admin
}


// ===================================================================
// CÁC LUỒNG DƯỚI ĐÂY LÀ KHU VỰC CẤM: CHỈ DÀNH CHO ADMIN
// ===================================================================
checkAdminAuth();

// LUỒNG 2: ADMIN LẤY DANH SÁCH HỒ SƠ
if ($method === 'GET' && ($action === 'list' || $action === '')) {
    try {
        $stmt = $pdo->query("SELECT * FROM job_applications ORDER BY created_at DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Lỗi truy xuất dữ liệu hồ sơ']);
    }
    exit;
}

// LUỒNG 3: ADMIN DUYỆT / CẬP NHẬT TRẠNG THÁI
if ($method === 'POST' && $action === 'update_status') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Ép kiểu ID về số nguyên để chống Injection
    $id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
    $status = $data['status'] ?? '';

    // Khóa chặt trạng thái, chỉ nhận 3 chữ này
    if (!$id || !in_array($status, ['pending', 'contacted', 'rejected'], true)) {
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
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Không thể cập nhật trạng thái']);
    }
    exit;
}

// Trả về 404 nếu gọi sai Endpoint
http_response_code(404);
echo json_encode(['error' => 'Endpoint không tồn tại']);
?>