<?php
/**
 * Dasbor per peran - FR-08, FR-11
 * - Anggota : temukan acara, statistik pribadi
 * - Panitia : ringkasan operasional acara
 * - Pembina : ringkasan pengawasan & proposal
 * - Admin   : ringkasan sistem menyeluruh
 */
$u = current_user();
$role = user_role();
$title = 'Dashboard';
// ambil nama panggilan (lewati gelar seperti "Dr." / "Ir.")
$bagian = preg_split('/\s+/', trim($u['nama']));
$nama = $bagian[0] ?? '';
foreach ($bagian as $b) {
    if (!preg_match('/[.]$/', $b) && mb_strlen($b) > 1) { $nama = $b; break; }
}
$heading = 'Halo, ' . $nama . '!';

$HARI = ['Sunday'=>'Minggu','Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis','Friday'=>'Jumat','Saturday'=>'Sabtu'];
$hariIni = $HARI[date('l')] ?? '';

$actions = '';
$subtitle = '';

/* ============================================================
   ANGGOTA
   ============================================================ */
if ($role === 'anggota') {

    $riwayat = q_all("
        SELECT e.id, e.nama, e.jenis, e.tanggal, e.lokasi, e.status AS ev_status,
               r.status AS reg_status, a.status AS att_status
        FROM registration r
        JOIN event e ON e.id=r.event_id
        LEFT JOIN attendance a ON a.registration_id=r.id
        WHERE r.member_id=? ORDER BY e.tanggal DESC", [$u['id']]);
    $diikuti = count($riwayat);
    $hadir = 0;
    foreach ($riwayat as $x) if (in_array($x['att_status'], ['hadir','cadangan'], true)) $hadir++;
    $pct = $diikuti > 0 ? round($hadir / $diikuti * 100) : 0;

    $kategori = q_all("SELECT jenis, COUNT(*) AS n FROM event
        WHERE status IN ('dipublikasikan','berlangsung') GROUP BY jenis ORDER BY n DESC LIMIT 8");
    $acara = q_all("SELECT e.*,
        (SELECT COUNT(*) FROM registration r WHERE r.event_id=e.id) AS reg,
        (SELECT COUNT(*) FROM registration r2 WHERE r2.event_id=e.id AND r2.member_id=?) AS mine
        FROM event e WHERE e.status IN ('dipublikasikan','berlangsung')
        ORDER BY e.tanggal ASC LIMIT 6", [$u['id']]);

    $content = '<div class="grid col-4" style="margin-bottom:18px">'
        . stat_card('calendar','blue', $diikuti, 'Acara Diikuti', 'terdaftar', true)
        . stat_card('check','green', $hadir, 'Kehadiran', 'hadir', true)
        . stat_card('trend','violet', $pct . '%', 'Persentase', 'dari acara diikuti', true)
        . stat_card('shield','rose', $pct >= 75 ? 'Baik' : 'Perlu Tingkatkan', 'Status Keaktifan', 'syarat 75%', true)
        . '</div>';

    // filter kategori + pencarian
    $content .= '<div class="toolbar">'
        . '<div class="search">' . icon('search') . '<input id="q" placeholder="Cari acara"></div>'
        . '<div class="pills" id="pills"><button type="button" class="pill active" data-k="">Semua</button>';
    foreach ($kategori as $k) {
        $content .= '<button type="button" class="pill" data-k="' . e(strtolower($k['jenis'])) . '">' . e($k['jenis']) . '</button>';
    }
    $content .= '</div></div>';

    // kartu acara
    $content .= '<div class="evgrid" id="cards">';
    if (!$acara) {
        $content .= '<div class="card"><div class="empty">Belum ada acara tersedia.</div></div>';
    }
    foreach ($acara as $a) {
        [$d, $m] = tgl_short($a['tanggal']);
        $inisial = strtoupper(substr($a['nama'], 0, 1));
        $penuh = $a['reg'] >= $a['kuota'];
        $content .= '<article class="evcard" data-jenis="' . e(strtolower($a['jenis'])) . '" data-nama="' . e(strtolower($a['nama'])) . '">'
            . '<div class="cover"><span class="big">' . e($inisial) . '</span>'
            . '<span class="st">' . status_badge($a['status']) . '</span>'
            . '<span class="pill">' . e($a['jenis']) . '</span></div>'
            . '<div class="body"><h4>' . e($a['nama']) . '</h4>'
            . '<div class="line">' . icon('calendar', 15) . $d . ' ' . ucfirst(strtolower($m)) . ' ' . date('Y', strtotime($a['tanggal'])) . ' · ' . substr($a['waktu'],0,5) . '</div>'
            . '<div class="line">' . icon('pin', 15) . e($a['lokasi']) . '</div>'
            . '<div class="line">' . icon('users', 15) . $a['reg'] . '/' . $a['kuota'] . ' peserta</div>'
            . '<div class="acts">';
        if ($a['mine']) {
            $content .= '<a class="btn btn-ghost btn-sm" href="index.php?r=myqr&reg=' . $a['mine'] . '">' . icon('star', 15) . 'Lihat QR</a>';
        } elseif ($penuh) {
            $content .= '<button class="btn btn-ghost btn-sm" disabled>Kuota Penuh</button>';
        } else {
            $content .= '<a class="btn btn-primary btn-sm" href="index.php?r=rsvp&id=' . $a['id'] . '">Daftar Sekarang</a>';
        }
        $content .= '<a class="btn btn-ghost btn-sm" href="index.php?r=event&id=' . $a['id'] . '" aria-label="Detail">' . icon('eye', 15) . '</a>'
            . '</div></div></article>';
    }
    $content .= '</div>';

    // riwayat
    $content .= '<div class="card" style="margin-top:18px"><div class="card-head"><h3>Riwayat Kehadiran Saya</h3><a class="link-more" href="index.php?r=events">Lihat acara</a></div>';
    if (!$riwayat) {
        $content .= '<div class="empty">Belum ada riwayat.</div>';
    } else {
        $content .= '<div class="table-scroll"><table><thead><tr><th>Acara</th><th>Tanggal</th><th>Status</th><th class="num">Kehadiran</th></tr></thead><tbody>';
        foreach ($riwayat as $x) {
            $content .= '<tr><td><b>' . e($x['nama']) . '</b><div class="sub">' . e($x['jenis']) . '</div></td>'
                . '<td class="muted">' . tgl_id($x['tanggal']) . '</td>'
                . '<td>' . status_badge($x['ev_status']) . '</td>'
                . '<td class="num">' . status_badge($x['att_status'] ?? 'tidak_hadir') . '</td></tr>';
        }
        $content .= '</tbody></table></div>';
    }
    $content .= '</div>';

    $content .= '<script>
    (function(){
      var pills=document.querySelectorAll("#pills .pill"), q=document.getElementById("q"),
          cards=document.querySelectorAll("#cards .evcard"), aktif="";
      function filter(){
        var s=(q.value||"").toLowerCase();
        cards.forEach(function(c){
          var ok=(!aktif||c.dataset.jenis===aktif)&&(!s||(c.dataset.nama||"").indexOf(s)>-1);
          c.style.display=ok?"":"none";
        });
      }
      pills.forEach(function(b){ b.addEventListener("click",function(){
        pills.forEach(function(x){x.classList.remove("active")}); b.classList.add("active");
        aktif=b.dataset.k||""; filter();
      });});
      q.addEventListener("input",filter);
    })();
    </script>';

    require __DIR__ . '/../layout.php';
    return;
}

/* ============================================================
   PANITIA / PEMBINA / ADMIN
   ============================================================ */
$whereAcara = "status IN ('dipublikasikan','berlangsung','ditutup')";

$totalAcara   = (int) q_val("SELECT COUNT(*) FROM event WHERE $whereAcara");
$mendatang    = (int) q_val("SELECT COUNT(*) FROM event WHERE tanggal >= CURRENT_DATE AND status IN ('dipublikasikan','berlangsung')");
$bulanIni     = (int) q_val("SELECT COUNT(*) FROM event WHERE date_trunc('month', tanggal) = date_trunc('month', CURRENT_DATE)");
$totalPeserta = (int) q_val("SELECT COUNT(*) FROM registration");
$totalHadir   = (int) q_val("SELECT COUNT(*) FROM attendance");
$avg          = $totalPeserta > 0 ? round($totalHadir / $totalPeserta * 100) : 0;
$anggotaAktif = (int) q_val("SELECT COUNT(*) FROM member WHERE status='aktif'");
$mggIni       = (int) q_val("SELECT COUNT(*) FROM registration WHERE registered_at >= date_trunc('week', CURRENT_DATE)");

// acara mendatang
$acara = q_all("SELECT e.*,
    (SELECT COUNT(*) FROM registration r WHERE r.event_id=e.id) AS reg,
    (SELECT COUNT(*) FROM attendance a WHERE a.event_id=e.id) AS hadir
    FROM event e WHERE e.status IN ('dipublikasikan','berlangsung')
    ORDER BY e.tanggal ASC, e.waktu ASC LIMIT 5");

// aktivitas terkini (audit)
$aktivitas = q_all("SELECT a.*, m.nama FROM audit_log a LEFT JOIN member m ON m.id=a.actor_id ORDER BY a.id DESC LIMIT 6");

// anggota teraktif
$top = q_all("SELECT m.nama, m.divisi, COUNT(a.id) AS hadir,
    (SELECT COUNT(*) FROM registration r2 WHERE r2.member_id=m.id) AS total
    FROM member m LEFT JOIN attendance a ON a.member_id=m.id
    WHERE m.status='aktif' GROUP BY m.id ORDER BY hadir DESC LIMIT 5");

$actions = '<a class="btn btn-primary" href="index.php?r=event_form">' . icon('plus', 17) . 'Buat Acara</a>';

$content = '<div class="grid col-4" style="margin-bottom:18px">'
    . stat_card('calendar','blue', $totalAcara, 'Total Acara', '+' . $bulanIni . ' bulan ini')
    . stat_card('clock','amber', $mendatang, 'Acara Mendatang', 'dalam 30 hari', true)
    . stat_card('users','violet', number_format($totalPeserta, 0, ',', '.'), 'Total Peserta', '+' . $mggIni . ' minggu ini')
    . stat_card('check','green', $totalHadir, 'Kehadiran Tercatat', $avg . '% rata-rata')
    . '</div>';

$content .= '<div class="grid col-2">';

// kiri: acara mendatang
$content .= '<div class="card"><div class="card-head"><h3>Acara Mendatang</h3><a class="link-more" href="index.php?r=events">Lihat Semua ' . icon('chev', 14) . '</a></div>';
if (!$acara) {
    $content .= '<div class="empty">Belum ada acara.</div>';
} else {
    foreach ($acara as $a) {
        [$d, $m] = tgl_short($a['tanggal']);
        $content .= '<div class="evrow"><div class="datebox"><div class="d">' . $d . '</div><div class="m">' . $m . '</div></div>'
            . '<div class="meta"><b>' . e($a['nama']) . '</b><div class="loc">' . icon('pin', 14) . e($a['lokasi']) . '</div></div>'
            . '<div class="right">' . status_badge($a['status'])
            . '<span class="muted" style="font-size:12.5px;white-space:nowrap">' . $a['reg'] . ' peserta</span></div></div>';
    }
}
$content .= '</div>';

// kanan: aktivitas
$content .= '<div class="card"><div class="card-head"><h3>Aktivitas Terkini</h3></div>';
if (!$aktivitas) {
    $content .= '<div class="empty">Belum ada aktivitas.</div>';
} else {
    $ikonMap = ['login'=>'user','logout'=>'exit','presensi'=>'check','buat'=>'plus','ubah'=>'edit','hapus'=>'alert','proposal'=>'file','publikasi'=>'calendar'];
    foreach ($aktivitas as $v) {
        $key = 'calendar'; $col = 'blue';
        foreach ($ikonMap as $k => $ic) { if (stripos($v['aksi'], $k) !== false) { $key = $ic; break; } }
        $colMap = ['user'=>'rose','exit'=>'gray','check'=>'green','plus'=>'violet','edit'=>'amber','alert'=>'rose','file'=>'blue','calendar'=>'blue'];
        $col = $colMap[$key] ?? 'blue';
        $txt = $v['detail'] ?: ucfirst($v['aksi'] . ' ' . $v['entitas']);
        $content .= '<div class="act"><span class="aic sic ' . $col . '" style="width:34px;height:34px;border-radius:50%">' . icon($key, 16) . '</span>'
            . '<div class="txt"><div>' . e($txt) . '</div><div class="time">' . waktu_lalu($v['created_at']) . '</div></div></div>';
    }
}
$content .= '</div></div>';

// bawah: peserta teraktif (panitia/admin) atau proposal (pembina)
if ($role === 'pembina') {
    $prop = q_all("SELECT p.*, e.nama AS ev, m.nama AS pengaju FROM proposal p
        JOIN event e ON e.id=p.event_id LEFT JOIN member m ON m.id=p.diajukan_oleh
        ORDER BY p.id DESC LIMIT 5");
    $content .= '<div class="card" style="margin-top:16px"><div class="card-head"><h3>Proposal Terbaru</h3><a class="link-more" href="index.php?r=proposals">Tinjau ' . icon('chev', 14) . '</a></div>';
    if (!$prop) {
        $content .= '<div class="empty">Belum ada proposal.</div>';
    } else {
        $content .= '<div class="table-scroll"><table><thead><tr><th>Proposal</th><th>Acara</th><th>Pengaju</th><th>Status</th></tr></thead><tbody>';
        foreach ($prop as $p) {
            $content .= '<tr><td><b>' . e($p['judul'] ?: 'Proposal') . '</b></td><td class="muted">' . e($p['ev']) . '</td>'
                . '<td class="muted">' . e($p['pengaju'] ?? '-') . '</td><td>' . status_badge($p['status']) . '</td></tr>';
        }
        $content .= '</tbody></table></div>';
    }
    $content .= '</div>';
} else {
    $content .= '<div class="card" style="margin-top:16px"><div class="card-head"><h3>Peserta Teraktif</h3><a class="link-more" href="index.php?r=members">Kelola ' . icon('chev', 14) . '</a></div>';
    if (!$top) {
        $content .= '<div class="empty">Belum ada data.</div>';
    } else {
        foreach ($top as $t) {
            $p = $t['total'] > 0 ? round($t['hadir'] / $t['total'] * 100) : 0;
            $content .= '<div style="display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 0;border-bottom:1px solid var(--line);flex-wrap:wrap">'
                . '<div style="min-width:120px;flex:1 1 140px"><b>' . e($t['nama']) . '</b><div class="sub">' . e($t['divisi']) . '</div></div>'
                . '<div style="flex:1 1 160px;max-width:240px"><div class="bar"><i style="width:' . $p . '%"></i></div><div class="sub mono" style="text-align:right">' . $p . '%</div></div></div>';
        }
    }
    $content .= '</div>';
}

require __DIR__ . '/../layout.php';
