<?php
header('Content-Type: application/json');
require_once 'db.php';

// [BẢO MẬT 1] Bắt buộc phải khởi tạo session mới đọc được quyền của user
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$method = $_SERVER['REQUEST_METHOD'];

// [BẢO MẬT 2] Nâng cấp hàm checkAuth thành checkAdminAuth: Khóa chặt chỉ cho Admin
function checkAdminAuth() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Từ chối truy cập! Chỉ Quản trị viên (Admin) mới có quyền thực hiện hành động này.']);
        exit; // Lệnh exit chặn đứng luồng chạy, kẻ gian không thể can thiệp DB
    }
}

// Đảm bảo bảng movies có cột status để lưu trạng thái
function ensureStatusColumn(PDO $pdo): void {
    static $checked = false;
    if ($checked) {
        return;
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM movies LIKE 'status'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE movies ADD COLUMN status VARCHAR(20) NULL");
    }
    $checked = true;
}

switch ($method) {
    case 'GET':
        // GET: Mở cửa cho mọi người (kể cả chưa đăng nhập) để hiển thị phim ra trang chủ cho khách xem
        $stmt = $pdo->query("SELECT * FROM movies ORDER BY (title = 'Chị Chị Em Em 3') DESC, id DESC");
        $movies = $stmt->fetchAll();
        echo json_encode($movies);
        break;

    case 'POST':
        checkAdminAuth(); // [Ổ KHÓA]: Chặn người dùng thường thêm phim
        ensureStatusColumn($pdo);
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['title']) && trim($data['title']) !== '') {
            $statusVal = (isset($data['status']) && $data['status'] !== '' && $data['status'] !== null && $data['status'] !== 'auto')
                ? (string)$data['status']
                : null;
                
            $sql = "INSERT INTO movies (title, genre, duration, release_date, poster_url, trailer_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                trim($data['title']),
                trim($data['genre'] ?? ''),
                (int)($data['duration'] ?? 0),
                $data['release_date'] ?? date('Y-m-d'),
                trim($data['poster_url'] ?? ''),
                trim($data['trailer_url'] ?? ''),
                $statusVal
            ]);
            echo json_encode(['message' => 'Thêm phim thành công', 'id' => $pdo->lastInsertId()]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Tên phim không được để trống']);
        }
        break;

    case 'PUT':
        checkAdminAuth(); // [Ổ KHÓA]: Chặn người dùng thường sửa phim
        ensureStatusColumn($pdo);
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $_GET['id'] ?? ($data['id'] ?? null);
        
        if ($id && isset($data['title']) && trim($data['title']) !== '') {
            $statusVal = (array_key_exists('status', $data) && $data['status'] !== '' && $data['status'] !== null && $data['status'] !== 'auto')
                ? (string)$data['status']
                : null;
                
            $sql = "UPDATE movies SET title=?, genre=?, duration=?, release_date=?, poster_url=?, trailer_url=?, status=? WHERE id=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                trim($data['title']),
                trim($data['genre'] ?? ''),
                (int)($data['duration'] ?? 0),
                $data['release_date'] ?? date('Y-m-d'),
                trim($data['poster_url'] ?? ''),
                trim($data['trailer_url'] ?? ''),
                $statusVal,
                $id
            ]);
            echo json_encode(['message' => 'Cập nhật phim thành công']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu ID phim hoặc tên phim']);
        }
        break;

    case 'DELETE':
        checkAdminAuth(); // [Ổ KHÓA]: Chặn người dùng thường xóa phim
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['message' => 'Xóa phim thành công']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Thiếu ID phim cần xóa']);
        }
        break;
}
?>