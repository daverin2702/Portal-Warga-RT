<?php
// Pintu masuk: arahkan ke aplikasi jika sudah terpasang, ke installer jika belum.
$terpasang = is_file(__DIR__.'/app/config.local.php') || getenv('DB_HOST');
header('Location: '.($terpasang ? 'app/' : 'install.php'));
