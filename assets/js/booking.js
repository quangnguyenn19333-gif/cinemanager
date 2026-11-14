// =============================================
// BOOKING.JS - Logic đặt vé rạp phim (Đã tích hợp thanh ngày tháng động)
// =============================================

const params = new URLSearchParams(window.location.search);
const MOVIE_ID = params.get('movie_id');

// Trạng thái toàn cục
let state = {
    movie: null,
    selectedShowtime: null,
    selectedSeats: [],      // [{id, row, number, type, price}]
    selectedCombos: {},     // {combo_id: {combo, qty}}
    holdExpiresAt: null,
    countdownInterval: null,
    seatRefreshInterval: null,
    allShowtimes: [],       // Lưu trữ toàn bộ suất chiếu để lọc theo ngày
    selectedDateStr: null,
    cinemas: [],            // Thêm biến lưu danh sách rạp
    selectedCinemaId: null  // Thêm biến lưu rạp đang chọn
};

// =============================================
// KHỞI TẠO
// =============================================
document.addEventListener('DOMContentLoaded', async () => {
    // Kiểm tra đăng nhập
    const authRes = await fetch('api/auth.php?action=check');
    const authData = await authRes.json();
    if (!authData.isLoggedIn) { window.location.href = 'login.html'; return; }
    if (authData.role === 'admin') {
        alert('Tài khoản Admin không thể đặt vé.');
        window.location.href = 'index.html';
        return;
    }
    document.getElementById('display-username').innerText = authData.username;

    if (!MOVIE_ID) { alert('Thiếu thông tin phim!'); window.location.href = 'index.html'; return; }

    // Lấy thông tin phim
    const movRes = await fetch('api/movies.php');
    const movies = await movRes.json();
    state.movie = movies.find(m => m.id == MOVIE_ID);

    if (!state.movie) { alert('Phim không tồn tại!'); window.location.href = 'index.html'; return; }

    renderMovieBanner();
    await loadCinemas();
    renderDateTabs(); // Khởi tạo thanh ngày tháng động
    await loadShowtimes();
    loadCombos();
});

// =============================================
// BƯỚC 1: SUẤT CHIẾU & THANH NGÀY THÁNG ĐỘNG
// =============================================
// BƯỚC 1: SUẤT CHIẾU & THANH NGÀY THÁNG ĐỘNG
function renderMovieBanner() {
    document.getElementById('movie-info-banner').innerHTML = `
        <img src="${state.movie.poster_url || ''}" alt="${state.movie.title}" onclick="openMovieDetailModal()" style="cursor: pointer;" title="Xem chi tiết phim">
        <div>
            <h3>${state.movie.title}</h3>
            <p>🎭 ${state.movie.genre}</p>
            <p>⏰ ${state.movie.duration} phút</p>
            <button onclick="openMovieDetailModal()" style="margin-top: 10px; background-color: #e50914; color: white; border: none; padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s;" onmouseover="this.style.background='#b80710'" onmouseout="this.style.background='#e50914'">
                <i class="fa-solid fa-circle-info"></i> Chi tiết phim
            </button>
        </div>
    `;
}
// =============================================
// LẤY DANH SÁCH CƠ SỞ RẠP TỪ DATABASE
// =============================================
async function loadCinemas() {
    try {
        const res = await fetch('api/cinemas.php');
        const cinemas = await res.json();
        state.cinemas = cinemas;

        const selectEl = document.getElementById('cinema-select');
        const addressEl = document.getElementById('cinema-address');

        if (!cinemas.length) {
            selectEl.innerHTML = '<option value="">Hệ thống đang bảo trì, chưa có rạp</option>';
            return;
        }

        // Tạo các option cho thẻ select
        selectEl.innerHTML = cinemas.map(c => `<option value="${c.id}">${c.name}</option>`).join('');

        // Mặc định chọn rạp đầu tiên
        if (cinemas.length > 0) {
            selectEl.value = cinemas[0].id;
            state.selectedCinemaId = cinemas[0].id;
            addressEl.innerText = '📍 ' + cinemas[0].address;
        }
    } catch (error) {
        console.error("Lỗi khi tải danh sách rạp:", error);
    }
}

