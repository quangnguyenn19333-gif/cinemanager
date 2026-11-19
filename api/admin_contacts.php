<?php
// Ngăn chặn hiển thị lỗi HTML ra ngoài làm hỏng JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Không có quyền truy cập']);
    exit;
}

$action = $_GET['action'] ?? 'list';
$method = $_SERVER['REQUEST_METHOD'];

if ($action === 'list') {
    try {
        $stmt = $pdo->query("SELECT * FROM contacts ORDER BY created_at DESC");
        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($contacts ? $contacts : []);
    } catch (PDOException $e) {
        echo json_encode([]);
    }
} elseif ($method === 'POST' && $action === 'reply') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $reply = $data['admin_reply'] ?? '';
    
    if ($id) {
        try {
            $stmt = $pdo->prepare("UPDATE contacts SET admin_reply = ?, status = 'resolved' WHERE id = ?");
            $stmt->execute([$reply, $id]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'ID không hợp lệ']);
    }
}
?>