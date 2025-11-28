// Biến để lưu timeout cho debounce
let translateTimeout = null;

// Hàm dịch văn bản
async function translateText(showErrorOnEmpty = true) {
    const sourceText = document.getElementById('source-text').value.trim();
    const sourceLang = document.getElementById('source-lang').value;
    const targetLang = document.getElementById('target-lang').value;

    // Kiểm tra văn bản đầu vào
    if (!sourceText) {
        if (showErrorOnEmpty) {
            showError('Vui lòng nhập văn bản cần dịch!');
        } else {
            // Nếu là tự động dịch và văn bản trống, xóa kết quả
            document.getElementById('target-text').value = '';
            document.getElementById('pronunciation-section').classList.add('hidden');
            document.getElementById('pronunciation-text').textContent = '';
        }
        return;
    }

    // Kiểm tra ngôn ngữ nguồn và đích
    if (sourceLang === targetLang && sourceLang !== 'auto') {
        showError('Ngôn ngữ nguồn và đích không được giống nhau!');
        return;
    }

    hideError();

    try {
        const formData = new FormData();
        formData.append('text', sourceText);
        formData.append('source', sourceLang);
        formData.append('target', targetLang);

        const response = await fetch('api/translate.php', {
            method: 'POST',
            body: formData
        });

        // Kiểm tra response status
        if (!response.ok) {
            const errorText = await response.text();
            let errorData;
            try {
                errorData = JSON.parse(errorText);
            } catch (e) {
                errorData = { error: errorText || `HTTP ${response.status}: ${response.statusText}` };
            }
            throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();

        if (data.success) {
            document.getElementById('target-text').value = data.translatedText;
            
            // Hiển thị phiên âm nếu có
            const pronunciationSection = document.getElementById('pronunciation-section');
            const pronunciationText = document.getElementById('pronunciation-text');
            
            if (data.pronunciation && data.pronunciation.trim() !== '') {
                pronunciationText.textContent = data.pronunciation;
                pronunciationSection.classList.remove('hidden');
            } else {
                pronunciationSection.classList.add('hidden');
            }
        } else {
            showError(data.error || 'Có lỗi xảy ra khi dịch văn bản!');
            // Ẩn phần phiên âm khi có lỗi
            document.getElementById('pronunciation-section').classList.add('hidden');
        }
    } catch (error) {
        let errorMessage = error.message;
        
        // Kiểm tra nếu là lỗi rate limit
        if (errorMessage.includes('Resource exhausted') || 
            errorMessage.includes('rate limit') || 
            errorMessage.includes('quota') ||
            errorMessage.includes('429')) {
            errorMessage = '⚠️ API đã đạt giới hạn. Vui lòng đợi vài phút rồi thử lại.';
        } else if (error instanceof SyntaxError) {
            errorMessage = 'Lỗi: Server trả về dữ liệu không hợp lệ';
        } else if (!errorMessage.includes('Lỗi')) {
            errorMessage = 'Lỗi: ' + errorMessage;
        }
        
        showError(errorMessage);
        console.error('Translation error:', error);
    }
}

// Hàm đổi ngôn ngữ
function swapLanguages() {
    const sourceLang = document.getElementById('source-lang').value;
    const targetLang = document.getElementById('target-lang').value;
    const sourceText = document.getElementById('source-text').value;
    const targetText = document.getElementById('target-text').value;

    // Đổi ngôn ngữ
    document.getElementById('source-lang').value = targetLang === 'auto' ? 'en' : targetLang;
    document.getElementById('target-lang').value = sourceLang === 'auto' ? 'vi' : sourceLang;

    // Đổi văn bản
    document.getElementById('source-text').value = targetText;
    document.getElementById('target-text').value = sourceText;
}

// Hàm sao chép văn bản
function copyText() {
    const targetText = document.getElementById('target-text');
    
    if (!targetText.value.trim()) {
        showError('Không có văn bản để sao chép!');
        return;
    }

    targetText.select();
    targetText.setSelectionRange(0, 99999); // Cho mobile

    try {
        document.execCommand('copy');
        showSuccess('Đã sao chép vào clipboard!');
    } catch (err) {
        // Fallback cho trình duyệt mới
        navigator.clipboard.writeText(targetText.value).then(() => {
            showSuccess('Đã sao chép vào clipboard!');
        }).catch(() => {
            showError('Không thể sao chép văn bản!');
        });
    }
}

// Hàm phát âm phiên âm
function speakPronunciation() {
    const pronunciationText = document.getElementById('pronunciation-text').textContent.trim();
    const speakBtn = document.getElementById('speak-pronunciation-btn');
    
    if (!pronunciationText) {
        showError('Không có phiên âm để phát âm!');
        return;
    }
    
    // Kiểm tra xem có đang phát âm không
    if (window.speechSynthesis.speaking) {
        window.speechSynthesis.cancel();
        speakBtn.textContent = '🔊 Phát âm';
        speakBtn.classList.remove('speaking');
        return;
    }
    
    // Tạo utterance
    const utterance = new SpeechSynthesisUtterance(pronunciationText);
    
    // Cấu hình giọng đọc
    utterance.rate = 0.9; // Tốc độ đọc (0.1 - 10)
    utterance.pitch = 1; // Cao độ (0 - 2)
    utterance.volume = 1; // Âm lượng (0 - 1)
    
    // Thử chọn giọng phù hợp với ngôn ngữ đích
    const targetLang = document.getElementById('target-lang').value;
    const voices = window.speechSynthesis.getVoices();
    
    // Mapping ngôn ngữ với giọng đọc
    const langVoices = {
        'en': ['en-US', 'en-GB'],
        'vi': ['vi-VN'],
        'zh': ['zh-CN', 'zh-TW'],
        'ja': ['ja-JP'],
        'ko': ['ko-KR'],
        'fr': ['fr-FR'],
        'de': ['de-DE'],
        'es': ['es-ES', 'es-MX'],
        'ru': ['ru-RU'],
        'th': ['th-TH'],
        'ar': ['ar-SA'],
        'hi': ['hi-IN'],
        'pt': ['pt-BR', 'pt-PT'],
        'it': ['it-IT'],
        'nl': ['nl-NL'],
        'pl': ['pl-PL'],
        'tr': ['tr-TR'],
        'id': ['id-ID'],
        'sv': ['sv-SE'],
        'da': ['da-DK'],
        'no': ['no-NO'],
        'fi': ['fi-FI'],
        'cs': ['cs-CZ'],
        'hu': ['hu-HU'],
        'ro': ['ro-RO'],
        'bg': ['bg-BG'],
        'uk': ['uk-UA'],
        'el': ['el-GR'],
        'he': ['he-IL'],
        'fa': ['fa-IR'],
        'ur': ['ur-PK'],
        'bn': ['bn-BD'],
        'ta': ['ta-IN'],
        'te': ['te-IN'],
        'mr': ['mr-IN'],
        'ca': ['ca-ES'],
        'hr': ['hr-HR'],
        'sk': ['sk-SK'],
        'sl': ['sl-SI'],
        'lt': ['lt-LT'],
        'lv': ['lv-LV'],
        'et': ['et-EE'],
        'is': ['is-IS'],
        'ga': ['ga-IE'],
        'cy': ['cy-GB'],
        'mt': ['mt-MT'],
        'ne': ['ne-NP'],
        'ml': ['ml-IN'],
        'kn': ['kn-IN'],
        'gu': ['gu-IN'],
        'pa': ['pa-IN'],
        'tl': ['tl-PH'],
        'az': ['az-AZ'],
        'kk': ['kk-KZ'],
        'uz': ['uz-UZ'],
        'mn': ['mn-MN'],
        'mk': ['mk-MK'],
        'sq': ['sq-AL'],
        'bs': ['bs-BA'],
        'lb': ['lb-LU']
    };
    
    if (langVoices[targetLang]) {
        const preferredVoices = langVoices[targetLang];
        const voice = voices.find(v => preferredVoices.some(pv => v.lang.startsWith(pv.split('-')[0])));
        if (voice) {
            utterance.voice = voice;
        }
    }
    
    // Sự kiện khi bắt đầu phát âm
    utterance.onstart = () => {
        speakBtn.textContent = '⏸️ Dừng';
        speakBtn.classList.add('speaking');
    };
    
    // Sự kiện khi kết thúc phát âm
    utterance.onend = () => {
        speakBtn.textContent = '🔊 Phát âm';
        speakBtn.classList.remove('speaking');
    };
    
    // Sự kiện khi có lỗi
    utterance.onerror = (event) => {
        console.error('Speech synthesis error:', event);
        speakBtn.textContent = '🔊 Phát âm';
        speakBtn.classList.remove('speaking');
        showError('Không thể phát âm. Vui lòng thử lại!');
    };
    
    // Phát âm
    window.speechSynthesis.speak(utterance);
}

// Hàm phát âm bản dịch
function speakTranslation() {
    const translationText = document.getElementById('target-text').value.trim();
    const speakBtn = document.getElementById('speak-translation-btn');
    
    if (!translationText) {
        showError('Không có văn bản để phát âm!');
        return;
    }
    
    // Kiểm tra xem có đang phát âm không
    if (window.speechSynthesis.speaking) {
        window.speechSynthesis.cancel();
        speakBtn.textContent = '🔊 Phát âm';
        speakBtn.classList.remove('speaking');
        return;
    }
    
    // Tạo utterance
    const utterance = new SpeechSynthesisUtterance(translationText);
    
    // Cấu hình giọng đọc
    utterance.rate = 0.9;
    utterance.pitch = 1;
    utterance.volume = 1;
    
    // Thử chọn giọng phù hợp với ngôn ngữ đích
    const targetLang = document.getElementById('target-lang').value;
    const voices = window.speechSynthesis.getVoices();
    
    const langVoices = {
        'en': ['en-US', 'en-GB'],
        'vi': ['vi-VN'],
        'zh': ['zh-CN', 'zh-TW'],
        'ja': ['ja-JP'],
        'ko': ['ko-KR'],
        'fr': ['fr-FR'],
        'de': ['de-DE'],
        'es': ['es-ES', 'es-MX'],
        'ru': ['ru-RU'],
        'th': ['th-TH'],
        'ar': ['ar-SA'],
        'hi': ['hi-IN'],
        'pt': ['pt-BR', 'pt-PT'],
        'it': ['it-IT'],
        'nl': ['nl-NL'],
        'pl': ['pl-PL'],
        'tr': ['tr-TR'],
        'id': ['id-ID'],
        'sv': ['sv-SE'],
        'da': ['da-DK'],
        'no': ['no-NO'],
        'fi': ['fi-FI'],
        'cs': ['cs-CZ'],
        'hu': ['hu-HU'],
        'ro': ['ro-RO'],
        'bg': ['bg-BG'],
        'uk': ['uk-UA'],
        'el': ['el-GR'],
        'he': ['he-IL'],
        'fa': ['fa-IR'],
        'ur': ['ur-PK'],
        'bn': ['bn-BD'],
        'ta': ['ta-IN'],
        'te': ['te-IN'],
        'mr': ['mr-IN'],
        'ca': ['ca-ES'],
        'hr': ['hr-HR'],
        'sk': ['sk-SK'],
        'sl': ['sl-SI'],
        'lt': ['lt-LT'],
        'lv': ['lv-LV'],
        'et': ['et-EE'],
        'is': ['is-IS'],
        'ga': ['ga-IE'],
        'cy': ['cy-GB'],
        'mt': ['mt-MT'],
        'ne': ['ne-NP'],
        'ml': ['ml-IN'],
        'kn': ['kn-IN'],
        'gu': ['gu-IN'],
        'pa': ['pa-IN'],
        'tl': ['tl-PH'],
        'az': ['az-AZ'],
        'kk': ['kk-KZ'],
        'uz': ['uz-UZ'],
        'mn': ['mn-MN'],
        'mk': ['mk-MK'],
        'sq': ['sq-AL'],
        'bs': ['bs-BA'],
        'lb': ['lb-LU']
    };
    
    if (langVoices[targetLang]) {
        const preferredVoices = langVoices[targetLang];
        const voice = voices.find(v => preferredVoices.some(pv => v.lang.startsWith(pv.split('-')[0])));
        if (voice) {
            utterance.voice = voice;
        }
    }
    
    // Sự kiện khi bắt đầu phát âm
    utterance.onstart = () => {
        speakBtn.textContent = '⏸️ Dừng';
        speakBtn.classList.add('speaking');
    };
    
    // Sự kiện khi kết thúc phát âm
    utterance.onend = () => {
        speakBtn.textContent = '🔊 Phát âm';
        speakBtn.classList.remove('speaking');
    };
    
    // Sự kiện khi có lỗi
    utterance.onerror = (event) => {
        console.error('Speech synthesis error:', event);
        speakBtn.textContent = '🔊 Phát âm';
        speakBtn.classList.remove('speaking');
        showError('Không thể phát âm. Vui lòng thử lại!');
    };
    
    // Phát âm
    window.speechSynthesis.speak(utterance);
}

// Load voices khi trang được tải
window.addEventListener('load', () => {
    // Đợi voices được load
    if (window.speechSynthesis.onvoiceschanged !== undefined) {
        window.speechSynthesis.onvoiceschanged = () => {
            // Voices đã được load
        };
    }
});

// Hàm xóa tất cả
function clearText() {
    document.getElementById('source-text').value = '';
    document.getElementById('target-text').value = '';
    document.getElementById('pronunciation-section').classList.add('hidden');
    document.getElementById('pronunciation-text').textContent = '';
    
    // Dừng phát âm nếu đang phát
    if (window.speechSynthesis.speaking) {
        window.speechSynthesis.cancel();
    }
    
    // Reset các nút phát âm
    document.getElementById('speak-pronunciation-btn').textContent = '🔊 Phát âm';
    document.getElementById('speak-pronunciation-btn').classList.remove('speaking');
    document.getElementById('speak-translation-btn').textContent = '🔊 Phát âm';
    document.getElementById('speak-translation-btn').classList.remove('speaking');
    
    hideError();
    hideSuccess();
}

// Hàm hiển thị lỗi
function showError(message) {
    const errorDiv = document.getElementById('error');
    errorDiv.textContent = message;
    errorDiv.classList.remove('hidden');
    
    // Tự động ẩn sau 5 giây
    setTimeout(() => {
        hideError();
    }, 5000);
}

// Hàm ẩn lỗi
function hideError() {
    document.getElementById('error').classList.add('hidden');
}

// Hàm hiển thị thông báo thành công
function showSuccess(message) {
    hideError();
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = message;
    
    const container = document.querySelector('.container');
    const existingSuccess = container.querySelector('.success-message');
    if (existingSuccess) {
        existingSuccess.remove();
    }
    
    container.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.remove();
    }, 3000);
}