// Xử lý khi người dùng chọn một rạp khác
function changeCinema() {
    const selectEl = document.getElementById('cinema-select');
    const addressEl = document.getElementById('cinema-address');
    
    state.selectedCinemaId = selectEl.value;
    
    // Tìm địa chỉ của rạp vừa chọn để hiển thị
    const selectedCinema = state.cinemas.find(c => c.id == state.selectedCinemaId);
    if (selectedCinema) {
        addressEl.innerText = '📍 ' + selectedCinema.address;
    }

    // GỌI LẠI HÀM LỌC SUẤT CHIẾU ĐỂ UPDATE DANH SÁCH SUẤT CHIẾU
    //khi đổi rạp thì danh sách suất chiếu cũng phải đổi theo
    // do dữ liệu suất chiếu đang fix sẵn, chúng ta có thể load lại hoặc thông báo
    displayShowtimesForSelectedDate(); 
}
// Tự động sinh ra 7 ngày liên tiếp từ ngày hiện tại
function renderDateTabs() {
    const container = document.getElementById('date-tabs');
    if (!container) return;
    container.innerHTML = '';

    const daysOfWeek = ["CN", "T2", "T3", "T4", "T5", "T6", "T7"];
    const today = new Date();

    for (let i = 0; i < 7; i++) {
        const targetDate = new Date(today);
        targetDate.setDate(today.getDate() + i);

        const dayNum = String(targetDate.getDate()).padStart(2, '0');
        const monthNum = String(targetDate.getMonth() + 1).padStart(2, '0');
        const dayOfWeek = daysOfWeek[targetDate.getDay()];
        const fullDateStr = `${targetDate.getFullYear()}-${monthNum}-${dayNum}`;

        // Mặc định chọn ngày đầu tiên (hôm nay)
        if (i === 0) state.selectedDateStr = fullDateStr;

        const li = document.createElement('li');
        li.className = `date-item ${i === 0 ? 'active' : ''}`;
        li.setAttribute('data-date', fullDateStr);
        li.innerHTML = `
            <span class="date-number">${dayNum}</span>
            <span class="date-month">/${monthNum} - ${dayOfWeek}</span>
        `;

        li.addEventListener('click', function() {
            document.querySelectorAll('.date-item').forEach(el => el.classList.remove('active'));
            this.classList.add('active');
            state.selectedDateStr = this.getAttribute('data-date');
            displayShowtimesForSelectedDate();
        });

        container.appendChild(li);
    }
}

async function loadShowtimes() {
    const res = await fetch(`api/showtimes.php?movie_id=${MOVIE_ID}`);
    const showtimes = await res.json();
    state.allShowtimes = showtimes;

    displayShowtimesForSelectedDate();
}

// Lọc và hiển thị suất chiếu ứng với ngày và rạp đang chọn
function displayShowtimesForSelectedDate() {
    const grid = document.getElementById('showtimes-grid');
    if (!grid) return;

    const filteredList = state.allShowtimes.filter(st => {
        // 1. Điều kiện 1: Trùng ngày
        let matchDate = st.show_date === state.selectedDateStr;
        
        // 2. Điều kiện 2: Trùng cơ sở rạp (nếu có chọn rạp)
        let matchCinema = true;
        if (state.selectedCinemaId && state.selectedCinemaId !== 'all') {
            matchCinema = st.cinema_id == state.selectedCinemaId;
        }

        // Phải thỏa mãn cả 2 điều kiện mới lấy suất chiếu đó
        return matchDate && matchCinema;
    });

    if (!filteredList.length) {
        grid.innerHTML = '<p style="color:var(--text-muted); grid-column: 1/-1; text-align: center; padding: 2rem;">Hiện không có suất chiếu nào vào ngày này tại rạp đã chọn.</p>';
        return;
    }

    let html = '';
    filteredList.forEach(st => {
        html += `
            <div class="showtime-card" id="st-${st.id}" onclick="selectShowtime(${st.id})">
                <div class="time">${st.show_time.slice(0,5)}</div>
                <div class="room">${st.room_name}</div>
                <div class="price-tag">Từ ${Number(st.price_standard).toLocaleString('vi-VN')} đ</div>
            </div>
        `;
    });
    grid.innerHTML = html;
}

