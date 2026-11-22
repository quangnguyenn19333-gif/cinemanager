/* =====================================================
   FOOTER DÙNG CHUNG — CineManager
   Cách dùng: các trang đã nạp assets/js/site-data.js sẽ TỰ có footer này
   (site-data.js tự nạp file này). Trang nào không dùng site-data.js thì
   thêm 1 dòng vào cuối <body>:
       <script src="assets/js/site-footer.js"></script>
   Script sẽ:
     1) tự nạp assets/css/footer.css (nếu trang chưa nạp)
     2) gỡ footer cũ (.cinema-footer hoặc <footer> khác)
     3) chèn footer mới ở cuối trang
   Muốn sửa footer: chỉ sửa trong file này.
   ===================================================== */
(function () {
    'use strict';

    // Chống chạy 2 lần (khi trang vừa có thẻ <script> vừa được site-data.js nạp)
    if (window.__siteFooterInit) return;
    window.__siteFooterInit = true;

    // Dùng nền kem như CGV? đổi thành 'site-footer site-footer--cream'
    var FOOTER_CLASS = 'site-footer';

    var FOOTER_HTML = '' +
    '<div class="ft-formats">' +
        '<div class="ft-formats-inner">' +
            '<span class="fmt fmt-4dx"><em>4D</em><b>X</b></span>' +
            '<span class="fmt fmt-imax">IMAX</span>' +
            '<span class="fmt fmt-gold">Gold Class</span>' +
            '<span class="fmt fmt-sweet">Sweetbox</span>' +
            '<span class="fmt fmt-premium">Premium Cinema</span>' +
            '<span class="fmt fmt-screenx">SCREEN<b>X</b></span>' +
        '</div>' +
    '</div>' +

    '<div class="ft-main">' +
        '<div class="ft-col">' +
            '<h3>CineManager Việt Nam</h3>' +
            '<ul>' +
                '<li><a href="about.html">Giới thiệu</a></li>' +
                '<li><a href="cinemas.html">Hệ thống rạp</a></li>' +
                '<li><a href="promotions.html">Khuyến mãi</a></li>' +
                '<li><a href="membership.html">Thành viên</a></li>' +
                '<li><a href="about.html?tab=careers">Tuyển dụng</a></li>' +
            '</ul>' +
        '</div>' +

        '<div class="ft-col">' +
            '<h3>Điều khoản sử dụng</h3>' +
            '<ul>' +
                '<li><a href="#">Điều khoản chung</a></li>' +
                '<li><a href="#">Điều khoản giao dịch</a></li>' +
                '<li><a href="#">Chính sách thanh toán</a></li>' +
                '<li><a href="#">Chính sách bảo mật</a></li>' +
                '<li><a href="#">Câu hỏi thường gặp</a></li>' +
            '</ul>' +
        '</div>' +

        '<div class="ft-col">' +
            '<h3>Kết nối với chúng tôi</h3>' +
            '<div class="ft-social">' +
                '<a href="about.html?tab=contact" aria-label="Facebook"><img src="assets/images/fb.png" alt="Facebook"></a>' +
                '<a href="about.html?tab=contact" aria-label="YouTube"><img src="assets/images/youtube.png" alt="YouTube"></a>' +
                '<a href="about.html?tab=contact" aria-label="Instagram"><img src="assets/images/ig.png" alt="Instagram"></a>' +
                '<a href="about.html?tab=contact" aria-label="Zalo"><img src="assets/images/zalo.png" alt="Zalo"></a>' +
            '</div>' +
        '</div>' +

        '<div class="ft-col">' +
            '<h3>Chăm sóc khách hàng</h3>' +
            '<p>Hotline: <a href="tel:19006017">1900 6017</a></p>' +
            '<p>Giờ làm việc: 8:00 - 22:00 (kể cả Lễ, Tết)</p>' +
            '<p>Email hỗ trợ: <a href="mailto:support@cinemanager.com">support@cinemanager.com</a></p>' +
            '<p><a href="about.html?tab=contact" class="ft-cta">Gửi liên hệ / góp ý &rarr;</a></p>' +
        '</div>' +
    '</div>' +

    '<div class="ft-company">' +
        '<div class="ft-company-inner">' +
            '<div class="ft-brand"><b>CINE</b>MANAGER</div>' +
            '<div class="ft-company-text">' +
                '<p><strong>Công Ty TNHH CineManager Việt Nam</strong></p>' +
                '<p>Giấy phép kinh doanh phim số 2026/TT-TT, cấp bởi Bộ Văn hóa, Thể thao và Du lịch.</p>' +
                '<p>Địa chỉ: Tầng 10, Tòa nhà CineManager, 220 Phạm Hùng, Nam Từ Liêm, Hà Nội, Việt Nam.</p>' +
                '<p>Hotline: 1900 6017 &nbsp;|&nbsp; Email: support@cinemanager.com</p>' +
            '</div>' +
        '</div>' +
    '</div>' +

    '<div class="ft-bottom">' +
        '<div class="ft-bottom-inner">' +
            '<p>© 2026 CineManager. All Rights Reserved.</p>' +
            '<div class="ft-pay">' +
                '<img src="assets/images/momo.png" alt="MoMo">' +
                '<img src="assets/images/visa.png" alt="Visa">' +
                '<img src="assets/images/vnp.png" alt="VNPay">' +
            '</div>' +
        '</div>' +
    '</div>';

    // Đường dẫn footer.css suy ra từ vị trí của chính file JS này
    function cssHref() {
        var me = document.currentScript ||
            document.querySelector('script[src*="site-footer.js"]');
        var src = me && me.getAttribute('src');
        return src ? src.replace(/js\/site-footer\.js.*$/, 'css/footer.css?v=7')
                   : 'assets/css/footer.css?v=7';
    }
    var CSS_HREF = cssHref();

    function ensureCss() {
        if (document.querySelector('link[href*="footer.css"]')) return;
        var l = document.createElement('link');
        l.rel = 'stylesheet';
        l.href = CSS_HREF;
        document.head.appendChild(l);
    }

    // Nạp font (Bebas Neue, Cormorant Garamond, Inter) nếu trang chưa nạp
    function ensureFonts() {
        if (document.querySelector('link[href*="Cormorant"]')) return;
        var l = document.createElement('link');
        l.rel = 'stylesheet';
        l.href = 'https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700;800;900&display=swap';
        document.head.appendChild(l);
    }

    // Gỡ MỌI footer cũ: <footer class="cinema-footer">, <footer> con trực tiếp của body,
    // hoặc bất kỳ khối nào chứa .cinema-types (dù được đổi tên/bọc thêm)
    function removeOldFooters() {
        var olds = document.querySelectorAll('footer.cinema-footer, .cinema-footer, body > footer:not(.site-footer)');
        var i, el;
        for (i = 0; i < olds.length; i++) {
            if (olds[i].parentNode) olds[i].parentNode.removeChild(olds[i]);
        }
        var types = document.querySelectorAll('.cinema-types');
        for (i = 0; i < types.length; i++) {
            el = types[i];
            if (el.closest('.site-footer')) continue;
            var box = el.closest('footer') || el;
            while (box.parentNode && box.parentNode !== document.body && box.parentNode !== document.documentElement) {
                box = box.parentNode;
            }
            if (box.parentNode) box.parentNode.removeChild(box);
        }
    }

    function mount() {
        ensureCss();
        ensureFonts();
        removeOldFooters();
        if (document.querySelector('footer.site-footer')) return; // đã có, không chèn lặp
        var f = document.createElement('footer');
        f.className = FOOTER_CLASS;
        f.innerHTML = FOOTER_HTML;
        document.body.appendChild(f);

        // Nếu footer cũ được chèn muộn bởi script khác (vd. site-header.js), gỡ nốt (~6 giây)
        var tries = 0;
        var t = setInterval(function () {
            removeOldFooters();
            if (++tries >= 12) clearInterval(t);
        }, 500);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mount);
    } else {
        mount();
    }
})();
