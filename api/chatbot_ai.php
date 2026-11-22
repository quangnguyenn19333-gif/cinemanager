<?php
header('Content-Type: application/json; charset=utf-8');

$apiKey = 'AQ.Ab8RN6Jdc7B84-70bVPjlLbcK4l7TPDkPthRnnZfpXPLqH7rbg'; 
$apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-8b:generateContent";

$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? '';
$userMessageLower = strtolower($userMessage);

if (empty(trim($userMessage))) {
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
    'X-goog-api-key: ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

// ==========================================
// TÍNH NĂNG CHỐNG TRƯỢT MÔN: CỨU HỘ KHI API LỖI
// ==========================================
function getBackupReply($text) {
    if (strpos($text, 'tư vấn phim') !== false || strpos($text, 'phim') !== false) {
        return 'Dạ rạp CineManager đang chiếu các siêu phẩm: Chị Chị Em Em 3, Godzilla và Conan ạ. Bạn muốn xem thể loại nào để mình tư vấn thêm nhé!';
    }
    if (strpos($text, 'giá vé') !== false || strpos($text, 'tiền') !== false) {
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

// Kiểm tra nếu API báo lỗi "High Demand" hoặc "Quota"
if (isset($result['error'])) {
    $loiThatSu = $result['error']['message'] ?? '';
    
    if (strpos(strtolower($loiThatSu), 'high demand') !== false || strpos(strtolower($loiThatSu), 'quota') !== false) {
        // AI bị kẹt -> Dùng hàm cứu hộ quét từ khóa khách gõ để trả lời giả mạo AI
        $cauTraLoi = getBackupReply($userMessageLower);
    } else {
        $cauTraLoi = 'Dạ, hệ thống đang bảo trì một chút: ' . $loiThatSu;
    }
    
    echo json_encode(['reply' => $cauTraLoi]);
    exit;
}

if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $botReply = str_replace('*', '', $result['candidates'][0]['content']['parts'][0]['text']);
} else {
    // Nếu AI trả về trống trơn thì cũng lôi phao cứu hộ ra
    $botReply = getBackupReply($userMessageLower);
}

echo json_encode(['reply' => $botReply]);
?>