async function selectShowtime(id) {
    state.selectedShowtime = state.allShowtimes.find(s => s.id == id);

    document.querySelectorAll('.showtime-card').forEach(c => c.classList.remove('selected'));
    document.getElementById(`st-${id}`).classList.add('selected');

    setTimeout(() => goToStep(2), 300);
}

// =============================================
// BƯỚC 2: SƠ ĐỒ GHẾ
// =============================================
async function loadSeatMap() {
    const st = state.selectedShowtime;
    document.getElementById('selected-showtime-info').innerText =
        `${st.room_name} — ${st.show_time.slice(0,5)} — ${new Date(st.show_date).toLocaleDateString('vi-VN')}`;

    const res = await fetch(`api/seats.php?action=map&showtime_id=${st.id}`);
    const seats = await res.json();

    const rows = {};
    seats.forEach(s => {
        if (!rows[s.row_label]) rows[s.row_label] = [];
        rows[s.row_label].push(s);
    });

    const mapEl = document.getElementById('seat-map');
    mapEl.innerHTML = '';

    for (const [row, rowSeats] of Object.entries(rows).sort()) {
        const rowDiv = document.createElement('div');
        rowDiv.className = 'seat-row';
        rowDiv.innerHTML = `<div class="row-label">${row}</div>`;

        rowSeats.sort((a, b) => a.seat_number - b.seat_number).forEach(seat => {
            if (seat.seat_number === 6 && rowSeats.length > 6) {
                const aisle = document.createElement('div');
                aisle.className = 'seat-aisle';
                rowDiv.appendChild(aisle);
            }

            const btn = document.createElement('div');
            btn.className = `seat ${seat.seat_type}`;
            btn.id = `seat-${seat.id}`;
            btn.innerText = seat.seat_number;
            btn.dataset.id = seat.id;
            btn.dataset.row = seat.row_label;
            btn.dataset.num = seat.seat_number;
            btn.dataset.type = seat.seat_type;

            if (seat.status === 'booked') {
                btn.classList.add('booked');
            } else if (seat.status === 'held' && !seat.is_my_hold) {
                btn.classList.add('held-other');
            } else if (seat.is_my_hold) {
                btn.classList.add('my-hold', 'selected');
                if (!state.selectedSeats.find(s => s.id == seat.id)) {
                    state.selectedSeats.push(buildSeatObj(seat));
                }
            } else {
                btn.onclick = () => toggleSeat(btn, seat);
            }

            rowDiv.appendChild(btn);
        });

        mapEl.appendChild(rowDiv);
    }
    updateSeatSummary();
}

function buildSeatObj(seat) {
    const st = state.selectedShowtime;
    const price = seat.seat_type === 'vip' ? st.price_vip :
                  seat.seat_type === 'couple' ? st.price_couple : st.price_standard;
    return { id: seat.id, row: seat.row_label, number: seat.seat_number, type: seat.seat_type, price: Number(price) };
}

async function toggleSeat(btn, seat) {
    const idx = state.selectedSeats.findIndex(s => s.id == seat.id);
    if (idx > -1) {
        state.selectedSeats.splice(idx, 1);
        btn.classList.remove('selected');
    } else {
        state.selectedSeats.push(buildSeatObj(seat));
        btn.classList.add('selected');
    }
    updateSeatSummary();

    if (state.selectedSeats.length > 0) {
        await holdSeats();
    } else {
        await fetch(`api/seats.php?action=release&showtime_id=${state.selectedShowtime.id}`, { method: 'DELETE' });
        stopCountdown();
        document.getElementById('timer-box').classList.add('hidden');
    }
}

