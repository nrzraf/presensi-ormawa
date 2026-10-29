<?php
/**
 * Scan Presensi Peserta - FR-05, FR-06.
 * Peserta memindai QR sesi acara yang ditampilkan panitia.
 */
require_role('anggota');
$u = current_user();

$eventId = (int)($_GET['event'] ?? 0);
$ev = $eventId ? q_one("SELECT * FROM event WHERE id=?", [$eventId]) : null;
$reg = ($ev && $eventId) ? q_one("SELECT * FROM registration WHERE event_id=? AND member_id=?", [$eventId, $u['id']]) : null;
$att = $reg ? q_one("SELECT * FROM attendance WHERE registration_id=?", [$reg['id']]) : null;

$title = 'Scan Presensi';
$subtitle = '';
ob_start(); ?>
<div class="grid col-2">
  <div class="card" style="background:var(--brand);border-color:var(--brand);color:#fff">
    <div class="card-head" style="color:#fff"><h3 style="color:#fff">Pindai QR Acara</h3><span class="badge b-present" id="camStatus">Menyiapkan...</span></div>
    <div id="camWrap" style="position:relative;width:100%;border-radius:14px;overflow:hidden;background:#0b1220;min-height:220px">
      <div id="reader" style="width:100%"></div>
      <div id="scanOverlay" style="position:absolute;inset:0;pointer-events:none">
        <div id="scanBox" style="position:absolute;width:230px;height:230px;border:2px solid #fff;border-radius:12px;
             box-shadow:0 0 0 9999px rgba(0,0,0,.5);pointer-events:none;touch-action:none">
          <span id="scanHandle" style="position:absolute;right:-14px;bottom:-14px;width:28px;height:28px;border-radius:50%;
                background:#fff;border:2px solid var(--brand);box-shadow:0 2px 6px rgba(0,0,0,.35);
                pointer-events:auto;cursor:nwse-resize;touch-action:none;display:grid;place-items:center">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#580018" stroke-width="2.6" stroke-linecap="round">
              <path d="M21 15v6h-6M3 9V3h6M21 21l-6-6M3 3l6 6"/></svg>
          </span>
        </div>
      </div>
    </div>
    <div id="result" style="margin-top:16px;display:none"></div>
    <div style="margin-top:14px">
      <label style="color:#fff;opacity:.85">Kode sesi (bila kamera gagal)</label>
      <div class="inline" style="flex-wrap:nowrap">
        <input id="manualCode" class="mono" placeholder="EVT2-XXXXXXXX"
               style="font-size:14px;text-transform:uppercase">
        <button class="btn" style="background:#fff;color:var(--brand)" onclick="submitManual()">Kirim</button>
      </div>
    </div>
  </div>

  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-head"><h3>Status Kehadiran</h3></div>
      <?php if (!$reg): ?>
        <div class="empty">Anda belum terdaftar di acara ini. <a href="index.php?r=events">Daftar dulu</a>.</div>
      <?php else: ?>
        <div class="table-scroll">
        <table>
          <tr><td class="muted">Acara</td><td><b><?= e($ev['nama']) ?></b></td></tr>
          <tr><td class="muted">Nama</td><td><?= e($u['nama']) ?></td></tr>
          <tr><td class="muted">NIM</td><td class="mono"><?= e($u['nim']) ?></td></tr>
          <tr><td class="muted">Status</td><td><?= status_badge($att ? 'hadir' : 'terdaftar') ?></td></tr>
          <?php if ($att): ?>
            <tr><td class="muted">Check-in</td><td class="mono"><?= date('d/m/Y H:i:s', strtotime($att['checkin_at'])) ?></td></tr>
          <?php endif; ?>
        </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-head"><h3>Acara Aktif</h3></div>
      <?php
      $aktif = q_all("SELECT e.id, e.nama, e.tanggal,
          (SELECT COUNT(*) FROM registration r WHERE r.event_id=e.id AND r.member_id=?) AS mine
          FROM event e WHERE e.status IN ('dipublikasikan','berlangsung')
          ORDER BY e.tanggal DESC LIMIT 6", [$u['id']]);
      ?>
      <?php if (!$aktif): ?><div class="empty">Tidak ada acara aktif.</div><?php else: ?>
        <?php foreach ($aktif as $a): ?>
          <a class="evrow" href="index.php?r=scan&event=<?= $a['id'] ?>" style="display:flex;text-decoration:none;color:inherit">
            <div class="datebox"><div class="d"><?= date('d', strtotime($a['tanggal'])) ?></div><div class="m"><?= date('M', strtotime($a['tanggal'])) ?></div></div>
            <div class="meta"><b><?= e($a['nama']) ?></b><div class="loc"><?= $a['mine'] ? 'Terdaftar' : 'Belum daftar' ?></div></div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const CSRF = <?= json_encode(csrf_token()) ?>;
const API = 'index.php?r=scan_api';
let busy = false, lastCode = '', lastTime = 0, qr = null, stopped = false;

function beep(ok){
  try{
    const ctx = new (window.AudioContext||window.webkitAudioContext)();
    const o = ctx.createOscillator(), g = ctx.createGain();
    o.connect(g); g.connect(ctx.destination);
    o.frequency.value = ok ? 880 : 220; o.type='sine';
    g.gain.setValueAtTime(.15, ctx.currentTime);
    g.gain.exponentialRampToValueAtTime(.001, ctx.currentTime+.18);
    o.start(); o.stop(ctx.currentTime+.2);
  }catch(e){}
}

function setStatus(t, ok){
  const s = document.getElementById('camStatus');
  if (s) { s.textContent = t; s.className = 'badge ' + (ok === false ? 'b-absent' : 'b-present'); }
}

// matikan kamera + lepas izin track
async function stopCam(){
  if (stopped) return; stopped = true;
  try { if (qr) { await qr.stop(); await qr.clear(); } } catch(e){}
  document.querySelectorAll('#reader video').forEach(v => {
    const st = v.srcObject; if (st) st.getTracks().forEach(t => t.stop());
    v.srcObject = null; v.remove();
  });
  const r = document.getElementById('reader'); if (r) r.innerHTML = '';
  const ov = document.getElementById('scanOverlay'); if (ov) ov.style.display = 'none';
}

async function send(code){
  if (busy) return; busy = true;
  try{
    const res = await fetch(API, {
      method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'csrf='+encodeURIComponent(CSRF)+'&code='+encodeURIComponent(code)
    });
    const d = await res.json();
    showResult(d);
    beep(!!d.ok);
  }catch(e){
    showResult({ok:false, pesan:'Gagal menghubungi server.'});
  }finally{ busy = false; }
}

function showResult(d){
  const box = document.getElementById('result');
  box.style.display='block';
  const color = d.ok ? '#047857' : '#BE123C';
  box.innerHTML = `<div style="background:rgba(255,255,255,.1);border:1px solid ${color};border-radius:12px;padding:16px">
    <div style="font-weight:700;font-size:15px">${d.ok ? 'Presensi tercatat' : 'Gagal'}</div>
    <div style="font-size:13px;color:#e2e8f0;margin-top:3px">${d.pesan || ''}</div>
  </div>`;
  if (d.ok) setTimeout(()=>location.reload(), 1400);
}

function submitManual(){
  const c = document.getElementById('manualCode').value.trim().toUpperCase();
  if (!c) { alert('Masukkan kode sesi.'); return; }
  stopCam();
  send(c);
  document.getElementById('manualCode').value='';
}

async function onScan(decoded){
  const now = Date.now();
  if (decoded === lastCode && now - lastTime < 3000) return;
  lastCode = decoded; lastTime = now;
  setStatus('Terbaca', true);
  await stopCam();          // kamera langsung ditutup
  send(decoded);
}

/* --- kotak bidik resizable, selalu terpusat --- */
const camWrap = () => document.getElementById('camWrap');
const scanBox = () => document.getElementById('scanBox');
const BOX_MIN = 120, BOX_DEF = 230;
let resizing = null;

// tempatkan kotak tepat di tengah area kamera
function centerBox(){
  const w = camWrap(), b = scanBox();
  if (!w || !b) return;
  const cw = w.clientWidth, ch = w.clientHeight;
  if (!cw || !ch) return;
  b.style.left = Math.round((cw - b.offsetWidth) / 2) + 'px';
  b.style.top  = Math.round((ch - b.offsetHeight) / 2) + 'px';
}

// ukuran awal menyesuaikan area, lalu dipusatkan
function fitBox(){
  const w = camWrap(), b = scanBox();
  if (!w || !b) return;
  const cw = w.clientWidth, ch = w.clientHeight;
  if (!cw || !ch) return;
  const max = Math.max(BOX_MIN, Math.min(cw, ch));
  const size = Math.min(BOX_DEF, max);
  b.style.width = b.style.height = size + 'px';
  centerBox();
}

(function initResize(){
  const h = document.getElementById('scanHandle'), b = scanBox(), w = camWrap();
  if (!h || !b || !w) return;

  h.addEventListener('pointerdown', function(e){
    const r = b.getBoundingClientRect();
    resizing = { x: e.clientX, y: e.clientY, size: r.width };
    try { h.setPointerCapture(e.pointerId); } catch(_){}
    e.preventDefault(); e.stopPropagation();
  });

  h.addEventListener('pointermove', function(e){
    if (!resizing) return;
    // gerakan menjauh dari pusat = perbesar (dihitung dari jarak, bukan arah sudut)
    const d = Math.max(e.clientX - resizing.x, e.clientY - resizing.y) * 2;
    const max = Math.min(w.clientWidth, w.clientHeight);
    const size = Math.max(BOX_MIN, Math.min(max, resizing.size + d));
    b.style.width = b.style.height = size + 'px';
    centerBox();                 // tetap di tengah setiap kali ukuran berubah
    e.preventDefault(); e.stopPropagation();
  });

  ['pointerup','pointercancel'].forEach(function(ev){
    h.addEventListener(ev, function(){ resizing = null; });
  });

  window.addEventListener('resize', function(){ if (!resizing) fitBox(); });
})();

async function startCam(){
  qr = new Html5Qrcode('reader', { verbose:false });
  try{
    await qr.start(
      { facingMode: 'environment' },
      { fps: 10 },
      onScan,
      () => {}
    );
    setStatus('Kamera aktif', true);
    fitBox();
    setTimeout(fitBox, 400);
  }catch(e){
    const msg = String(e && e.message || e);
    if (/NotAllowed|Permission/i.test(msg)) {
      setStatus('Izin kamera ditolak', false);
      showResult({ok:false, pesan:'Izinkan akses kamera di browser, atau ketik kode sesi di bawah.'});
    } else if (/NotFound|no camera|Overconstrained/i.test(msg)) {
      setStatus('Kamera tidak ada', false);
      showResult({ok:false, pesan:'Kamera tidak ditemukan. Gunakan kode sesi di bawah.'});
    } else {
      setStatus('Kamera gagal', false);
      showResult({ok:false, pesan:'Kamera tidak bisa dibuka. Gunakan kode sesi di bawah.'});
    }
  }
}

// auto-mulai saat halaman dibuka
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', startCam);
} else {
  startCam();
}
window.addEventListener('pagehide', stopCam);
</script>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
