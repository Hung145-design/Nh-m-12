<?php
// Tăng thời gian thực thi tối đa lên 60 giây
set_time_limit(60);
ini_set('max_execution_time', 60);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// API Key Google Gemini
define('GEMINI_API_KEY', 'AIzaSyC5HxTSxyXPS203yNCA4kc0Oc0nAdq0UGQ');

// Mapping tên ngôn ngữ
$languageNames = [
    'vi' => 'Tiếng Việt',
    'en' => 'English',
    'zh' => 'Chinese',
    'ja' => 'Japanese',
    'ko' => 'Korean',
    'fr' => 'French',
    'de' => 'German',
    'es' => 'Spanish',
    'ru' => 'Russian',
    'th' => 'Thai',
    'ar' => 'Arabic',
    'hi' => 'Hindi',
    'pt' => 'Portuguese',
    'it' => 'Italian',
    'nl' => 'Dutch',
    'pl' => 'Polish',
    'tr' => 'Turkish',
    'id' => 'Indonesian',
    'ms' => 'Malay',
    'sv' => 'Swedish',
    'da' => 'Danish',
    'no' => 'Norwegian',
    'fi' => 'Finnish',
    'cs' => 'Czech',
    'hu' => 'Hungarian',
    'ro' => 'Romanian',
    'bg' => 'Bulgarian',
    'uk' => 'Ukrainian',
    'el' => 'Greek',
    'he' => 'Hebrew',
    'fa' => 'Persian',
    'ur' => 'Urdu',
    'bn' => 'Bengali',
    'ta' => 'Tamil',
    'te' => 'Telugu',
    'mr' => 'Marathi',
    'sw' => 'Swahili',
    'zu' => 'Zulu',
    'af' => 'Afrikaans',
    'ca' => 'Catalan',
    'eu' => 'Basque',
    'gl' => 'Galician',
    'hr' => 'Croatian',
    'sr' => 'Serbian',
    'sk' => 'Slovak',
    'sl' => 'Slovenian',
    'lt' => 'Lithuanian',
    'lv' => 'Latvian',
    'et' => 'Estonian',
    'is' => 'Icelandic',
    'ga' => 'Irish',
    'cy' => 'Welsh',
    'mt' => 'Maltese',
    'km' => 'Khmer',
    'my' => 'Myanmar',
    'lo' => 'Lao',
    'ne' => 'Nepali',
    'si' => 'Sinhala',
    'ml' => 'Malayalam',
    'kn' => 'Kannada',
    'gu' => 'Gujarati',
    'pa' => 'Punjabi',
    'or' => 'Odia',
    'as' => 'Assamese',
    'ha' => 'Hausa',
    'yo' => 'Yoruba',
    'ig' => 'Igbo',
    'am' => 'Amharic',
    'so' => 'Somali',
    'mg' => 'Malagasy',
    'jw' => 'Javanese',
    'su' => 'Sundanese',
    'ceb' => 'Cebuano',
    'tl' => 'Tagalog',
    'haw' => 'Hawaiian',
    'mi' => 'Maori',
    'ka' => 'Georgian',
    'hy' => 'Armenian',
    'az' => 'Azerbaijani',
    'kk' => 'Kazakh',
    'ky' => 'Kyrgyz',
    'uz' => 'Uzbek',
    'mn' => 'Mongolian',
    'be' => 'Belarusian',
    'mk' => 'Macedonian',
    'sq' => 'Albanian',
    'bs' => 'Bosnian',
    'lb' => 'Luxembourgish',
    'gd' => 'Scottish Gaelic',
    'br' => 'Breton',
    'co' => 'Corsican',
    'fy' => 'Frisian',
    'yi' => 'Yiddish',
    'ku' => 'Kurdish',
    'ps' => 'Pashto',
    'sd' => 'Sindhi',
    'auto' => 'auto-detect'
];

// Hàm lấy tên ngôn ngữ
function getLanguageName($code, $languageNames) {
    return isset($languageNames[$code]) ? $languageNames[$code] : $code;
}

// Hàm phát hiện ngôn ngữ bằng Gemini
function detectLanguage($text, $apiKey) {
    $prompt = "Chỉ trả về mã ngôn ngữ ISO 639-1 (ví dụ: vi, en, zh, ja, ko, fr, de, es, ru, th) của văn bản sau. Chỉ trả về mã ngôn ngữ, không có giải thích:\n\n" . $text;
    
    // Sử dụng model gemini-2.0-flash với header X-goog-api-key
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent';
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-goog-api-key: ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20); // Timeout 20 giây cho detect language
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Timeout kết nối 5 giây
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return 'auto';
    }
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            $detected = trim(strtolower($result['candidates'][0]['content']['parts'][0]['text']));
            // Lấy mã ngôn ngữ từ kết quả (có thể có format khác)
            if (strlen($detected) <= 5) {
                return $detected;
            }
        }
    }
    
    return 'auto';
}

