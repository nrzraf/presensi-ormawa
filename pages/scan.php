<?php
/** Scan Presensi - UC-05, FR-05, FR-06 */
require_role('panitia','admin');
$u = current_user();

// pilih acara aktif
$eventId = (int)($_GET['event'] ?? 0);
$acara = q_all("SELECT e.id, e.nama, e.status,
    (SELECT COUNT(*) FROM registration r WHERE r.event_id=e.id) AS reg
    FROM event e WHERE e.status IN ('dipublikasikan','berlangsung')
    ORDER BY reg DESC, e.tanggal DESC");
if (!$eventId && $acara) $eventId = (int)$acara[0]['id'];
$ev = $eventId ? q_one("SELECT * FROM event WHERE id=?", [$eventId]) : null;

$stat = $ev ? [
    'hadir'  => (int) q_val("SELECT COUNT(*) FROM attendance WHERE event_id=?", [$eventId]),
    'manual' => (int) q_val("SELECT COUNT(*) FROM attendance WHERE event_id=? AND metode='manual'", [$eventId]),
    'total'  => (int) q_val("SELECT COUNT(*) FROM registration WHERE event_id=?", [$eventId]),
] : ['hadir'=>0,'manual'=>0,'total'=>0];

$title = 'Scan Presensi';
$subtitle = $ev ? e($ev['nama']) . ' · ' . tgl_id($ev['tanggal']) : 'Belum ada acara aktif';
ob_start(); ?>
<div class="card" style="margin-bottom:16px">
  <form method="get" class="inline">
    <input type="hidden" name="r" value="scan">
    <div style="flex:1;min-width:240px">
      <label>Pilih acara</label>
      <select name="event" onchange="this.form.submit()">
        <?php if (!$acara): ?><option>- tidak ada acara aktif -</option><?php endif; ?>
        <?php foreach ($acara as $a): ?>
          <option value="<?= $a['id'] ?>" <?= $a['id']==$eventId?'selected':'' ?>><?= e($a['nama']) ?> (<?= $a['status'] ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <a class="btn btn-ghost" href="index.php?r=event&id=<?= $eventId ?>">Detail Acara</a>
  </form>
</div>

<?php if (!$ev): ?>
  <div class="card"><div class="empty">Belum ada acara dibuka.</div></div>
<?php else: ?>
<div class="grid col-2">
  <!-- STAGE KAMERA -->
  <div class="card" style="background:var(--primary);border-color:var(--primary);color:#fff">
    <div class="card-head" style="color:#fff"><h3 style="color:#fff">Pindai QR Peserta</h3><span class="badge b-present" id="camStatus">Menyiapkan...</span></div>
    <div id="reader" style="width:100%;border-radius:14px;overflow:hidden;background:#0b1220"></div>
    <div id="result" style="margin-top:16px;display:none"></div>
  </div>

  <!-- SISI KANAN -->
  <div>
    <div class="card" style="margin-bottom:16px">
      <div class="card-head"><h3>Sesi Berjalan</h3><span class="badge b-info">Live</span></div>
      <div style="display:flex;justify-content:space-between;gap:12px;text-align:center">
        <div style="flex:1"><div class="mono" style="font-size:26px;font-weight:800" id="cHadir"><?= $stat['hadir'] ?></div><div class="sub">Hadir</div></div>
        <div style="flex:1"><div class="mono" style="font-size:26px;font-weight:800" id="cManual"><?= $stat['manual'] ?></div><div class="sub">Cadangan</div></div>
        <div style="flex:1"><div class="mono" style="font-size:26px;font-weight:800"><?= $stat['total'] ?></div><div class="sub">Terdaftar</div></div>
      </div>
    </div>

    <div class="card">
      <div class="card-head"><h3>Mode Cadangan</h3></div>
      <label>Kode manual 6 digit</label>
      <div class="inline" style="flex-wrap:nowrap">
        <input id="manualCode" class="mono" maxlength="6" inputmode="numeric" placeholder="000000"
               style="text-align:center;letter-spacing:.35em;font-size:18px">
        <button class="btn btn-primary" onclick="submitManual()">Catat</button>
      </div>
    </div>
  </div>
</div>

<!-- hasil terakhir list -->
<div class="card">
  <div class="card-head"><h3>Presensi Terakhir</h3><span class="chip">realtime</span></div>
  <div class="table-scroll">
  <table><tbody id="log"><tr><td class="muted">Belum ada pemindaian pada sesi ini.</td></tr></tbody></table>
  </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
const EVENT_ID = <?= $eventId ?>;
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

async function send(code, metode){
  if (busy) return; busy = true;
  try{
    const res = await fetch(API, {
      method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'csrf='+encodeURIComponent(CSRF)+'&event_id='+EVENT_ID+'&code='+encodeURIComponent(code)+'&metode='+metode
    });
    const d = await res.json();
    showResult(d);
    if (d.ok){
      beep(true);
      document.getElementById('cHadir').textContent = d.hadir;
      document.getElementById('cManual').textContent = d.manual;
      addLog(d);
    } else { beep(false); }
  }catch(e){
    showResult({ok:false, pesan:'Gagal menghubungi server.'});
  }finally{ busy = false; }
}

function showResult(d){
  const box = document.getElementById('result');
  box.style.display='block';
  const color = d.ok ? '#047857' : '#BE123C';
  const icon  = d.ok ? '' : '';
  const extra = d.ok ? `<div style="font-size:12px;color:#94a3b8;margin-top:2px">${d.metode==='manual'?'Mode cadangan':'QR'} · ${d.waktu}</div>` : '';
  box.innerHTML = `<div style="background:rgba(255,255,255,.08);border:1px solid ${color};border-radius:12px;padding:16px;display:flex;gap:14px;align-items:center">
    <div style="width:42px;height:42px;border-radius:50%;background:${color};display:grid;place-items:center;font-size:20px;font-weight:800">${icon}</div>
    <div><div style="font-weight:700">${d.nama || d.pesan}</div>${d.nim?`<div style="font-size:12px;color:#94a3b8">NIM ${d.nim} · ${d.divisi||''}</div>`:''}${extra}</div>
  </div>`;
}

function addLog(d){
  const tb = document.getElementById('log');
  if (tb.querySelector('.muted')) tb.innerHTML='';
  const tr = document.createElement('tr');
  tr.innerHTML = `<td><b>${d.nama}</b><div class="sub">${d.nim}</div></td>
    <td><span class="badge ${d.metode==='manual'?'b-fallback':'b-present'}">${d.metode==='manual'?'Cadangan':'Hadir'}</span></td>
    <td class="num mono">${d.waktu}</td>`;
  tb.prepend(tr);
}

function submitManual(){
  const c = document.getElementById('manualCode').value.trim();
  if (!/^\d{6}$/.test(c)) { alert('Masukkan 6 digit angka.'); return; }
  send(c, 'manual');
  document.getElementById('manualCode').value='';
}

// --- QR scanner ---
const scanner = new Html5QrcodeScanner('reader', { fps:10, qrbox:{width:230,height:230}, rememberLastUsedCamera:true }, false);
function onScan(decoded){
  const now = Date.now();
  if (decoded === lastCode && now - lastTime < 3000) return; // debounce kode sama
  lastCode = decoded; lastTime = now;
  send(decoded, 'qr');
}
scanner.render(onScan, ()=>{});
setTimeout(()=>{ document.getElementById('camStatus').textContent='Kamera aktif'; }, 1500);
</script>
<?php endif; ?>
<?php $content = ob_get_clean();
require __DIR__ . '/../layout.php';
