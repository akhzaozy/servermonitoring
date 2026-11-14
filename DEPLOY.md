# Deploy Monitor ke server tambahan (Docker)

Arsitektur:

```
Server 1 (Portainer, dashboard utama)  ──HTTP + Bearer token──▶  Server 2 (container "monitor" = agent + dashboard)
```

Server 1 menjalankan dashboard ini (seperti sekarang). Server 2 menjalankan container Docker yang
menyediakan endpoint agent `/api/agent/*`. Dari dashboard Server 1, pilih server lewat dropdown di
halaman **Dashboard** dan **Terminal**.

## 1. Di Server 2

```bash
# salin proyek (tanpa vendor/node_modules/.env), mis. lewat git atau rsync
git clone <repo-anda> monitor && cd monitor

cp .env.docker.example .env
# isi APP_KEY, AGENT_TOKEN, ADMIN_EMAIL, ADMIN_PASSWORD
echo "base64:$(openssl rand -base64 32)"   # -> APP_KEY
openssl rand -hex 32                        # -> AGENT_TOKEN

docker compose up -d --build
docker compose logs -f monitor
```

Dashboard Server 2 tersedia di `http://IP-SERVER-2:8080` (login dengan `ADMIN_EMAIL`/`ADMIN_PASSWORD`).
Ganti `MONITOR_PORT` di `.env` bila port 8080 terpakai.

> Batasi akses port 8080 dengan firewall agar hanya IP Server 1 (dan Anda) yang bisa mengakses,
> atau taruh di belakang reverse proxy HTTPS / Cloudflare Tunnel.

## 2. Di Server 1

1. Buka **Servers → Tambah server**.
2. URL: `http://IP-SERVER-2:8080`, Token: nilai `AGENT_TOKEN` Server 2, Portainer URL (opsional).
3. Server 2 muncul di dropdown Dashboard & Terminal.

Server 1 butuh migrasi baru (tabel `servers`) dan akun login:

```bash
php artisan migrate --force
php artisan monitor:user email@anda.com --name="Nama Anda"     # akan meminta password
```

## Terminal

- **Server lokal (Server 1)**: perintah berjalan sebagai user PHP/web server (mis. `www-data`).
  Nonaktifkan dengan `TERMINAL_ENABLED=false`.
- **Server 2**: Dashboard Server 1 memanggil `/api/agent/exec`. Aktif bila `AGENT_ALLOW_EXEC=true` di Server 2.
  Secara default perintah berjalan **di dalam container**. Untuk shell **host** Server 2, isi di `.env` Server 2:

  ```
  TERMINAL_SSH_HOST=host.docker.internal
  TERMINAL_SSH_USER=namauser
  TERMINAL_SSH_KEY=/ssh/id_ed25519
  ```

  lalu siapkan kunci SSH khusus (daftarkan public key-nya di `~namauser/.ssh/authorized_keys` Server 2):

  ```bash
  mkdir ssh && ssh-keygen -t ed25519 -N "" -f ssh/id_ed25519
  cat ssh/id_ed25519.pub >> ~/.ssh/authorized_keys
  sudo chown 33:33 ssh/id_ed25519 && chmod 600 ssh/id_ed25519
  ```

  dan aktifkan baris `- ./ssh:/ssh:ro` pada `docker-compose.yml`.

Catatan keamanan: terminal = eksekusi perintah dari browser. Gunakan password kuat, HTTPS, dan batasi
akses jaringan. Token agent setara kunci akses ke Server 2.

Keterbatasan: tiap perintah dijalankan terpisah (non-interaktif). Program layar-penuh (`vim`, `htop`, `nano`)
tidak didukung; pakai `top -bn1`, `cat`, `sed`, dll. Batas waktu per perintah: `TERMINAL_TIMEOUT` (default 30 detik).

## Variabel environment penting

| Variabel | Fungsi |
|---|---|
| `MONITOR_SERVER_NAME` | Nama server tempat dashboard berjalan |
| `PORTAINER_URL` | Tombol Portainer di dashboard server tersebut |
| `AGENT_TOKEN` | Token yang dipakai dashboard lain untuk membaca server ini |
| `AGENT_ALLOW_EXEC` | Izinkan terminal remote ke server ini |
| `TERMINAL_ENABLED` / `TERMINAL_TIMEOUT` | Aktif/nonaktifkan terminal & batas waktu |
| `TERMINAL_SSH_*` | Jalankan perintah di host lewat SSH |
| `MONITOR_HOST_ROOT` | Root host yang di-mount (Docker: `/host`) |
