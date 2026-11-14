#!/bin/sh
# Buat akun admin pertama dari ADMIN_EMAIL / ADMIN_PASSWORD (dilewati jika sudah ada).
cd /var/www/html && php artisan monitor:user --from-env || true
