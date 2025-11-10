<?php
// PHẦN C: MÃ PHP PROXY (Cần upload file này lên Server)
// Key AI của bạn được bảo vệ ở đây.

// --- BƯỚC 1: ĐẶT KEY AI BÍ MẬT VÀO ĐÂY ---
$API_KEY = "AIzaSyCV-q8Xv1anjYGp6wUbXDz5gWwXU2EvigE"; 

// --- BƯỚC 2: Cấu hình và Thiết lập bảo mật CORS ---
header('Content-Type: application/json');

// Bảo mật CORS: Chỉ cho phép truy cập từ tên miền của bạn (ví dụ: https://ten-mien-cua-ban.com)
// Nếu bạn đang thử nghiệm, có thể dùng "*" nhưng KHÔNG NÊN làm thế trên môi trường production.
header("Access-Control-Allow-Origin: *"); // Thay thế bằng tên miền của bạn
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Xử lý yêu cầu OPTIONS (kiểm tra kết nối)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Chỉ chấp nhận phương thức POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Chỉ chấp nhận phương thức POST']);
    exit();
}

// Lấy dữ liệu từ client
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Kiểm tra dữ liệu bắt buộc từ Client
if (!isset($data['contents'], $data['systemInstruction'], $data['model'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Dữ liệu đầu vào không hợp lệ từ Client.']);
    exit();
}

$contents = $data['contents'];
$systemInstruction = $data['systemInstruction'];
$model = $data['model'];

// --- BƯỚC 3: Tạo Payload API Gemini ---
$api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$API_KEY}";

$payload = [
    'contents' => $contents,
    'tools' => [['google_search' => new stdClass()]],
    'systemInstruction' => [
        'parts' => [['text' => $systemInstruction]]
    ]
];

// --- BƯỚC 4: Gọi API của Google Gemini ---
$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200) {
    // Trả về lỗi nếu API Gemini không phản hồi thành công
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi khi gọi API Gemini', 'details' => $response]);
    exit();
}

$result = json_decode($response, true);
$aiResponseText = "Xin lỗi, không có phản hồi được tạo.";

// Lấy nội dung phản hồi
if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
    $aiResponseText = $result['candidates'][0]['content']['parts'][0]['text'];
} else if (isset($result['error'])) {
    $aiResponseText = "Lỗi từ Gemini: " . $result['error']['message'];
}

// Trả về kết quả cho trình duyệt dưới dạng JSON
echo json_encode(['text' => $aiResponseText]);
?>