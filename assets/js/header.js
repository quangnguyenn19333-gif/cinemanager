document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.main-header');
    const box = document.getElementById('header-search');
    const input = document.getElementById('search-movie');
    const sg = document.getElementById('search-suggest');
    const clearBtn = document.getElementById('search-clear');
    const userMenu = document.getElementById('user-menu');
    if (!header || !box || !input || !sg || !userMenu) return;

    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    // ---------- Gợi ý tìm kiếm (lấy từ api/movies.php) ----------
    let moviesPromise = null;
    const loadMovies = () => moviesPromise ||= fetch('api/movies.php')
        .then(r => r.json()).then(d => Array.isArray(d) ? d : []).catch(() => []);

    const render = async () => {
        const q = input.value.trim();
        if (!q) { sg.innerHTML = ''; return; }
        const movies = await loadMovies();
        if (input.value.trim() !== q) return; // đã gõ tiếp
        const kw = q.toLowerCase();
        const list = movies.filter(m => (m.title || '').toLowerCase().includes(kw) || (m.genre || '').toLowerCase().includes(kw)).slice(0, 4);
        const items = list.map(m => {
            const year = (String(m.release_date || '').match(/\d{4}/) || [''])[0];
            const meta = [year, m.genre].filter(Boolean).join(' • ');
            return `<a class="sg-item" href="booking.html?movie_id=${encodeURIComponent(m.id)}">
                <img src="${esc(m.poster_url || '')}" alt="" onerror="this.style.visibility='hidden'">
                <span><b>${esc(m.title)}</b><small>${esc(meta)}</small></span></a>`;
        }).join('') || `<div class="sg-empty">Không tìm thấy phim phù hợp</div>`;
        sg.innerHTML = items + `<a class="sg-all" href="index.html?q=${encodeURIComponent(q)}#movie-grid"><svg class="ic"><use href="#i-search"/></svg>Xem tất cả kết quả cho “${esc(q)}”</a>`;
    };

    // Ở trang chủ: cuộn tới lưới phim. Ở trang khác: chuyển về trang chủ kèm từ khóa.
    const goToResults = () => {
        input.blur();
        const grid = document.getElementById('movie-grid');
        if (grid) grid.scrollIntoView({behavior: 'smooth', block: 'start'});
        else {
            const q = input.value.trim();
            window.location.href = 'index.html' + (q ? '?q=' + encodeURIComponent(q) : '') + '#movie-grid';
        }
    };

    input.addEventListener('focus', () => { header.classList.add('searching'); box.classList.add('open'); render(); });
    input.addEventListener('blur', () => { header.classList.remove('searching'); box.classList.remove('open'); });
    input.addEventListener('input', () => { box.classList.toggle('has-text', !!input.value); render(); });
    input.addEventListener('keydown', e => {
        if (e.key === 'Enter') { e.preventDefault(); goToResults(); }
        if (e.key === 'Escape') input.blur();
    });
    // giữ focus khi bấm vào panel gợi ý / nút xóa
    [sg, clearBtn].forEach(el => el.addEventListener('mousedown', e => e.preventDefault()));
    sg.addEventListener('click', e => { if (e.target.closest('.sg-all')) { e.preventDefault(); goToResults(); } });
    clearBtn.addEventListener('click', () => {
        input.value = '';
        box.classList.remove('has-text');
        input.dispatchEvent(new Event('input')); // để script.js lọc lại danh sách phim
        input.focus();
    });

    // Nhận từ khóa từ ?q=... (khi tìm kiếm từ trang khác chuyển về trang chủ)
    const initQ = new URLSearchParams(location.search).get('q');
    if (initQ) {
        input.value = initQ;
        box.classList.add('has-text');
        if (document.getElementById('movie-grid')) input.dispatchEvent(new Event('input'));
    }

    // ---------- Menu tài khoản (click) ----------
    const trigger = userMenu.querySelector('.user-trigger');
    const setOpen = open => { userMenu.classList.toggle('open', open); trigger.setAttribute('aria-expanded', open); };
    trigger.addEventListener('click', e => { e.stopPropagation(); setOpen(!userMenu.classList.contains('open')); });
    document.addEventListener('click', e => { if (!userMenu.contains(e.target)) setOpen(false); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') setOpen(false); });
    userMenu.querySelectorAll('.user-dropdown a').forEach(a => a.addEventListener('click', () => setOpen(false)));

    // ---------- Avatar lấy chữ cái đầu (tên được ghi vào #display-username) ----------
    const nameEl = document.getElementById('display-username');
    const avatar = document.getElementById('user-avatar');
    if (nameEl && avatar) {
        const sync = () => { avatar.textContent = (nameEl.textContent.trim().charAt(0) || 'N').toUpperCase(); };
        new MutationObserver(sync).observe(nameEl, {childList: true, characterData: true, subtree: true});
        sync();
    }
});
// Ghi chú: hiển thị menu "Quản trị" cho admin hiện do assets/js/site-header.js xử lý (dựa trên api/auth.php?action=check).
