/* =====================================================================
 * SITE-DATA.JS — Dữ liệu dùng chung cho header, footer và các trang chi tiết
 *
 * ⚠️ LƯU Ý: Nội dung dưới đây là DỮ LIỆU MẪU để hoàn thiện giao diện.
 * Chỉ các mục sau lấy từ dự án gốc: tên + địa chỉ 6 rạp (schema.sql),
 * hotline 1900 6017, email support@cinemanager.com, địa chỉ trụ sở,
 * số giấy phép (footer index.html), tên 4 hạng thành viên.
 * Hãy thay số điện thoại rạp, giờ mở cửa, điều kiện khuyến mãi, mốc điểm,
 * tin tức, vị trí tuyển dụng... bằng thông tin thật.
 * ===================================================================== */
window.SITE = {

  company: {
    name: 'Công Ty TNHH CineManager Việt Nam',
    brand: 'CineManager',
    slogan: 'Khám phá thế giới điện ảnh',
    license: 'Giấy phép kinh doanh phim số 2026/TT-TT, cấp bởi Bộ Văn Hóa Thể Thao và Du Lịch',
    address: 'Tầng 10, Tòa nhà CineManager, 220 Phạm Hùng, Nam Từ Liêm, Hà Nội, Việt Nam',
    hotline: '1900 6017',
    supportHours: '8:00 - 22:00 (hằng ngày)',
    email: 'support@cinemanager.com',
    hrEmail: 'support@cinemanager.com',
    founded: 2026
  },

  /* ---------------------------- HỆ THỐNG RẠP ---------------------------- */
  cinemas: [
    {
      slug: 'xuanthuy', name: 'CineManager Xuân Thủy', district: 'Cầu Giấy',
      venue: 'TTTM AEON MALL Xuân Thủy',
      address: 'TTTM AEON MALL Xuân Thủy, Cầu Giấy, Hà Nội',
      phone: '1900 6017', email: 'xuanthuy@cinemanager.com', hours: '08:00 - 23:30',
      rooms: 3, seats: 280, formats: ['2D', '3D', 'IMAX'],
      facilities: ['Bãi đỗ xe ô tô & xe máy', 'Quầy bắp nước', 'Khu vui chơi', 'Ghế Couple', 'Wi-Fi miễn phí'],
      parking: 'Hầm gửi xe của trung tâm thương mại, miễn phí 3 giờ đầu khi xem phim.',
      transport: 'Xe buýt tuyến 32, 34 dừng ngay trước cổng; cách ga Metro Cầu Giấy khoảng 1,5 km.',
      intro: 'Cơ sở chủ lực của hệ thống với phòng chiếu IMAX và khu vực ghế Couple, nằm ngay trung tâm quận Cầu Giấy.'
    },
    {
      slug: 'thanglong', name: 'CineManager Thăng Long', district: 'Cầu Giấy',
      venue: 'TTTM Big C Thăng Long',
      address: 'TTTM Big C Thăng Long, 222 Trần Duy Hưng, Cầu Giấy, Hà Nội',
      phone: '1900 6017', email: 'thanglong@cinemanager.com', hours: '08:30 - 23:00',
      rooms: 4, seats: 340, formats: ['2D', '3D'],
      facilities: ['Bãi đỗ xe', 'Quầy bắp nước', 'Ghế VIP', 'Wi-Fi miễn phí'],
      parking: 'Bãi xe tầng hầm Big C Thăng Long, có chỗ riêng cho xe máy.',
      transport: 'Nhiều tuyến xe buýt đi qua đường Trần Duy Hưng; gần nút giao Trung Hòa - Nhân Chính.',
      intro: 'Rạp nằm trên trục Trần Duy Hưng sầm uất, thuận tiện cho khách hàng khu vực Cầu Giấy, Thanh Xuân.'
    },
    {
      slug: 'aeon-hadong', name: 'CineManager Aeon Hà Đông', district: 'Hà Đông',
      venue: 'AEON Mall Hà Đông',
      address: 'AEON Mall Hà Đông, Phường Dương Nội, Hà Đông, Hà Nội',
      phone: '1900 6017', email: 'hadong@cinemanager.com', hours: '08:00 - 23:00',
      rooms: 5, seats: 450, formats: ['2D', '3D', '4DX'],
      facilities: ['Bãi đỗ xe rộng', 'Quầy bắp nước', 'Khu ẩm thực', 'Ghế Couple', 'Wi-Fi miễn phí'],
      parking: 'Bãi đỗ xe ô tô và xe máy lớn của AEON Mall, ưu đãi giá cho khách xem phim.',
      transport: 'Cách ga Dương Nội (tuyến Metro số 2A) khoảng 1 km; có xe buýt tuyến 22, 54.',
      intro: 'Cụm rạp quy mô lớn nhất hệ thống, phục vụ khách hàng khu vực Hà Đông và các tỉnh phía Tây.'
    },
    {
      slug: 'royal-city', name: 'CineManager Royal City', district: 'Thanh Xuân',
      venue: 'Vincom Mega Mall Royal City',
      address: 'Tầng B2-VCCA, Vincom Mega Mall Royal City, 72A Nguyễn Trãi, Thanh Xuân, Hà Nội',
      phone: '1900 6017', email: 'royalcity@cinemanager.com', hours: '09:00 - 23:30',
      rooms: 4, seats: 360, formats: ['2D', '3D', 'IMAX'],
      facilities: ['Hầm gửi xe Royal City', 'Quầy bắp nước', 'Ghế VIP', 'Ghế Couple', 'Wi-Fi miễn phí'],
      parking: 'Gửi xe tại hầm B1, B2 Royal City. Giữ vé xe để được hỗ trợ giảm phí.',
      transport: 'Xe buýt tuyến 06, 26, 32 dừng trên đường Nguyễn Trãi; gần ga Metro Thượng Đình (dự kiến).',
      intro: 'Rạp nằm trong tổ hợp Royal City với không gian hiện đại, phù hợp đi cùng gia đình và bạn bè.'
    },
    {
      slug: 'mydinh', name: 'CineManager Mỹ Đình', district: 'Nam Từ Liêm',
      venue: 'TTTM The Garden',
      address: 'Tầng 5, TTTM The Garden, Khu đô thị The Manor, Nam Từ Liêm, Hà Nội',
      phone: '1900 6017', email: 'mydinh@cinemanager.com', hours: '09:00 - 23:00',
      rooms: 4, seats: 320, formats: ['2D', '3D'],
      facilities: ['Bãi đỗ xe', 'Quầy bắp nước', 'Ghế VIP', 'Wi-Fi miễn phí'],
      parking: 'Gửi xe tại tầng hầm TTTM The Garden.',
      transport: 'Gần Sân vận động Quốc gia Mỹ Đình; có xe buýt tuyến 16, 29, 34.',
      intro: 'Cơ sở phục vụ cư dân khu đô thị The Manor, Mỹ Đình và khu vực lân cận.'
    },
    {
      slug: 'danphuong', name: 'CineManager Đan Phượng', district: 'Đan Phượng',
      venue: 'Khu Đô Thị XPHomes',
      address: 'Khu Đô Thị XPHomes, Tân Tây Đô, Đan Phượng, Hà Nội',
      phone: '1900 6017', email: 'danphuong@cinemanager.com', hours: '09:00 - 22:30',
      rooms: 2, seats: 180, formats: ['2D'],
      facilities: ['Bãi đỗ xe miễn phí', 'Quầy bắp nước', 'Wi-Fi miễn phí'],
      parking: 'Bãi đỗ xe ô tô và xe máy miễn phí trong khuôn viên khu đô thị.',
      transport: 'Nằm trên trục đường Tân Tây Đô; di chuyển thuận tiện bằng ô tô và xe máy từ Hà Nội theo QL32.',
      intro: 'Rạp gần gũi, giá tốt, phục vụ khu vực phía Tây Hà Nội.'
    }
  ],

  /* ----------------------------- KHUYẾN MÃI ----------------------------- */
  promotions: [
    {
      id: 'thu-4-vui-ve', title: 'Thứ 4 vui vẻ', short: 'Giảm giá vé, bắp nước',
      icon: '🍿', gradient: 'linear-gradient(135deg,#c1121f,#ff4d4d)', banner: null,
      headline: 'Giảm 20% giá vé & bắp nước mỗi thứ Tư',
      start: '2026-01-01', end: null, when: 'Mỗi thứ Tư hằng tuần',
      audience: 'Tất cả khách hàng có tài khoản CineManager',
      tags: ['Hằng tuần', 'Vé & bắp nước'],
      benefits: ['Giảm 20% giá vé 2D tại tất cả các rạp', 'Giảm 10% cho mọi combo bắp nước', 'Áp dụng cho cả ghế Thường và VIP'],
      conditions: ['Không áp dụng ghế Couple và suất chiếu đặc biệt (IMAX, 4DX)', 'Không cộng dồn với ưu đãi khác', 'Áp dụng khi đặt vé trực tuyến trên website'],
      howto: ['Đăng nhập tài khoản CineManager', 'Chọn phim và suất chiếu vào thứ Tư', 'Ưu đãi được tự động trừ ở bước thanh toán']
    },
    {
      id: 'combo-bap-nuoc', title: 'Combo bắp nước', short: 'Tiết kiệm hơn',
      icon: '🥤', gradient: 'linear-gradient(135deg,#7a1010,#e8a33a)', banner: 'assets/images/banner-combo.png',
      headline: 'Combo bắp + nước tiết kiệm đến 30%',
      start: '2026-01-01', end: null, when: 'Áp dụng quanh năm',
      audience: 'Tất cả khách hàng đặt vé trực tuyến',
      tags: ['Combo', 'Quanh năm'],
      benefits: ['Combo 1 người: 1 bắp + 1 nước', 'Combo 2 người: 1 bắp lớn + 2 nước', 'Combo gia đình: 2 bắp + 4 nước'],
      conditions: ['Nhận combo tại quầy bắp nước khi xuất trình mã vé', 'Không đổi/hoàn combo đã đặt', 'Hương vị bắp có thể thay đổi theo từng rạp'],
      howto: ['Chọn ghế ngồi ở Bước 2', 'Thêm combo mong muốn ở Bước 3', 'Xuất trình mã QR tại quầy để nhận combo']
    },
    {
      id: 'uu-dai-sinh-vien', title: 'Ưu đãi sinh viên', short: 'Giảm 10% cho sinh viên',
      icon: '🎓', gradient: 'linear-gradient(135deg,#1b5fa8,#7fc4ff)', banner: null,
      headline: 'Giảm 10% giá vé cho sinh viên',
      start: '2026-01-01', end: null, when: 'Từ thứ Hai đến thứ Sáu',
      audience: 'Học sinh, sinh viên dưới 25 tuổi',
      tags: ['Sinh viên', 'Ngày thường'],
      benefits: ['Giảm 10% giá vé 2D, 3D', 'Áp dụng tối đa 2 vé/lượt đặt'],
      conditions: ['Xuất trình thẻ HSSV hợp lệ khi nhận vé tại rạp', 'Không áp dụng ngày lễ, Tết', 'Không cộng dồn với ưu đãi khác'],
      howto: ['Đặt vé trực tuyến vào ngày thường', 'Mang theo thẻ HSSV khi đến rạp', 'Nhân viên kiểm tra thẻ trước khi vào phòng chiếu']
    },
    {
      id: 'thanh-vien-moi', title: 'Thành viên mới', short: 'Nhiều quà tặng hấp dẫn',
      icon: 'VIP', gradient: 'linear-gradient(135deg,#d7191f,#d7191f)', banner: null,
      headline: 'Quà chào mừng cho thành viên mới',
      start: '2026-01-01', end: null, when: 'Trong 30 ngày kể từ ngày đăng ký',
      audience: 'Tài khoản mới đăng ký CineManager',
      tags: ['Thành viên', 'Quà tặng'],
      benefits: ['Tặng 50 điểm thưởng khi đăng ký', 'Voucher giảm giá cho lần đặt vé đầu tiên', 'Tặng 1 bắp ngọt khi hoàn tất vé đầu tiên'],
      conditions: ['Mỗi khách hàng chỉ nhận ưu đãi một lần', 'Quà tặng có hiệu lực 30 ngày', 'Hệ thống có quyền thu hồi nếu phát hiện gian lận'],
      howto: ['Đăng ký tài khoản CineManager', 'Đăng nhập và đặt vé đầu tiên', 'Quà tự động được cộng vào tài khoản']
    },
    {
      id: 'flash-deal-black-friday', title: 'Flash Deal Black Friday', short: 'Giảm sốc trong ngày',
      icon: '⚡', gradient: 'linear-gradient(135deg,#111,#e50914)', banner: 'assets/images/uu-dai-flash.png',
      headline: 'Black Friday — Vé phim giá sốc',
      start: '2026-11-27', end: '2026-11-29', when: '27/11 - 29/11/2026',
      audience: 'Tất cả khách hàng',
      tags: ['Sự kiện', 'Số lượng có hạn'],
      benefits: ['Số lượng vé ưu đãi có hạn mỗi ngày', 'Giảm sâu cho suất chiếu khung giờ vàng'],
      conditions: ['Số lượng có hạn, hết là ngừng', 'Không hoàn/hủy vé khuyến mãi', 'Không áp dụng đồng thời với mã giảm giá khác'],
      howto: ['Theo dõi website vào 0h ngày 27/11', 'Chọn suất chiếu có nhãn Flash Deal', 'Thanh toán trong thời gian giữ ghế']
    },
    {
      id: 'cine-party-combo', title: 'Cine Party Combo', short: 'Mua 2 tặng 1',
      icon: '🎉', gradient: 'linear-gradient(135deg,#6a11cb,#ff4d4d)', banner: 'assets/images/uu-dai-party.png',
      headline: 'Mua 2 vé tặng 1 vé cho nhóm bạn',
      start: '2026-11-01', end: '2026-12-31', when: '01/11 - 31/12/2026',
      audience: 'Nhóm từ 3 người trở lên',
      tags: ['Nhóm bạn', 'Có thời hạn'],
      benefits: ['Mua 2 tặng 1 vé 2D cùng suất', 'Kèm combo party ưu đãi'],
      conditions: ['Vé tặng là vé có giá thấp nhất trong 3 vé', 'Áp dụng cho suất chiếu từ thứ Hai đến thứ Năm'],
      howto: ['Chọn đủ 3 ghế cùng suất', 'Ưu đãi tự động áp dụng ở bước xác nhận']
    }
  ],

  /* ------------------------------ THÀNH VIÊN ------------------------------ */
  member: {
    earnRule: 'Mỗi 10.000đ chi tiêu (vé và bắp nước) tích lũy 1 điểm',
    tiers: [
      { key: 'Bronze', name: 'Thường', en: 'Bronze', min: 0, color: '#cd7f32',
        perks: ['Tích điểm 1 điểm / 10.000đ', 'Quà chào mừng thành viên mới', 'Nhận thông tin khuyến mãi sớm'] },
      { key: 'Silver', name: 'Bạc', en: 'Silver', min: 500, color: '#c0c0c0',
        perks: ['Tích điểm 1,2 điểm / 10.000đ', 'Tặng 1 bắp ngọt mỗi quý', 'Ưu tiên đặt vé suất chiếu sớm'] },
      { key: 'Gold', name: 'Vàng', en: 'Gold', min: 1500, color: '#ffc107',
        perks: ['Tích điểm 1,5 điểm / 10.000đ', 'Tặng vé sinh nhật', 'Giảm 5% combo bắp nước'] },
      { key: 'Platinum', name: 'Bạch kim', en: 'Platinum', min: 3000, color: '#7fd1ff',
        perks: ['Tích điểm 2 điểm / 10.000đ', 'Tặng 2 vé mỗi năm', 'Giảm 10% combo, ưu tiên hỗ trợ 1:1'] }
    ],
    rewards: [
      { name: 'Bắp ngọt size M', points: 150 },
      { name: 'Nước ngọt size L', points: 120 },
      { name: 'Combo 1 người (bắp + nước)', points: 250 },
      { name: 'Vé 2D tiêu chuẩn', points: 600 },
      { name: 'Combo gia đình', points: 900 }
    ]
  },

  /* ------------------------------ TIN TỨC / TUYỂN DỤNG ------------------------------ */
  news: [
    { id: 'khai-truong', date: '2026-11-01', tag: 'Hệ thống rạp', title: 'CineManager mở rộng hệ thống lên 6 cơ sở tại Hà Nội',
      summary: 'Hệ thống rạp chiếu phim CineManager hiện đã có mặt tại Cầu Giấy, Hà Đông, Thanh Xuân, Nam Từ Liêm và Đan Phượng.',
      body: ['Với 6 cơ sở trải dài từ trung tâm đến các khu đô thị phía Tây, CineManager mong muốn mang trải nghiệm điện ảnh hiện đại đến gần hơn với khán giả Thủ đô.',
             'Tất cả cơ sở đều hỗ trợ đặt vé trực tuyến, chọn ghế theo thời gian thực và nhận mã QR ngay sau khi thanh toán.'] },
    { id: 'black-friday', date: '2026-11-10', tag: 'Khuyến mãi', title: 'Flash Deal Black Friday: vé phim giá sốc từ 27/11',
      summary: 'Chương trình ưu đãi lớn nhất năm với số lượng vé có hạn mỗi ngày.',
      body: ['Từ 27/11 đến 29/11/2026, khách hàng có thể săn vé giá ưu đãi ở các khung giờ vàng.',
             'Hãy đăng nhập sẵn tài khoản để thao tác nhanh nhất khi chương trình bắt đầu.'] },
    { id: 'tich-diem', date: '2026-10-20', tag: 'Thành viên', title: 'Chương trình tích điểm đổi quà: lên hạng nhanh hơn',
      summary: 'Thành viên được cộng điểm trên mỗi giao dịch và đổi quà ngay tại quầy.',
      body: ['Điểm thưởng được tính trên tổng giá trị vé và bắp nước. Khi đạt mốc điểm, tài khoản tự động nâng hạng.',
             'Xem chi tiết các hạng và quyền lợi tại mục Thành viên.'] },
    { id: 'cap-nhat-web', date: '2026-10-05', tag: 'Công nghệ', title: 'Giữ ghế 5 phút và vé điện tử QR trên website mới',
      summary: 'Tính năng giữ ghế tạm thời giúp bạn thanh toán thoải mái, không lo mất chỗ.',
      body: ['Sau khi chọn ghế, hệ thống giữ chỗ trong 5 phút để bạn hoàn tất thanh toán.',
             'Vé điện tử kèm mã QR được lưu trong mục “Vé của tôi”.'] }
  ],

  jobs: [
    { id: 'fe', title: 'Lập trình viên Front-end', place: 'Trụ sở - Nam Từ Liêm', type: 'Toàn thời gian', desc: 'Phát triển giao diện đặt vé và trang quản trị bằng HTML/CSS/JavaScript.' },
    { id: 'be', title: 'Lập trình viên PHP/MySQL', place: 'Trụ sở - Nam Từ Liêm', type: 'Toàn thời gian', desc: 'Xây dựng API, tối ưu cơ sở dữ liệu cho hệ thống đặt vé.' },
    { id: 'cs', title: 'Nhân viên Chăm sóc khách hàng', place: 'Hà Nội', type: 'Toàn thời gian', desc: 'Hỗ trợ khách hàng qua hotline và email, xử lý góp ý.' },
    { id: 'staff', title: 'Nhân viên rạp (bán vé, quầy bắp nước)', place: 'Các cơ sở rạp', type: 'Bán thời gian / Toàn thời gian', desc: 'Phục vụ khách hàng tại quầy vé, quầy bắp nước và soát vé.' }
  ],

  /* Mục con của các dropdown (tab trong trang) */
  memberLinks: [
    { tab: 'benefits', title: 'Quyền lợi thành viên', sub: 'Nhiều ưu đãi đặc quyền', icon: 'crown', color: '#ffc107' },
    { tab: 'points',   title: 'Tích điểm đổi quà',    sub: 'Xem điểm của bạn',        icon: 'coins' },
    { tab: 'ranking',  title: 'Xếp hạng thành viên',  sub: 'Silver - Gold - Platinum', icon: 'bars' },
    { tab: 'register', title: 'Đăng ký thành viên',   sub: 'Tham gia ngay',           icon: 'user-plus' }
  ],
  aboutLinks: [
    { tab: 'intro',   title: 'Giới thiệu', sub: 'CineManager là ai?',        icon: 'file' },
    { tab: 'news',    title: 'Tin tức',    sub: 'Cập nhật mới nhất',         icon: 'news' },
    { tab: 'careers', title: 'Tuyển dụng', sub: 'Gia nhập đội ngũ',          icon: 'briefcase' },
    { tab: 'contact', title: 'Liên hệ',    sub: 'Mọi thắc mắc, góp ý',       icon: 'mail' }
  ]
};
