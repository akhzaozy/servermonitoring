#!/bin/sh
# 1. Buat akun admin pertama dari ADMIN_EMAIL / ADMIN_PASSWORD (dilewati jika sudah ada).
cd /var/www/html && php artisan monitor:user --from-env || true

# 2. Jalankan background loop untuk menarik data metrik sistem & cek website setiap 60 detik.
nohup sh -c 'while true; do php /var/www/html/artisan monitor:check --all >/dev/null 2>&1; sleep 60; done' >/dev/null 2>&1 &