// Hàm dịch văn bản bằng Gemini AI với retry logic
function translateText($text, $sourceLang, $targetLang, $languageNames, $retryCount = 0) {
    $apiKey = GEMINI_API_KEY;
    $maxRetries = 1; // Giảm số lần retry để tránh timeout
    $retryDelay = 1; // Giảm delay xuống 1 giây
    
    // Tạm thời tắt auto-detect để giảm số request (tránh rate limit)
    // Nếu sourceLang là auto, không gọi detectLanguage để tránh tăng số request
    if ($sourceLang === 'auto') {
        $sourceLangName = 'ngôn ngữ nguồn';
    } else {
        $sourceLangName = getLanguageName($sourceLang, $languageNames);
    }
    
    $targetLangName = getLanguageName($targetLang, $languageNames);
    
    // Tạo prompt tối ưu - ngắn gọn và rõ ràng
    if ($sourceLang === 'auto') {
        $prompt = "Translate to {$targetLangName} with pronunciation:\n\n{$text}\n\nReturn JSON only:\n{\"translation\":\"text\",\"pronunciation\":\"pron\"}";
    } else {
        $prompt = "Translate {$sourceLangName} to {$targetLangName} with pronunciation:\n\n{$text}\n\nReturn JSON only:\n{\"translation\":\"text\",\"pronunciation\":\"pron\"}";
    }
    
    // Sử dụng đa luồng với nhiều model để lấy kết quả nhanh nhất
    $models = ['gemini-2.0-flash', 'gemini-pro']; // Thử 2 model song song
    
    // Chuẩn bị dữ liệu
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.2, // Giảm temperature để nhanh hơn
            'topK' => 1,
            'topP' => 0.8,
            'maxOutputTokens' => 1024 // Giảm tokens để nhanh hơn
        ]
    ];
    
    // Gọi nhiều model song song bằng curl_multi
    $mh = curl_multi_init();
    $handles = [];
    $responses = [];
    
    foreach ($models as $model) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-goog-api-key: ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Timeout 30 giây cho mỗi request
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        curl_multi_add_handle($mh, $ch);
        $handles[$model] = $ch;
    }
    
    // Thực thi các request song song
    $running = null;
    $startTime = microtime(true);
    $maxWaitTime = 35; // Tối đa 35 giây
    
    do {
        curl_multi_exec($mh, $running);
        curl_multi_select($mh, 0.1); // Chờ 100ms
        
        // Kiểm tra timeout
        if (microtime(true) - $startTime > $maxWaitTime) {
            break;
        }
        
        // Kiểm tra xem có request nào đã hoàn thành chưa
        while ($info = curl_multi_info_read($mh)) {
            if ($info['msg'] == CURLMSG_DONE) {
                $handle = $info['handle'];
                $model = array_search($handle, $handles);
                
                if ($model !== false) {
                    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                    $response = curl_multi_getcontent($handle);
                    $error = curl_error($handle);
                    
                    if ($httpCode === 200 && !$error) {
                        // Lấy kết quả đầu tiên thành công
                        $responses[$model] = [
                            'response' => $response,
                            'httpCode' => $httpCode
                        ];
                        
                        // Hủy các request còn lại
                        foreach ($handles as $m => $h) {
                            if ($m !== $model) {
                                curl_multi_remove_handle($mh, $h);
                                curl_close($h);
                            }
                        }
                        
                        curl_multi_close($mh);
                        
                        // Thoát khỏi vòng lặp do-while
                        $running = 0;
                        break 2; // Break cả 2 vòng lặp (while và do-while)
                    }
                }
            }
        }
    } while ($running > 0);
    
    // Nếu không có response nào thành công trong vòng lặp, lấy response đầu tiên
    if (empty($responses)) {
        // Đóng tất cả handles
        foreach ($handles as $handle) {
            curl_multi_remove_handle($mh, $handle);
            curl_close($handle);
        }
        curl_multi_close($mh);
        
        // Nếu không có response nào, throw error
        throw new Exception('Tất cả các model đều không phản hồi');
    } else {
        // Lấy response đầu tiên thành công
        $firstModel = array_key_first($responses);
        $response = $responses[$firstModel]['response'];
        $httpCode = $responses[$firstModel]['httpCode'];
        $error = '';
        
        // Đóng tất cả handles còn lại
        foreach ($handles as $m => $handle) {
            if ($m !== $firstModel) {
                curl_multi_remove_handle($mh, $handle);
                curl_close($handle);
            }
        }
        curl_multi_close($mh);
    }
    
    if ($error) {
        throw new Exception('Lỗi kết nối: ' . $error);
    }
    
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = 'Lỗi API: HTTP ' . $httpCode;
        $isRateLimit = false;
        
        if (isset($errorData['error']['message'])) {
            $errorMessage = $errorData['error']['message'];
            
            // Kiểm tra nếu là lỗi rate limit
            if (strpos($errorMessage, 'Resource exhausted') !== false || 
                strpos($errorMessage, '429') !== false ||
                strpos($errorMessage, 'quota') !== false ||
                $httpCode == 429) {
                $isRateLimit = true;
                
                // Thử lại nếu chưa vượt quá số lần retry
                if ($retryCount < $maxRetries) {
                    sleep($retryDelay * ($retryCount + 1)); // Exponential backoff
                    return translateText($text, $sourceLang, $targetLang, $languageNames, $retryCount + 1);
                }
                
                $errorMessage = 'API đã đạt giới hạn (rate limit). Vui lòng đợi vài phút rồi thử lại.';
            }
        } elseif (isset($errorData['error']['status'])) {
            $errorMessage = $errorData['error']['status'];
        } elseif (!empty($response)) {
            // Nếu không parse được JSON, trả về response thô
            $errorMessage = 'Lỗi từ Gemini API: ' . substr($response, 0, 200);
        }
        
        throw new Exception($errorMessage);
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Lỗi parse JSON từ Gemini API: ' . json_last_error_msg());
    }
    
    if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
        $responseText = trim($result['candidates'][0]['content']['parts'][0]['text']);
        
        // Loại bỏ các ký tự không mong muốn (markdown code blocks, etc)
        $responseText = preg_replace('/^```json\s*/', '', $responseText);
        $responseText = preg_replace('/\s*```$/', '', $responseText);
        $responseText = trim($responseText);
        
        // Thử parse JSON từ response
        $parsedResult = json_decode($responseText, true);
        
        if ($parsedResult && isset($parsedResult['translation'])) {
            // Response là JSON hợp lệ với translation và pronunciation
            return [
                'translation' => $parsedResult['translation'],
                'pronunciation' => isset($parsedResult['pronunciation']) ? $parsedResult['pronunciation'] : ''
            ];
        } else {
            // Response không phải JSON, có thể chỉ là text thuần
            // Thử extract JSON từ text (hỗ trợ nested JSON)
            if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/', $responseText, $matches)) {
                $jsonMatch = json_decode($matches[0], true);
                if ($jsonMatch && isset($jsonMatch['translation'])) {
                    return [
                        'translation' => $jsonMatch['translation'],
                        'pronunciation' => isset($jsonMatch['pronunciation']) ? $jsonMatch['pronunciation'] : ''
                    ];
                }
            }
            
            // Thử tìm JSON block trong markdown
            if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $responseText, $matches)) {
                $jsonMatch = json_decode($matches[1], true);
                if ($jsonMatch && isset($jsonMatch['translation'])) {
                    return [
                        'translation' => $jsonMatch['translation'],
                        'pronunciation' => isset($jsonMatch['pronunciation']) ? $jsonMatch['pronunciation'] : ''
                    ];
                }
            }
            
            // Nếu không parse được JSON, trả về text như translation
            return [
                'translation' => $responseText,
                'pronunciation' => ''
            ];
        }
    } elseif (isset($result['error'])) {
        $errorMsg = isset($result['error']['message']) ? $result['error']['message'] : 'Lỗi không xác định từ Gemini API';
        
        // Kiểm tra rate limit và retry
        if (strpos($errorMsg, 'Resource exhausted') !== false && $retryCount < $maxRetries) {
            sleep($retryDelay * ($retryCount + 1));
            return translateText($text, $sourceLang, $targetLang, $languageNames, $retryCount + 1);
        }
        
        throw new Exception($errorMsg);
    } else {
        throw new Exception('Không thể lấy kết quả dịch từ Gemini API. Response: ' . substr(json_encode($result), 0, 500));
    }
}

