<?php
// Ngăn chặn hiển thị lỗi HTML ra ngoài làm hỏng JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

// [BẢO MẬT 1] Bắt buộc khởi tạo session trước khi kiểm tra quyền
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// [BẢO MẬT 2] Khóa bảo vệ dành riêng cho Admin
function checkAdminAuth() {
    if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Từ chối truy cập: Chỉ Quản trị viên mới được thao tác!']);
        exit;
    }
}

// Khóa cửa toàn bộ file này bằng checkAdminAuth
checkAdminAuth();

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

// ===================================================================
// LUỒNG 1: LẤY DANH SÁCH GÓP Ý
// ===================================================================
if ($method === 'GET' && $action === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC");
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($contacts ?: []);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Lỗi truy xuất dữ liệu hệ thống.']);
    }
    exit;
} 

// ===================================================================
// LUỒNG 2: ADMIN TRẢ LỜI GÓP Ý VÀ ĐÁNH DẤU HOÀN TẤT
// ===================================================================
elseif ($method === 'POST' && $action === 'reply') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // [BẢO MẬT 3] Ép kiểu ID và dọn dẹp khoảng trắng nội dung
    $id = filter_var($data['id'] ?? 0, FILTER_VALIDATE_INT);
    $reply = trim($data['admin_reply'] ?? '');
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID góp ý không hợp lệ.']);
        exit;
    }

    // Chặn gửi câu trả lời rỗng
    if (empty($reply)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Nội dung trả lời không được để trống.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE contacts SET admin_reply = ?, status = 'resolved' WHERE id = ?");
        $stmt->execute([$reply, $id]);
        echo json_encode(['success' => true, 'message' => 'Đã gửi phản hồi thành công.']);
    } catch (PDOException $e) {
        http_response_code(500);
        // [BẢO MẬT 4] Che giấu lỗi SQL thực tế
        echo json_encode(['success' => false, 'error' => 'Lỗi hệ thống: Không thể lưu câu trả lời lúc này.']);
    }
    exit;
}

// Nếu gọi sai Endpoint
http_response_code(404);
echo json_encode(['success' => false, 'error' => 'Endpoint không tồn tại.']);
?>