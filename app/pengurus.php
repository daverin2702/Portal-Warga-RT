<?php require 'config.php'; require 'layout.php'; need(['admin','rt','warga']);
$p = []; foreach (q("SELECT nama,foto,jabatan FROM users WHERE jabatan<>'warga' ORDER BY nama") as $r) $p[$r['jabatan']][] = $r;
function orang($list) {
  if (!$list) return '<div class="vacant">Belum ditetapkan</div>'; $h = '';
  foreach ($list as $o) { $f = foto($o['foto']);
    $h .= '<div class="org-person">'.($f ? '<img src="'.e($f).'" alt="">' : '<span class="ini">'.e(inisial($o['nama'])).'</span>').'<span>'.e($o['nama']).'</span></div>'; }
  return $h;
}
function node($key, $cls, $p) { return '<div class="org-node '.$cls.'"><div class="org-title">'.e(JABATAN[$key]).'</div>'.orang($p[$key] ?? []).'</div>'; }
head('Struktur Pengurus', nama_rt().' · '.cfg('ketua_masa')); ?>
<div class="panel"><div class="org">
  <div class="org-core">
    <?= node('ketua', 'n-ketua', $p) ?><div class="gap"></div>
    <?= node('wakil', 'n-wakil', $p) ?><div class="gap"></div>
    <div class="org-mid">
      <div class="org-stack"><?= node('sekretaris', 'n-sek', $p) ?><?= node('wakil_sekretaris', 'n-wsek', $p) ?></div>
      <?= node('bendahara', 'n-ben', $p) ?>
    </div>
  </div>
  <div class="org-seksi lima">
    <div class="seksi-col"><?= node('seksi_pembangunan', 's1', $p) ?></div>
    <div class="seksi-col"><?= node('seksi_keamanan', 's2', $p) ?><?= node('tim_keamanan', 'org-tim t2', $p) ?></div>
    <div class="seksi-col"><?= node('seksi_kebersihan', 's5', $p) ?><?= node('tim_kebersihan', 'org-tim t5', $p) ?></div>
    <div class="seksi-col"><?= node('seksi_pkk', 's3', $p) ?></div>
    <div class="seksi-col"><?= node('seksi_pemuda', 's4', $p) ?></div>
  </div>
</div>
<?php if (user()['role'] === 'admin'): ?><div class="text-center mt-4 no-print"><a href="akun.php" class="btn btn-light"><i class="bi bi-sliders me-1"></i>Atur jabatan di Akun Pengguna</a></div><?php endif; ?></div>
<?php foot();