async function holdSeats() {
    const res = await fetch('api/seats.php?action=hold', {
        method: 'POST',
        body: JSON.stringify({
            showtime_id: state.selectedShowtime.id,
            seat_ids: state.selectedSeats.map(s => s.id)
        })
    });
    const data = await res.json();
    if (res.ok) {
        state.holdExpiresAt = new Date(Date.now() + data.expires_in * 1000);
        startCountdown();
        document.getElementById('timer-box').classList.remove('hidden');
    } else {
        alert(data.error || 'Lỗi giữ ghế');
        loadSeatMap();
    }
}

function seatTypeLabel(type) {
    return { standard: 'Thường', vip: 'VIP', couple: 'Couple' }[type] || type;
}

function updateSeatSummary() {
    const total = state.selectedSeats.reduce((s, seat) => s + seat.price, 0);
    const labels = state.selectedSeats.map(s => `${s.row}${s.number}`).join(', ');
    document.getElementById('selected-seats-text').innerText = labels || 'Chưa có';
    document.getElementById('seats-subtotal').innerText = total.toLocaleString('vi-VN') + ' đ';

    const btn = document.getElementById('btn-go-combo');
    if (state.selectedSeats.length > 0) btn.classList.remove('hidden');
    else btn.classList.add('hidden');
}

// =============================================
// COUNTDOWN 5 PHÚT
// =============================================
function startCountdown() {
    stopCountdown();
    const el = document.getElementById('countdown');
    state.countdownInterval = setInterval(() => {
        const remaining = Math.max(0, state.holdExpiresAt - new Date());
        const mins = String(Math.floor(remaining / 60000)).padStart(2, '0');
        const secs = String(Math.floor((remaining % 60000) / 1000)).padStart(2, '0');
        el.innerText = `${mins}:${secs}`;
        if (remaining <= 60000) el.style.color = '#ff4444';
        else el.style.color = 'var(--primary)';
        if (remaining <= 0) {
            stopCountdown();
            state.selectedSeats = [];
            updateSeatSummary();
            loadSeatMap();
            alert('⏰ Thời gian giữ ghế đã hết!');
            document.getElementById('timer-box').classList.add('hidden');
        }
    }, 1000);
}

function stopCountdown() {
    if (state.countdownInterval) clearInterval(state.countdownInterval);
    state.countdownInterval = null;
}

// =============================================
// BƯỚC 3: COMBO
// =============================================
async function loadCombos() {
    const res = await fetch('api/combos.php');
    const combos = await res.json();
    const grid = document.getElementById('combos-grid');
    grid.innerHTML = combos.map(c => `
        <div class="combo-card" id="combo-${c.id}">
            <div class="combo-qty">
                <button onclick="changeComboQty(${c.id}, -1, ${c.price}, '${c.name}')">−</button>
                <span id="qty-${c.id}">0</span>
                <button onclick="changeComboQty(${c.id}, 1, ${c.price}, '${c.name}')">+</button>
            </div>
            <div class="combo-icon">${c.icon}</div>
            <h3>${c.name}</h3>
            <p>${c.description}</p>
            <div class="combo-price">${Number(c.price).toLocaleString('vi-VN')} đ</div>
        </div>
    `).join('');
}

function changeComboQty(id, delta, price, name) {
    const current = state.selectedCombos[id] ? state.selectedCombos[id].qty : 0;
    const newQty = Math.max(0, current + delta);
    const qtyEl = document.getElementById(`qty-${id}`);
    const card = document.getElementById(`combo-${id}`);
    if (newQty === 0) {
        delete state.selectedCombos[id];
        card.classList.remove('selected');
    } else {
        state.selectedCombos[id] = { id, name, price, qty: newQty };
        card.classList.add('selected');
    }
    qtyEl.innerText = newQty;
}