// Hàm ẩn thông báo thành công
function hideSuccess() {
    const successDiv = document.querySelector('.success-message');
    if (successDiv) {
        successDiv.remove();
    }
}

// Hàm tự động dịch với debounce
function autoTranslate() {
    // Xóa timeout cũ nếu có
    if (translateTimeout) {
        clearTimeout(translateTimeout);
    }
    
    // Đặt timeout mới - dịch sau 800ms khi người dùng ngừng gõ
    translateTimeout = setTimeout(() => {
        translateText(false); // false = không hiển thị lỗi nếu văn bản trống
    }, 800);
}

// Tự động dịch khi người dùng nhập văn bản
document.getElementById('source-text').addEventListener('input', autoTranslate);

// Tự động dịch khi thay đổi ngôn ngữ
document.getElementById('source-lang').addEventListener('change', function() {
    const sourceText = document.getElementById('source-text').value.trim();
    if (sourceText) {
        autoTranslate();
    }
});

document.getElementById('target-lang').addEventListener('change', function() {
    const sourceText = document.getElementById('source-text').value.trim();
    if (sourceText) {
        autoTranslate();
    }
});

// Cho phép dịch bằng phím Enter (Ctrl+Enter) - dịch ngay lập tức
document.getElementById('source-text').addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'Enter') {
        // Xóa timeout nếu có
        if (translateTimeout) {
            clearTimeout(translateTimeout);
        }
        translateText(true); // true = hiển thị lỗi nếu văn bản trống
    }
});

