<?php require 'config.php';
$role = $_GET['role'] ?? 'warga';
if (!in_array($role, ['admin','rt','warga'])) $role = 'warga';
$judul = ['admin'=>'Login Administrator','rt'=>'Login Pengurus RT','warga'=>'Login Warga'][$role];
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $u = q('SELECT * FROM users WHERE username=? AND role=?', [trim($_POST['username']), $role])->fetch();
  if ($u && password_verify($_POST['password'], $u['password'])) {
    session_regenerate_id(true);
    $_SESSION['user'] = ['id'=>$u['id'],'nama'=>$u['nama'],'role'=>$u['role'],'warga_id'=>$u['warga_id']];
    header('Location: '.(!empty($u['ganti_sandi']) ? 'profil_saya.php?ganti=1' : 'dashboard.php')); exit;
  }
  $err = 'Username atau kata sandi salah, atau akun ini bukan akun '.['admin'=>'administrator','rt'=>'pengurus RT','warga'=>'warga'][$role].'.';
}
?>
<!doctype html>
<html lang="id"<?= tema_attr() ?>><head>
<meta charset="utf-8"><?= head_extra() ?><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $judul ?> | <?= e(nama_situs()) ?></title>
<?= aset_css() ?></head><body>
<div class="login-wrap"><div class="panel p-4" style="width:100%;max-width:410px;border-radius:20px">
  <div class="plate mb-3"><?= nama_rt() ?></div>
  <h1 class="h4 fw-bold mb-1"><?= $judul ?></h1><p class="text-secondary mb-3">Masukkan akun Anda untuk melanjutkan.</p>
  <?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>
  <form method="post"><?= csrf_field() ?>
    <label class="form-label">Username</label><input name="username" class="form-control mb-3" required autofocus>
    <label class="form-label">Kata sandi</label><input name="password" type="password" class="form-control mb-3" required>
    <button class="btn btn-primary w-100 py-2">Masuk</button>
  </form>
  <div class="small mt-3 d-flex justify-content-between">
    <a href="index.php">Kembali ke beranda</a>
    <span>
      <?php foreach (['warga'=>'Warga','rt'=>'RT','admin'=>'Admin'] as $k=>$v) if ($k !== $role) echo "<a class='ms-2' href='login.php?role=$k'>$v</a>"; ?>
    </span>
  </div>
</div></div></body></html>