// =============================================
// BƯỚC 4: XÁC NHẬN
// =============================================
function renderConfirmation() {
    const st = state.selectedShowtime;
    const m = state.movie;
    const seatsTotal = state.selectedSeats.reduce((s, seat) => s + seat.price, 0);
    const comboTotal = Object.values(state.selectedCombos).reduce((s, c) => s + c.price * c.qty, 0);
    const grandTotal = seatsTotal + comboTotal;

    document.getElementById('confirm-movie-info').innerHTML = `
        <h3>Thông tin phim</h3>
        <p>🎬 <strong>${m.title}</strong></p>
        <p>🏛️ ${st.room_name} — ${st.show_time.slice(0,5)} — ${new Date(st.show_date).toLocaleDateString('vi-VN')}</p>
    `;

    const seatRows = state.selectedSeats.map(s =>
        `<span style="background:var(--card-bg); padding:0.2rem 0.5rem; border-radius:4px; margin:0.15rem; display:inline-block;">${s.row}${s.number} <small>(${seatTypeLabel(s.type)})</small></span>`
    ).join('');
    document.getElementById('confirm-seats-info').innerHTML = `
        <h3>Ghế đã chọn — ${seatsTotal.toLocaleString('vi-VN')} đ</h3>
        <p>${seatRows}</p>
    `;

    const comboList = Object.values(state.selectedCombos).length
        ? Object.values(state.selectedCombos).map(c => `<p>${c.name} x${c.qty} — ${(c.price * c.qty).toLocaleString('vi-VN')} đ</p>`).join('')
        : '<p style="color:var(--text-muted)">Không chọn combo</p>';
    document.getElementById('confirm-combo-info').innerHTML = `<h3>Combo — ${comboTotal.toLocaleString('vi-VN')} đ</h3>${comboList}`;

    document.getElementById('confirm-total-price').innerText = grandTotal.toLocaleString('vi-VN') + ' đ';
    document.getElementById('payment-amount').innerText = grandTotal.toLocaleString('vi-VN');

    const paymentData = encodeURIComponent(`CineManager Payment: ${grandTotal} VND`);
    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${paymentData}&color=e50914&bgcolor=1a1a1a`;
    document.getElementById('payment-qr-container').innerHTML = `<img src="${qrUrl}" alt="Payment QR" style="border: 4px solid white; border-radius: 8px;">`;
}

async function confirmBooking() {
    document.getElementById('btn-confirm').disabled = true;
    document.getElementById('btn-confirm').innerText = 'Đang xử lý...';

    const combosArr = Object.values(state.selectedCombos).map(c => ({ id: c.id, name: c.name, price: c.price, qty: c.qty }));
    const seatsTotal = state.selectedSeats.reduce((s, seat) => s + seat.price, 0);
    const comboTotal = combosArr.reduce((s, c) => s + c.price * c.qty, 0);

    const res = await fetch('api/bookings.php', {
        method: 'POST',
        body: JSON.stringify({
            showtime_id: state.selectedShowtime.id,
            seat_ids: state.selectedSeats.map(s => s.id),
            combos: combosArr,
            total_price: seatsTotal + comboTotal
        })
    });
    const data = await res.json();

    if (res.ok) {
        stopCountdown();
        document.getElementById('success-booking-id').innerText = `Mã vé: #${data.booking_id}`;
        const ticketData = encodeURIComponent(`CineManager Ticket #${data.booking_id}\nPhim: ${state.movie.title}\nGhế: ${state.selectedSeats.map(s=>s.row+s.number).join(', ')}`);
        const ticketQrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=${ticketData}`;
        document.getElementById('ticket-qr-container').innerHTML = `<img src="${ticketQrUrl}" alt="Ticket QR">`;
        showOnlyStep('success');
    } else {
        alert(data.error || 'Có lỗi xảy ra');
        document.getElementById('btn-confirm').disabled = false;
        document.getElementById('btn-confirm').innerText = '🎫 Xác Nhận & Đặt Vé';
    }
}

// =============================================
// ĐIỀU HƯỚNG BƯỚC
// =============================================
async function goToStep(num) {
    if (num === 2 && !state.selectedShowtime) { alert('Vui lòng chọn suất chiếu!'); return; }
    if (num === 4 && state.selectedSeats.length === 0) { alert('Vui lòng chọn ghế!'); return; }

    if (num === 2) { state.selectedSeats = []; loadSeatMap(); }
    if (num === 4) renderConfirmation();

    showOnlyStep(num);
    updateStepBar(num);
}

function showOnlyStep(num) {
    document.querySelectorAll('.step-content').forEach(el => el.classList.remove('active'));
    const target = num === 'success' ? document.getElementById('step-success') : document.getElementById(`step-${num}`);
    if (target) target.classList.add('active');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateStepBar(num) {
    [1, 2, 3, 4].forEach(i => {
        const el = document.getElementById(`step-indicator-${i}`);
        if(el) {
            el.classList.remove('active', 'done');
            if (i < num) el.classList.add('done');
            if (i === num) el.classList.add('active');
        }
    });
}

// =============================================
// POPUP XÁC NHẬN ĐỘ TUỔI
// =============================================
let nextStepTarget = 3;

function openAgeModal(step = 3) {
    if (state.selectedSeats.length === 0) {
        alert('Vui lòng chọn ít nhất 1 ghế ngồi!');
        return;
    }
    nextStepTarget = step;
    const modal = document.getElementById('age-confirm-modal');
    if (modal) modal.classList.remove('hidden');
}

function closeAgeModal() {
    const modal = document.getElementById('age-confirm-modal');
    if (modal) modal.classList.add('hidden');
}

function confirmAgeAndProceed() {
    closeAgeModal();
    goToStep(nextStepTarget);
}
// Mở popup chi tiết phim động theo từng phim
function openMovieDetailModal() {
    const m = state.movie;
    if (!m) return;

    document.getElementById('modal-movie-title').innerText = m.title;
    document.getElementById('m-genre').innerText = m.genre || 'Đang cập nhật';
    document.getElementById('m-duration').innerText = (m.duration || '120') + ' phút';

    // Tạo kho thông tin riêng biệt cho từng phim tránh bị code cứng một kiểu
    let extraInfo = {};
    
    if (m.title.includes('Chị Chị Em Em')) {
        extraInfo = {
            director: "Nguyễn Hữu Hoàng",
            cast: "Kỳ Duyên, Steven Nguyễn, Chi Pu, Quốc Anh, Ninh Dương Lan Ngọc...",
            language: "Tiếng Việt",
            release: "30/10/2026"
        };
    } else if (m.title.includes('Spider-Man') || m.title.includes('Spider')) {
        extraInfo = {
            director: "Joaquim Dos Santos, Kemp Powers",
            cast: "Shameik Moore, Hailee Steinfeld, Oscar Isaac, Daniel Kaluuya...",
            language: "Tiếng Việt (Lồng tiếng & Phụ đề)",
            release: "02/06/2026"
        };
    } else {
        // Dữ liệu mặc định tự động cho các phim khác trong hệ thống
        extraInfo = {
            director: "Đang cập nhật",
            cast: "Diễn viên chính thức theo đoàn làm phim",
            language: "Tiếng Việt",
            release: "15/11/2026"
        };
    }

    // Đổ dữ liệu tương ứng lên giao diện
    document.getElementById('m-director').innerText = extraInfo.director;
    document.getElementById('m-cast').innerText = extraInfo.cast;
    document.getElementById('m-language').innerText = extraInfo.language;
    document.getElementById('m-release').innerText = extraInfo.release;
    
    // Lấy nội dung tóm tắt từ cơ sở dữ liệu (cột description) ra
    document.getElementById('m-desc').innerText = m.description || m.desc || "Bộ phim mang đến những thước phim mãn nhãn, cốt truyện hấp dẫn và nhiều cảm xúc đọng lại cho khán giả tại rạp.";

    document.getElementById('movie-detail-modal').classList.remove('hidden');
}

function closeMovieDetailModal() {
    document.getElementById('movie-detail-modal').classList.add('hidden');
}