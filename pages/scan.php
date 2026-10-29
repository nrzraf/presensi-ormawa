<?php
/**
 * Scan Presensi Peserta - FR-05, FR-06.
 * Peserta memindai QR sesi acara yang ditampilkan panitia.
 */
require_login();
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
    <div id="reader" style="width:100%;border-radius:14px;overflow:hidden;background:#0b1220"></div>
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
let busy = false, lastCode = '', lastTime = 0;

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
  if (d.ok) setTimeout(()=>location.reload(), 1200);
}

function submitManual(){
  const c = document.getElementById('manualCode').value.trim().toUpperCase();
  if (!c) { alert('Masukkan kode sesi.'); return; }
  send(c);
  document.getElementById('manualCode').value='';
}

const scanner = new Html5QrcodeScanner('reader', { fps:10, qrbox:{width:230,height:230}, rememberLastUsedCamera:true }, false);
function onScan(decoded){
  const now = Date.now();
  if (decoded === lastCode && now - lastTime < 3000) return;
  lastCode = decoded; lastTime = now;
  send(decoded);
}
scanner.render(onScan, ()=>{});
setTimeout(()=>{ const s=document.getElementById('camStatus'); if(s) s.textContent='Kamera aktif'; }, 1500);
</script>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