// Xử lý request
try {
    // Kiểm tra method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'error' => 'Chỉ chấp nhận phương thức POST'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Lấy dữ liệu từ POST
    $text = isset($_POST['text']) ? trim($_POST['text']) : '';
    $sourceLang = isset($_POST['source']) ? $_POST['source'] : 'auto';
    $targetLang = isset($_POST['target']) ? $_POST['target'] : 'en';
    
    // Kiểm tra dữ liệu đầu vào
    if (empty($text)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Văn bản cần dịch không được để trống'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (empty($targetLang)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Ngôn ngữ đích không được để trống'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Kiểm tra độ dài văn bản (Gemini API có giới hạn)
    if (strlen($text) > 30000) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Văn bản quá dài (tối đa 30000 ký tự)'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Thực hiện dịch bằng Gemini AI
    $translationResult = translateText($text, $sourceLang, $targetLang, $languageNames);
    
    // Trả về kết quả
    if (is_array($translationResult)) {
        echo json_encode([
            'success' => true,
            'translatedText' => $translationResult['translation'],
            'pronunciation' => $translationResult['pronunciation'],
            'sourceLang' => $sourceLang,
            'targetLang' => $targetLang
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // Fallback cho trường hợp cũ
        echo json_encode([
            'success' => true,
            'translatedText' => $translationResult,
            'pronunciation' => '',
            'sourceLang' => $sourceLang,
            'targetLang' => $targetLang
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    // Trả về lỗi
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Error $e) {
    // Bắt lỗi PHP fatal errors
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Lỗi server: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

