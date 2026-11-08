#!/usr/bin/env bash
# Tunggu MariaDB siap, lalu pastikan akun contoh ada.
for i in $(seq 1 90); do
  php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";dbname=".getenv("DB_NAME"), getenv("DB_USER"), getenv("DB_PASS")); exit(0); } catch (Exception $e) { exit(1); }' && break
  sleep 2
done
php app/setup.php
echo "Siap. Buka tab Ports lalu klik tautan port 8080."
