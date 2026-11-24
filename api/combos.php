<?php
// [BẢO MẬT 1] Chặn hiển thị lỗi HTML ra ngoài làm hỏng cấu trúc JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

// [BẢO MẬT 2] Khóa chặt API: Chỉ cho phép lấy dữ liệu (GET), chặn mọi nỗ lực POST/PUT/DELETE
if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Phương thức không được hỗ trợ.']);
    exit;
}

// Danh sách combo bắp nước cố định
$combos = [
    ['id' => 1, 'name' => 'Combo 1 người', 'description' => '1 Bắp vừa + 1 Nước ngọt 400ml', 'price' => 75000, 'icon' => '🍿'],
    ['id' => 2, 'name' => 'Combo 2 người', 'description' => '2 Bắp vừa + 2 Nước ngọt 400ml', 'price' => 139000, 'icon' => '🍿🥤'],
    ['id' => 3, 'name' => 'Combo VIP', 'description' => '1 Bắp lớn + 2 Nước lớn + 1 Snack', 'price' => 165000, 'icon' => '👑'],
    ['id' => 4, 'name' => 'Chỉ bắp', 'description' => '1 Bắp lớn không nước', 'price' => 55000, 'icon' => '🌽'],
    ['id' => 5, 'name' => 'Chỉ nước', 'description' => '2 Nước ngọt 500ml', 'price' => 40000, 'icon' => '🥤'],
];

// Trả về trực tiếp mảng để Frontend hoạt động bình thường
echo json_encode($combos);
?>