<?php
// [BẢO MẬT 1] Tắt hiển thị lỗi PHP ra ngoài làm hỏng JSON
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');

// Yêu cầu file cấu hình hoặc khai báo API Key ở đây (Nếu bạn lưu trong db.php thì mở comment dòng dưới)
// require_once 'db.php'; 
$apiKey = 'NHẬP_API_KEY_GEMINI_CỦA_BẠN_VÀO_ĐÂY'; // Nhớ thay key thật nhé!

$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-8b:generateContent";

// [BẢO MẬT 2] Chặn Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['reply' => 'Phương thức không được hỗ trợ.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// [BẢO MẬT 3] Lột sạch thẻ HTML/JS chống XSS ngay từ cửa
$userMessage = trim(strip_tags($data['message'] ?? ''));
$userMessageLower = strtolower($userMessage);

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Dạ bạn muốn hỏi gì về rạp chiếu phim CineManager ạ?']);
    exit;
}

// Lệnh "Tẩy não" AI
$prompt = "Bạn là nhân viên tư vấn của rạp chiếu phim CineManager. 
Quy tắc: Trả lời ngắn gọn dưới 40 chữ, thân thiện. 
Giá vé: 2D 85k, 3D 120k. Phim đang chiếu: Chị Chị Em Em 3, Godzilla, Conan. Bắp nước: 60k. 
Từ chối trả lời nếu ngoài lề.
Khách hỏi: " . $userMessage;

$payload = json_encode(["contents" => [["parts" => [["text" => $prompt]]]]]);

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-goog-api-key: ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
// [TỐI ƯU 1] Thêm Timeout (10 giây) để tránh treo Server nếu Google phản hồi chậm
curl_setopt($ch, CURLOPT_TIMEOUT, 10); 

$response = curl_exec($ch);
$curlError = curl_errno($ch); // Bắt lỗi mất mạng
curl_close($ch);

// ==========================================
// TÍNH NĂNG CHỐNG TRƯỢT MÔN: CỨU HỘ KHI API LỖI
// ==========================================
function getBackupReply($text) {
    if (strpos($text, 'tư vấn') !== false || strpos($text, 'phim') !== false) {
        return 'Dạ rạp CineManager đang chiếu các siêu phẩm: Chị Chị Em Em 3, Godzilla và Conan ạ. Bạn muốn xem thể loại nào để mình tư vấn thêm nhé!';
    }
    if (strpos($text, 'giá') !== false || strpos($text, 'tiền') !== false) {
        return 'Dạ vé 2D hiện tại là 85.000đ, còn vé 3D là 120.000đ ạ. Bạn đi mấy người để mình hỗ trợ chọn ghế đẹp nhé!';
    }
    if (strpos($text, 'bắp') !== false || strpos($text, 'nước') !== false || strpos($text, 'combo') !== false) {
        return 'Dạ combo 1 bắp 1 nước ngọt tại rạp đang có giá ưu đãi là 60.000đ ạ!';
    }
    if (strpos($text, 'chào') !== false || strpos($text, 'hi') !== false || strpos($text, 'alo') !== false) {
        return 'Dạ CineBot xin chào! Mình có thể giúp bạn giải đáp thông tin về giá vé, lịch chiếu và ưu đãi của rạp ạ.';
    }
    return 'Dạ hiện tại có rất nhiều khách đang nhờ tư vấn, bạn chờ mình xíu nhé, hoặc bạn có thể gọi hotline 1900 6017 để được hỗ trợ nhanh nhất ạ!';
}

// Nếu cURL lỗi do mất mạng hoặc Timeout
if ($curlError) {
    echo json_encode(['reply' => getBackupReply($userMessageLower)]);
    exit;
}

$result = json_decode($response, true);

// [BẢO MẬT 4] Che giấu thông báo lỗi từ Google, kích hoạt phao cứu hộ ngay
if (isset($result['error'])) {
    echo json_encode(['reply' => getBackupReply($userMessageLower)]);
    exit;
}

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $botReply = str_replace('*', '', $result['candidates'][0]['content']['parts'][0]['text']);
} else {
    // Nếu AI trả về trống trơn thì lôi phao cứu hộ ra
    $botReply = getBackupReply($userMessageLower);
}

echo json_encode(['reply' => $botReply]);
?>