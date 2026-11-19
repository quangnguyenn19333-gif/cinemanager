/**
 * ============================================================
 * CINEMANAGER CHATBOT - HỆ THỐNG CHĂM SÓC KHÁCH HÀNG TỰ ĐỘNG
 * ============================================================
 * Chatbot thông minh tích hợp sẵn bộ câu hỏi - trả lời 
 * dành riêng cho rạp phim CineManager.
 */

(function () {
    'use strict';

    // =========================================================
    // 1. CƠ SỞ DỮ LIỆU CÂU HỎI - TRẢ LỜI
    // =========================================================
    const FAQ_DATA = [
        // --- GIÁ VÉ ---
        {
            keywords: ['giá vé', 'gia ve', 'bao nhiêu tiền', 'bao nhieu tien', 'vé bao nhiêu', 've bao nhieu', 'giá tiền', 'gia tien', 'price', 'giá', 'gia'],
            answer: `🎫 <b>Bảng giá vé CineManager:</b><br><br>
                🪑 <b>Ghế thường:</b> 55.000đ - 75.000đ<br>
                ⭐ <b>Ghế VIP:</b> 75.000đ - 100.000đ<br>
                💑 <b>Ghế đôi (Sweetbox):</b> 150.000đ - 200.000đ<br><br>
                📌 Giá có thể thay đổi theo suất chiếu, ngày lễ và cơ sở rạp. Bạn có thể xem giá chính xác khi <a href="booking.html">đặt vé online</a> nhé!`
        },
        // --- ĐẶT VÉ ---
        {
            keywords: ['đặt vé', 'dat ve', 'mua vé', 'mua ve', 'book', 'booking', 'cách đặt', 'cach dat', 'đặt vé online', 'hướng dẫn đặt vé'],
            answer: `🎬 <b>Hướng dẫn đặt vé online CineManager:</b><br><br>
                1️⃣ Chọn phim bạn muốn xem tại <a href="index.html">Trang chủ</a><br>
                2️⃣ Bấm <b>"🎫 Đặt Vé"</b> trên poster phim<br>
                3️⃣ Chọn rạp, suất chiếu và ghế ngồi<br>
                4️⃣ Chọn combo bắp nước (nếu muốn)<br>
                5️⃣ Thanh toán qua MoMo, Visa hoặc VNPay<br>
                6️⃣ Nhận vé điện tử tại mục <a href="my-tickets.html">🎫 Vé của tôi</a><br><br>
                💡 <i>Mẹo: Đặt vé sớm để chọn được ghế đẹp nhé!</i>`
        },
        // --- LỊCH CHIẾU ---
        {
            keywords: ['lịch chiếu', 'lich chieu', 'suất chiếu', 'suat chieu', 'giờ chiếu', 'gio chieu', 'phim gì', 'phim gi', 'phim nào', 'phim nao', 'chiếu phim gì', 'showtime', 'schedule'],
            answer: `📅 <b>Lịch chiếu phim CineManager:</b><br><br>
                Bạn có thể xem toàn bộ lịch chiếu tại trang <a href="showtimes.html">📅 Lịch Chiếu</a>.<br><br>
                🎯 Tại trang chủ, bạn cũng có thể lọc theo:<br>
                • <b>Đang Chiếu</b> - Phim đang có suất<br>
                • <b>Sắp Chiếu</b> - Phim sắp ra mắt<br>
                • <b>Suất Chiếu Sớm</b> - Xem trước ngày công chiếu<br><br>
                🕐 Rạp hoạt động từ <b>8:00 - 24:00</b> hàng ngày.`
        },
        // --- ƯU ĐÃI / KHUYẾN MÃI ---
        {
            keywords: ['ưu đãi', 'uu dai', 'khuyến mãi', 'khuyen mai', 'giảm giá', 'giam gia', 'sale', 'discount', 'promo', 'voucher', 'mã giảm', 'ma giam', 'coupon'],
            answer: `🎉 <b>Ưu đãi HOT tại CineManager:</b><br><br>
                🔥 <b>Flash Deal Black Friday:</b> Giảm đến 50% vé phim<br>
                🎉 <b>Cine Party Combo:</b> Mua 2 tặng 1 combo bắp nước<br>
                🍿 <b>Combo CCEM 3:</b> Ưu đãi đặc biệt dành cho phim mới<br>
                🎂 <b>Sinh nhật vui:</b> Tặng vé miễn phí trong tháng sinh nhật<br><br>
                📢 Theo dõi banner trang chủ và fanpage để không bỏ lỡ ưu đãi mới nhất nhé!`
        },
        // --- COMBO BẮP NƯỚC ---
        {
            keywords: ['combo', 'bắp nước', 'bap nuoc', 'popcorn', 'đồ ăn', 'do an', 'nước uống', 'nuoc uong', 'bắp rang', 'bap rang', 'snack'],
            answer: `🍿 <b>Menu Combo CineManager:</b><br><br>
                🥤 <b>Combo Solo:</b> 1 bắp + 1 nước — 59.000đ<br>
                🍿 <b>Combo Couple:</b> 1 bắp lớn + 2 nước — 89.000đ<br>
                🎉 <b>Combo Party:</b> 2 bắp + 4 nước + 1 snack — 159.000đ<br>
                🌟 <b>Combo VIP:</b> 1 bắp caramel + 2 nước + nachos — 129.000đ<br><br>
                💡 Thêm combo khi đặt vé để tiết kiệm hơn so với mua lẻ tại quầy!`
        },
        // --- THANH TOÁN ---
        {
            keywords: ['thanh toán', 'thanh toan', 'payment', 'trả tiền', 'tra tien', 'momo', 'visa', 'vnpay', 'chuyển khoản', 'chuyen khoan', 'pay'],
            answer: `💳 <b>Phương thức thanh toán CineManager:</b><br><br>
                📱 <b>MoMo:</b> Quét mã QR, thanh toán siêu nhanh<br>
                💳 <b>Visa / Mastercard:</b> Thẻ quốc tế an toàn<br>
                🏦 <b>VNPay:</b> Liên kết ngân hàng nội địa<br><br>
                🔒 Mọi giao dịch đều được <b>mã hóa SSL</b> để bảo mật thông tin.<br>
                ⚡ Sau khi thanh toán, vé sẽ xuất hiện ngay trong mục <a href="my-tickets.html">Vé của tôi</a>.`
        },
        // --- HOÀN VÉ / ĐỔI VÉ ---
        {
            keywords: ['hoàn vé', 'hoan ve', 'đổi vé', 'doi ve', 'hủy vé', 'huy ve', 'refund', 'cancel', 'trả vé', 'tra ve'],
            answer: `🔄 <b>Chính sách hoàn/đổi vé CineManager:</b><br><br>
                ✅ Hoàn vé <b>trước 4 tiếng</b> so với giờ chiếu: hoàn <b>100%</b><br>
                ⚠️ Hoàn vé <b>trước 2 tiếng</b>: hoàn <b>70%</b><br>
                ❌ Hoàn vé <b>dưới 2 tiếng</b> hoặc sau giờ chiếu: <b>không hỗ trợ</b><br><br>
                📞 Liên hệ hotline <b>1900 6017</b> hoặc email <b>support@cinemanager.com</b> để được hỗ trợ hoàn vé.`
        },
        // --- TÀI KHOẢN ---
        {
            keywords: ['tài khoản', 'tai khoan', 'đăng ký', 'dang ky', 'đăng nhập', 'dang nhap', 'login', 'register', 'sign up', 'mật khẩu', 'mat khau', 'quên mật khẩu'],
            answer: `👤 <b>Hướng dẫn tài khoản CineManager:</b><br><br>
                📝 <b>Đăng ký:</b> Vào trang <a href="login.html">Đăng nhập</a> → chọn <b>Đăng ký</b><br>
                🔑 <b>Đăng nhập:</b> Dùng email/tên đăng nhập + mật khẩu hoặc <b>Google</b><br>
                🔒 <b>Đổi mật khẩu:</b> Sau khi đăng nhập, bấm nút <b>"Đổi mật khẩu"</b> ở header<br><br>
                ⚡ Đăng nhập bằng Google siêu nhanh, không cần nhớ mật khẩu!`
        },
        // --- LIÊN HỆ / HỖ TRỢ ---
        {
            keywords: ['liên hệ', 'lien he', 'hotline', 'điện thoại', 'dien thoai', 'email', 'hỗ trợ', 'ho tro', 'contact', 'support', 'phone', 'gọi'],
            answer: `📞 <b>Liên hệ CineManager:</b><br><br>
                ☎️ <b>Hotline:</b> 1900 6017 (8:00 - 22:00)<br>
                📧 <b>Email:</b> support@cinemanager.com<br>
                📍 <b>Địa chỉ:</b> Tầng 10, Tòa nhà CineManager, 220 Phạm Hùng, Nam Từ Liêm, Hà Nội<br><br>
                🌐 <b>Mạng xã hội:</b><br>
                • Facebook: /CineManagerVN<br>
                • Instagram: @cinemanager_vn<br>
                • Zalo OA: CineManager Việt Nam`
        },
        // --- GHẾ NGỒI ---
        {
            keywords: ['ghế', 'ghe', 'seat', 'ghế vip', 'ghe vip', 'sweetbox', 'ghế đôi', 'ghe doi', 'ghế thường', 'ghe thuong', 'chỗ ngồi', 'cho ngoi'],
            answer: `💺 <b>Các loại ghế tại CineManager:</b><br><br>
                🪑 <b>Ghế thường (Standard):</b> Thoải mái, giá mềm, phù hợp mọi người<br>
                ⭐ <b>Ghế VIP:</b> Rộng hơn, nệm êm, vị trí trung tâm<br>
                💑 <b>Ghế đôi Sweetbox:</b> Dành cho cặp đôi, hàng cuối riêng tư<br><br>
                💡 Ghế VIP và Sweetbox thường nằm ở vị trí đẹp nhất trong phòng chiếu. Bạn có thể chọn ghế khi đặt vé online!`
        },
        // --- PHÒNG CHIẾU ---
        {
           keywords: ['phòng chiếu', 'phong chieu', 'imax', 'phòng thường', 'phòng vip', 'phong thuong', 'phong vip', 'rạp', 'rap', 'cinema', 'cơ sở', 'co so'],
            answer: `🏢 <b>Hệ thống phòng chiếu CineManager:</b><br><br>
                🪑 <b>Phòng Thường (Standard):</b> Không gian thoải mái, màn hình sắc nét, âm thanh sống động.<br>
                ⭐ <b>Phòng VIP:</b> Ghế da cao cấp rộng rãi, nệm êm ái, vị trí ngồi trung tâm cực đẹp.<br>
                🖥️ <b>Phòng IMAX:</b> Màn hình khổng lồ chuẩn quốc tế, âm thanh vòm chân thực siêu đỉnh.<br><br>
                📺 <b>ScreenX:</b> Màn hình 270°, bao trọn tầm nhìn<br><br>
                📍 Loại phòng chiếu phụ thuộc vào từng cơ sở rạp.`
        },
        // --- VÉ CỦA TÔI ---
        {
            keywords: ['vé của tôi', 've cua toi', 'xem vé', 'xem ve', 'my ticket', 'lịch sử', 'lich su', 'history', 'vé đã mua', 've da mua', 'đơn hàng', 'don hang'],
            answer: `🎫 <b>Xem vé đã đặt:</b><br><br>
                Bạn có thể xem toàn bộ vé đã mua tại trang <a href="my-tickets.html">🎫 Vé của tôi</a>.<br><br>
                Tại đây bạn sẽ thấy:<br>
                • Thông tin phim, suất chiếu, ghế ngồi<br>
                • Mã QR để quét khi vào rạp<br>
                • Trạng thái vé (đã sử dụng / chưa sử dụng)<br><br>
                📱 Lưu mã QR trên điện thoại để vào rạp tiện lợi hơn nhé!`
        },
        // --- GIỜ HOẠT ĐỘNG ---
        {
            keywords: ['giờ mở cửa', 'gio mo cua', 'giờ hoạt động', 'gio hoat dong', 'mấy giờ', 'may gio', 'thời gian', 'thoi gian', 'opening', 'hour'],
            answer: `🕐 <b>Giờ hoạt động CineManager:</b><br><br>
                📅 <b>Thứ 2 - Chủ Nhật:</b> 8:00 - 24:00<br>
                📞 <b>Hotline CSKH:</b> 8:00 - 22:00<br>
                🎬 <b>Suất chiếu cuối:</b> thường bắt đầu lúc 22:00 - 23:00<br><br>
                ⚡ Hệ thống đặt vé online hoạt động <b>24/7</b>!`
        },
        // --- CẢM ƠN / CHÀO ---
        {
            keywords: ['cảm ơn', 'cam on', 'thanks', 'thank', 'tks', 'ok', 'được rồi', 'duoc roi', 'hiểu rồi', 'hieu roi'],
            answer: `😊 Không có gì! Rất vui được hỗ trợ bạn. Nếu cần thêm thông tin gì, cứ hỏi mình nhé! 🍿`
        },
        {
            keywords: ['xin chào', 'xin chao', 'hello', 'hi', 'chào', 'chao', 'hey', 'alo'],
            answer: `👋 Xin chào bạn! Mình là <b>CineBot</b> - trợ lý ảo của CineManager.<br><br>Mình có thể giúp bạn:<br>
                🎫 Hướng dẫn đặt vé<br>
                💰 Thông tin giá vé & ưu đãi<br>
                📅 Lịch chiếu phim<br>
                📞 Liên hệ hỗ trợ<br><br>
                Bạn cần hỗ trợ gì nào? 😊`
        },
        // --- TUỔI / GIỚI HẠN ĐỘ TUỔI ---
        {
            keywords: ['độ tuổi', 'do tuoi', 'tuổi', 'tuoi', 'trẻ em', 'tre em', 'bé', 'be', 'kid', 'children', 'age', 'giới hạn tuổi', 'gioi han tuoi', '13+', '16+', '18+', 'rating'],
            answer: `👶 <b>Quy định độ tuổi khi xem phim:</b><br><br>
                🟢 <b>P (Phổ biến):</b> Mọi lứa tuổi đều xem được<br>
                🟡 <b>C13 (13+):</b> Khán giả từ 13 tuổi trở lên<br>
                🟠 <b>C16 (16+):</b> Khán giả từ 16 tuổi trở lên<br>
                🔴 <b>C18 (18+):</b> Khán giả từ 18 tuổi trở lên<br><br>
                📌 Nhân viên có quyền yêu cầu xuất trình <b>CCCD/CMND</b> để xác minh tuổi.`
        },
        // --- PARKING / ĐỖ XE ---
        {
            keywords: ['đỗ xe', 'do xe', 'gửi xe', 'gui xe', 'parking', 'bãi đỗ', 'bai do', 'xe máy', 'xe may', 'ô tô', 'o to'],
            answer: `🅿️ <b>Thông tin gửi xe tại CineManager:</b><br><br>
                🏍️ <b>Xe máy:</b> 5.000đ/lượt (miễn phí khi có vé xem phim)<br>
                🚗 <b>Ô tô:</b> 20.000đ - 30.000đ/lượt tùy cơ sở<br><br>
                💡 Xuất trình vé xem phim tại quầy gửi xe để được <b>miễn phí</b> gửi xe máy!`
        }
    ];

    // Gợi ý nhanh hiển thị ban đầu
    const QUICK_SUGGESTIONS = [
        '💰 Giá vé',
        '🎬 Cách đặt vé',
        '📅 Lịch chiếu',
        '🎉 Ưu đãi',
        '🍿 Combo bắp nước',
        '📞 Liên hệ'
    ];

    // =========================================================
    // 2. TẠO GIAO DIỆN CHATBOT (DOM)
    // =========================================================
    function createChatbotDOM() {
        // Nút toggle
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'chatbot-toggle';
        toggleBtn.id = 'chatbot-toggle';
        toggleBtn.innerHTML = `
            <span class="chatbot-icon">💬</span>
            <span class="chatbot-badge" id="chatbot-badge" style="display:none;">1</span>
        `;

        // Cửa sổ chat
        const chatWindow = document.createElement('div');
        chatWindow.className = 'chatbot-window';
        chatWindow.id = 'chatbot-window';
        chatWindow.innerHTML = `
            <div class="chatbot-header">
                <div class="chatbot-avatar">🤖</div>
                <div class="chatbot-header-info">
                    <h4>CineBot - Trợ lý CineManager</h4>
                    <span><span class="chatbot-status-dot"></span>Đang hoạt động</span>
                </div>
                <button class="chatbot-close" id="chatbot-close-btn">✕</button>
            </div>
            <div class="chatbot-messages" id="chatbot-messages"></div>
            <div class="chatbot-suggestions" id="chatbot-suggestions"></div>
            <div class="chatbot-input-area">
                <input type="text" id="chatbot-input" placeholder="Nhập câu hỏi của bạn..." autocomplete="off">
                <button class="chatbot-send-btn" id="chatbot-send-btn">➤</button>
            </div>
        `;

        document.body.appendChild(toggleBtn);
        document.body.appendChild(chatWindow);
    }

    // =========================================================
    // 3. LOGIC CHATBOT
    // =========================================================
    let isOpen = false;
    let hasGreeted = false;

    function initChatbot() {
        createChatbotDOM();

        const toggleBtn = document.getElementById('chatbot-toggle');
        const chatWindow = document.getElementById('chatbot-window');
        const closeBtn = document.getElementById('chatbot-close-btn');
        const sendBtn = document.getElementById('chatbot-send-btn');
        const input = document.getElementById('chatbot-input');
        const messagesContainer = document.getElementById('chatbot-messages');
        const suggestionsContainer = document.getElementById('chatbot-suggestions');
        const badge = document.getElementById('chatbot-badge');

        // Hiện badge sau 3 giây
        setTimeout(() => {
            if (!isOpen) {
                badge.style.display = 'flex';
            }
        }, 3000);

        // Toggle mở / đóng
        toggleBtn.addEventListener('click', () => {
            isOpen = !isOpen;
            chatWindow.classList.toggle('open', isOpen);
            toggleBtn.classList.toggle('active', isOpen);

            if (isOpen) {
                badge.style.display = 'none';
                toggleBtn.querySelector('.chatbot-icon').textContent = '✕';
                if (!hasGreeted) {
                    showWelcomeMessage();
                    showSuggestions(QUICK_SUGGESTIONS);
                    hasGreeted = true;
                }
                setTimeout(() => input.focus(), 400);
            } else {
                toggleBtn.querySelector('.chatbot-icon').textContent = '💬';
            }
        });

        // Đóng chatbot
        closeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            isOpen = false;
            chatWindow.classList.remove('open');
            toggleBtn.classList.remove('active');
            toggleBtn.querySelector('.chatbot-icon').textContent = '💬';
        });

        // Gửi tin nhắn
        sendBtn.addEventListener('click', handleSend);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.isComposing) {
                e.preventDefault();
                handleSend();
            }
        });

        function handleSend() {
            const text = input.value.trim();
            if (!text) return;

            addMessage(text, 'user');
            input.value = '';
            suggestionsContainer.innerHTML = '';

            // Hiệu ứng đang gõ
            showTyping();

            setTimeout(() => {
                removeTyping();
                const reply = getReply(text);
                addMessage(reply, 'bot');

                // Hiện lại gợi ý sau khi trả lời
                setTimeout(() => {
                    showSuggestions(QUICK_SUGGESTIONS);
                }, 500);
            }, 800 + Math.random() * 700);
        }

        // Tin nhắn chào mừng
        function showWelcomeMessage() {
            const welcomeMsg = `👋 Xin chào! Mình là <b>CineBot</b> — trợ lý chăm sóc khách hàng của <b>CineManager</b>.<br><br>
                Mình có thể giúp bạn giải đáp về:<br>
                🎫 Đặt vé & giá vé<br>
                📅 Lịch chiếu phim<br>
                🎉 Ưu đãi khuyến mãi<br>
                🍿 Combo bắp nước<br>
                📞 Thông tin liên hệ<br><br>
                Hãy chọn câu hỏi bên dưới hoặc gõ trực tiếp nhé! 😊`;
            addMessage(welcomeMsg, 'bot');
        }

        // Hiển thị gợi ý
        function showSuggestions(list) {
            suggestionsContainer.innerHTML = '';
            list.forEach(text => {
                const btn = document.createElement('button');
                btn.textContent = text;
                btn.addEventListener('click', () => {
                    input.value = text.replace(/^[\p{Emoji_Presentation}\p{Extended_Pictographic}]\s*/u, '');
                    handleSend();
                });
                suggestionsContainer.appendChild(btn);
            });
        }

        // Thêm tin nhắn vào cửa sổ
        function addMessage(content, sender) {
            const msgDiv = document.createElement('div');
            msgDiv.className = `chat-message ${sender}`;

            const avatar = sender === 'bot' ? '🤖' : '👤';
            const time = new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' });

            msgDiv.innerHTML = `
                <div class="chat-msg-avatar">${avatar}</div>
                <div>
                    <div class="chat-bubble">${content}</div>
                    <div class="chat-time">${time}</div>
                </div>
            `;

            messagesContainer.appendChild(msgDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Hiệu ứng đang gõ
        function showTyping() {
            const typingDiv = document.createElement('div');
            typingDiv.className = 'chat-message bot';
            typingDiv.id = 'typing-indicator';
            typingDiv.innerHTML = `
                <div class="chat-msg-avatar">🤖</div>
                <div class="chat-bubble">
                    <div class="typing-indicator">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            `;
            messagesContainer.appendChild(typingDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function removeTyping() {
            const typing = document.getElementById('typing-indicator');
            if (typing) typing.remove();
        }
    }

    // =========================================================
    // 4. MATCHING ENGINE - TÌM CÂU TRẢ LỜI PHÙ HỢP
    // =========================================================
    function removeVietnameseTones(str) {
        return str
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/đ/g, 'd')
            .replace(/Đ/g, 'D');
    }

    function getReply(userInput) {
        const input = userInput.toLowerCase().trim();
        const inputNoTone = removeVietnameseTones(input);

        let bestMatch = null;
        let bestScore = 0;

        FAQ_DATA.forEach(faq => {
            let score = 0;
            faq.keywords.forEach(keyword => {
                const kwLower = keyword.toLowerCase();
                const kwNoTone = removeVietnameseTones(kwLower);

                // Exact match
                if (input.includes(kwLower) || inputNoTone.includes(kwNoTone)) {
                    score += kwLower.length; // Ưu tiên keyword dài hơn
                }

                // Word-level match
                const inputWords = input.split(/\s+/);
                const kwWords = kwLower.split(/\s+/);
                kwWords.forEach(kw => {
                    if (inputWords.some(w => w === kw || removeVietnameseTones(w) === removeVietnameseTones(kw))) {
                        score += 2;
                    }
                });
            });

            if (score > bestScore) {
                bestScore = score;
                bestMatch = faq;
            }
        });

        if (bestMatch && bestScore >= 2) {
            return bestMatch.answer;
        }

        // Fallback - Không tìm thấy câu trả lời
        return `🤔 Xin lỗi, mình chưa hiểu câu hỏi của bạn. Bạn có thể thử hỏi theo cách khác hoặc liên hệ trực tiếp:<br><br>
            ☎️ <b>Hotline:</b> 1900 6017<br>
            📧 <b>Email:</b> support@cinemanager.com<br>
            🕐 <b>Giờ hỗ trợ:</b> 8:00 - 22:00<br><br>
            Hoặc chọn một trong các chủ đề gợi ý bên dưới nhé! 👇`;
    }

    // =========================================================
    // 5. KHỞI CHẠY
    // =========================================================
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChatbot);
    } else {
        initChatbot();
    }

})();
