#!/bin/sh
# Siapkan file SQLite sebelum migrasi otomatis dijalankan.
touch "${DB_DATABASE:-/data/database.sqlite}"
