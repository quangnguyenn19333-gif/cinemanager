/* =====================================================================
 * SITE-HEADER.JS — Header + Footer dùng chung cho mọi trang khách hàng.
 * Cách dùng: đặt  <script src="assets/js/site-data.js"></script>
 *                 <script src="assets/js/site-header.js"></script>
 * ngay tại vị trí đầu <body>. Header được chèn đúng chỗ thẻ script.
 * Mọi liên kết (kể cả dropdown) được sinh từ site-data.js nên chỉ sửa 1 nơi.
 * ===================================================================== */
(function () {
  'use strict';

  var S = window.SITE || { cinemas: [], promotions: [], memberLinks: [], aboutLinks: [], company: {} };
  var page = (location.pathname.split('/').pop() || 'index.html').toLowerCase();
  var ACTIVE = {
    '': 'home', 'index.html': 'home',
    'cinemas.html': 'cinemas', 'promotions.html': 'promos',
    'membership.html': 'member', 'about.html': 'about'
  };
  var active = ACTIVE[page] || '';

  var esc = function (s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var ic = function (id, cls) { return '<svg class="ic ' + (cls || '') + '"><use href="#i-' + id + '"/></svg>'; };

  /* ---------- SVG sprite ---------- */
  var SPRITE = '<svg width="0" height="0" style="position:absolute" aria-hidden="true">' +
    '<symbol id="i-search" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></symbol>' +
    '<symbol id="i-bell" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></symbol>' +
    '<symbol id="i-chev-down" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></symbol>' +
    '<symbol id="i-chev-right" viewBox="0 0 24 24"><path d="M9 6l6 6-6 6"/></symbol>' +
    '<symbol id="i-x" viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></symbol>' +
    '<symbol id="i-menu" viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18"/></symbol>' +
    '<symbol id="i-gift" viewBox="0 0 24 24"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M19 12v9H5v-9M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8s1-5 4.5-5a2.5 2.5 0 0 1 0 5"/></symbol>' +
    '<symbol id="i-pin" viewBox="0 0 24 24"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></symbol>' +
    '<symbol id="i-film" viewBox="0 0 24 24"><rect x="2" y="2" width="20" height="20" rx="2.2"/><path d="M7 2v20M17 2v20M2 12h20M2 7h5M2 17h5M17 17h5M17 7h5"/></symbol>' +
    '<symbol id="i-ticket" viewBox="0 0 24 24"><path d="M2 9a3 3 0 0 1 0 6v2a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-2a3 3 0 0 1 0-6V7a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2z"/><path d="M13 5v2M13 11v2M13 17v2"/></symbol>' +
    '<symbol id="i-users" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/></symbol>' +
    '<symbol id="i-crown" viewBox="0 0 24 24"><path d="M3 18h18M4 18L2 7l5 4 5-7 5 7 5-4-2 11"/></symbol>' +
    '<symbol id="i-coins" viewBox="0 0 24 24"><ellipse cx="12" cy="6" rx="8" ry="3"/><path d="M4 6v6c0 1.7 3.6 3 8 3s8-1.3 8-3V6M4 12v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></symbol>' +
    '<symbol id="i-bars" viewBox="0 0 24 24"><path d="M5 21V12M12 21V4M19 21v-6" stroke-width="3.5"/></symbol>' +
    '<symbol id="i-user-plus" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6M22 11h-6"/></symbol>' +
    '<symbol id="i-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></symbol>' +
    '<symbol id="i-file" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8"/></symbol>' +
    '<symbol id="i-news" viewBox="0 0 24 24"><path d="M4 22h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8M15 18h-5M10 6h8v4h-8z"/></symbol>' +
    '<symbol id="i-briefcase" viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></symbol>' +
    '<symbol id="i-mail" viewBox="0 0 24 24"><path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><path d="M22 6l-10 7L2 6"/></symbol>' +
    '<symbol id="i-phone" viewBox="0 0 24 24"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/></symbol>' +
    '<symbol id="i-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></symbol>' +
    '<symbol id="i-user" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></symbol>' +
    '<symbol id="i-key" viewBox="0 0 24 24"><circle cx="7.5" cy="15.5" r="4.5"/><path d="M10.7 12.3L21 2M16 7l3 3M14 9l2 2"/></symbol>' +
    '<symbol id="i-gear" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></symbol>' +
    '<symbol id="i-logout" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></symbol>' +
    '<symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></symbol>' +
    '<symbol id="i-chart" viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M7 14l4-4 3 3 5-6"/></symbol>' +
    '</svg>';

  /* ---------- Helpers dựng menu ---------- */
  function megaItem(href, inner) {
    return '<a class="mega-item" href="' + href + '">' + inner + '</a>';
  }
  function megaCard(headIcon, title, headHref, itemsHtml, allHref, allIcon, allText) {
    return '<div class="mega"><div class="mega-card">' +
      '<a class="mega-head" href="' + headHref + '">' + ic(headIcon) + '<span>' + title + '</span>' + ic('chev-right', 'go') + '</a>' +
      itemsHtml +
      (allHref ? '<a class="mega-all" href="' + allHref + '">' + ic(allIcon) + allText + '</a>' : '') +
      '</div></div>';
  }
  function navItem(key, label, href, mega) {
    var cls = 'nav-link' + (active === key ? ' active-link' : '');
    return '<div class="nav-item"><a href="' + href + '" class="' + cls + '">' + label + (mega ? ic('chev-down', 'chev') : '') + '</a>' + (mega || '') + '</div>';
  }

  /* Hệ thống rạp */
  var cinemaItems = S.cinemas.map(function (c) {
    return megaItem('cinemas.html?id=' + encodeURIComponent(c.slug),
      '<span class="thumb">📍</span><span class="mi-text"><b>' + esc(c.name) + '</b><small>' + esc(c.district) + '</small></span>');
  }).join('');

  /* Khuyến mãi (4 mục đầu hiển thị trong dropdown) */
  var promoItems = S.promotions.slice(0, 4).map(function (p) {
    var big = String(p.icon).length > 2;
    return megaItem('promotions.html?id=' + encodeURIComponent(p.id),
      '<span class="thumb" style="background:' + p.gradient + ';font-size:' + (big ? 20 : 22) + 'px">' + esc(p.icon) + '</span>' +
      '<span class="mi-text"><b>' + esc(p.title) + '</b><small>' + esc(p.short) + '</small></span>');
  }).join('');

  /* Thành viên / Về chúng tôi */
  function tabItems(file, list) {
    return list.map(function (l) {
      return megaItem(file + '?tab=' + l.tab,
        '<span class="mi-ico"' + (l.color ? ' style="color:' + l.color + '"' : '') + '>' + ic(l.icon) + '</span>' +
        '<span class="mi-text"><b>' + esc(l.title) + '</b><small>' + esc(l.sub) + '</small></span>');
    }).join('');
  }

  /* Quản trị (chỉ hiện với admin) */
  var adminMega = '<div class="mega"><div class="mega-card">' +
    '<a class="mega-head" href="dashboard.html">' + ic('shield') + '<span>Quản trị</span>' + ic('chev-right', 'go') + '</a>' +
    megaItem('dashboard.html', '<span class="mi-ico">' + ic('chart') + '</span><span class="mi-text"><b>Thống kê doanh thu</b><small>Dashboard tổng quan</small></span>') +
    megaItem('admin_movies.html', '<span class="mi-ico">' + ic('film') + '</span><span class="mi-text"><b>Quản lý phim</b><small>Thêm, sửa, xóa phim</small></span>') +
    megaItem('showtimes.html', '<span class="mi-ico">' + ic('clock') + '</span><span class="mi-text"><b>Quản lý suất chiếu</b><small>Lịch chiếu & giá vé</small></span>') +
    '</div></div>';

  var nav =
    navItem('home', 'Trang chủ', 'index.html') +
    navItem('cinemas', 'Hệ thống rạp', 'cinemas.html', megaCard('film', 'Hệ thống rạp', 'cinemas.html', cinemaItems, 'cinemas.html', 'pin', 'Xem tất cả rạp')) +
    navItem('promos', 'Khuyến mãi', 'promotions.html', megaCard('gift', 'Khuyến mãi', 'promotions.html', promoItems, 'promotions.html', 'ticket', 'Xem tất cả khuyến mãi')) +
    navItem('member', 'Thành viên', 'membership.html', megaCard('users', 'Thành viên', 'membership.html', tabItems('membership.html', S.memberLinks))) +
    navItem('about', 'Về chúng tôi', 'about.html', megaCard('info', 'Về chúng tôi', 'about.html', tabItems('about.html', S.aboutLinks))) +
    '<div class="nav-item hidden" id="admin-menu"><a href="dashboard.html" class="nav-link">Quản trị' + ic('chev-down', 'chev') + '</a>' + adminMega + '</div>';

  /* Menu mobile (accordion) */
  function mGroup(label, href, links) {
    return '<details class="m-group"><summary>' + label + ic('chev-down', 'chev') + '</summary>' +
      '<a class="m-all" href="' + href + '">Xem tất cả</a>' + links + '</details>';
  }
  function mLinks(arr) { return arr.map(function (x) { return '<a href="' + x[0] + '">' + x[1] + '</a>'; }).join(''); }
  var mobileNav = '<nav class="mobile-nav" id="mobile-nav" aria-label="Menu di động">' +
    '<a class="m-link" href="index.html">Trang chủ</a>' +
    mGroup('Hệ thống rạp', 'cinemas.html', mLinks(S.cinemas.map(function (c) { return ['cinemas.html?id=' + c.slug, esc(c.name)]; }))) +
    mGroup('Khuyến mãi', 'promotions.html', mLinks(S.promotions.map(function (p) { return ['promotions.html?id=' + p.id, esc(p.title)]; }))) +
    mGroup('Thành viên', 'membership.html', mLinks(S.memberLinks.map(function (l) { return ['membership.html?tab=' + l.tab, esc(l.title)]; }))) +
    mGroup('Về chúng tôi', 'about.html', mLinks(S.aboutLinks.map(function (l) { return ['about.html?tab=' + l.tab, esc(l.title)]; }))) +
    '<a class="m-link" href="index.html#movie-grid">Tìm phim</a>' +
    '<div class="hidden" id="m-admin"><a class="m-link" href="dashboard.html">Quản trị</a></div>' +
    '</nav>';

  /* Chuông thông báo: liệt kê ưu đãi mới nhất */
  var notifItems = S.promotions.slice(-3).reverse().map(function (p) {
    return '<a href="promotions.html?id=' + encodeURIComponent(p.id) + '"><span class="n-ico" style="background:' + p.gradient + '">' + (String(p.icon).length > 2 ? '★' : esc(p.icon)) + '</span>' +
      '<span><b>' + esc(p.title) + '</b><small>' + esc(p.when) + '</small></span></a>';
  }).join('');

  var headerHtml = SPRITE +
    '<header class="main-header"><div class="header-container">' +
    '<button type="button" class="menu-toggle" id="menu-toggle" aria-label="Mở menu" aria-expanded="false" aria-controls="mobile-nav">' + ic('menu') + '</button>' +
    '<a href="index.html" class="logo"><span class="cine">CINE</span>MANAGER</a>' +
    '<nav class="main-nav" aria-label="Menu chính">' + nav + '</nav>' +
    '<div class="header-right">' +
      '<div class="header-search" id="header-search">' + ic('search') +
        '<input type="text" id="search-movie" placeholder="Tìm phim, diễn viên..." autocomplete="off">' +
        '<button type="button" class="search-clear" id="search-clear" aria-label="Xóa">' + ic('x') + '</button>' +
        '<div class="search-suggest" id="search-suggest"></div></div>' +
      '<a href="login.html" class="header-login hidden" id="header-login">Đăng nhập</a>' +
      '<div id="user-info" class="header-auth hidden">' +
        '<div class="notif-menu" id="notif-menu">' +
          '<button type="button" class="icon-btn notification" id="notif-btn" aria-label="Thông báo" aria-expanded="false">' + ic('bell') + '<i class="dot" id="notif-dot"></i></button>' +
          '<div class="notif-dropdown"><div class="notif-head">Ưu đãi mới nhất</div>' + notifItems +
          '<a class="notif-all" href="promotions.html">Xem tất cả khuyến mãi</a></div></div>' +
        '<div class="user-menu" id="user-menu">' +
          '<button type="button" class="user-trigger" aria-haspopup="true" aria-expanded="false">' +
            '<div class="avatar" id="user-avatar">N</div><span id="display-username"></span>' + ic('chev-down', 'chev') + '</button>' +
          '<div class="user-dropdown">' +
            '<a href="my-tickets.html" id="my-tickets-link">' + ic('ticket') + 'Vé của tôi</a>' +
            '<a href="account.html?tab=password" class="red">' + ic('key') + 'Đổi mật khẩu</a>' +
            '<a href="account.html">' + ic('gear') + 'Cài đặt tài khoản</a>' +
            '<hr>' +
            '<a href="login.html" class="red logout" onclick="handleLogout(); return false;">' + ic('logout') + 'Đăng xuất</a>' +
          '</div></div>' +
      '</div>' +
    '</div></div>' + mobileNav + '</header>';

  /* ---------- Chèn header đúng vị trí thẻ script ---------- */
  var me = document.currentScript;
  if (me) me.insertAdjacentHTML('beforebegin', headerHtml);
  else document.body.insertAdjacentHTML('afterbegin', headerHtml);

  /* ---------- Xác thực (dùng chung cho các trang) ---------- */
  window.CM_AUTH = fetch('api/auth.php?action=check', { credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .catch(function () { return { isLoggedIn: false }; });

  window.CM_AUTH.then(function (d) {
    var $ = function (id) { return document.getElementById(id); };
    if (d && d.isLoggedIn) {
      $('user-info').classList.remove('hidden');
      var nameEl = $('display-username');
      if (nameEl) nameEl.textContent = d.username + (d.role === 'admin' ? ' (Admin)' : '');
      if (d.role === 'admin') {
        $('admin-menu').classList.remove('hidden');
        $('m-admin').classList.remove('hidden');
        var t = $('my-tickets-link'); if (t) t.classList.add('hidden');
      }
    } else {
      $('header-login').classList.remove('hidden');
    }
  });

  /* ---------- Tương tác: menu mobile, chuông ---------- */
  document.addEventListener('DOMContentLoaded', function () {
    var toggle = document.getElementById('menu-toggle');
    var mnav = document.getElementById('mobile-nav');
    if (toggle && mnav) {
      var setM = function (open) {
        mnav.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open);
        toggle.innerHTML = ic(open ? 'x' : 'menu');
      };
      toggle.addEventListener('click', function (e) { e.stopPropagation(); setM(!mnav.classList.contains('open')); });
      mnav.addEventListener('click', function (e) { if (e.target.closest('a')) setM(false); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setM(false); });
      document.addEventListener('click', function (e) { if (!mnav.contains(e.target) && !toggle.contains(e.target)) setM(false); });
    }

    var nm = document.getElementById('notif-menu');
    var nb = document.getElementById('notif-btn');
    var dot = document.getElementById('notif-dot');
    if (nm && nb) {
      var seen = false;
      try { seen = localStorage.getItem('cm_notif_seen') === (S.promotions.length + ''); } catch (e) {}
      if (seen && dot) dot.style.display = 'none';
      nb.addEventListener('click', function (e) {
        e.stopPropagation();
        var open = !nm.classList.contains('open');
        nm.classList.toggle('open', open);
        nb.setAttribute('aria-expanded', open);
        if (open && dot) {
          dot.style.display = 'none';
          try { localStorage.setItem('cm_notif_seen', S.promotions.length + ''); } catch (e2) {}
        }
      });
      document.addEventListener('click', function (e) { if (!nm.contains(e.target)) nm.classList.remove('open'); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') nm.classList.remove('open'); });
    }
  });

  /* ---------- Footer dùng chung (bỏ qua nếu trang đã có footer) ---------- */
  document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelector('.cinema-footer')) return;
    var c = S.company;
    var footer = document.createElement('footer');
    footer.className = 'cinema-footer';
    footer.innerHTML =
      '<div class="cinema-types"><span>4DX</span><span>IMAX</span><span>GOLD CLASS</span><span>SWEETBOX</span><span>PREMIUM CINEMA</span><span>SCREENX</span></div>' +
      '<div class="footer-content">' +
        '<div class="footer-col"><h3>CineManager Việt Nam</h3>' +
          '<p>CineManager Việt Nam là hệ thống đặt vé và quản lý rạp chiếu phim hiện đại tại Việt Nam.</p></div>' +
        '<div class="footer-col"><h3>Khám phá</h3><ul class="footer-links">' +
          '<li><a href="cinemas.html">Hệ thống rạp</a></li><li><a href="promotions.html">Khuyến mãi</a></li>' +
          '<li><a href="membership.html">Thành viên</a></li><li><a href="about.html?tab=news">Tin tức</a></li>' +
          '<li><a href="about.html?tab=careers">Tuyển dụng</a></li></ul></div>' +
        '<div class="footer-col"><h3>Chăm sóc khách hàng</h3>' +
          '<p>Hotline: ' + esc(c.hotline) + '</p><p>Giờ hoạt động: ' + esc(c.supportHours) + '</p>' +
          '<p>Email: ' + esc(c.email) + '</p><p><a href="about.html?tab=contact">Gửi liên hệ / góp ý</a></p></div>' +
        '<div class="footer-col"><h3>' + esc(c.name) + '</h3><p>' + esc(c.license) + '.</p><p>Địa chỉ: ' + esc(c.address) + '.</p></div>' +
      '</div>' +
      '<div class="footer-bottom"><p>© 2026 CineManager. All Rights Reserved.</p></div>';
    document.body.appendChild(footer);
  });
})();
