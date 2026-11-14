# Deploy Monitor di Portainer (Source dari GitHub · Port 8576)

## 1. Deploy langsung dari Portainer (Git Repository)

1. Buka **Portainer** → **Stacks** → **Add stack**.
2. Pilih metode **Repository**:
   - **Name**: `servermonitoring`
   - **Repository URL**: `https://github.com/akhzaozy/servermonitoring`
   - **Repository reference**: `refs/heads/main`
   - **Compose path**: `docker-compose.yml`
3. *(Opsional)* Aktifkan **GitOps updates** jika ingin Portainer menarik update otomatis dari GitHub.
4. *(Opsional)* Tambahkan **Environment variables** di Portainer untuk mengganti default:
   - `MONITOR_PORT` = `8576` (default sudah `8576`, dipetakan ke `8080` di dalam container)
   - `MONITOR_SERVER_NAME` = `Server 2 Portainer`
   - `ADMIN_EMAIL` = `admin@monitor.local`
   - `ADMIN_PASSWORD` = `password-kuat-anda`
   - `AGENT_TOKEN` = `token-rahasia-agent-anda`
   - `PORTAINER_URL` = `https://portainer.domain-anda.com`
5. Klik **Deploy the stack**.

Setelah berjalan:
- **Dashboard Server 2**: `http://IP-SERVER:8576`
- **Endpoint Agent**: `http://IP-SERVER:8576/api/agent/metrics` (otomatis menarik data CPU, RAM, Disk `/host`, Suhu, Uptime, dan Website dari host setiap 60 detik maupun secara real-time).

## 2. Menghubungkan ke Dashboard Server Utama

1. Buka dashboard Server utama → menu **Servers** → klik **Tambah server**.
2. Isi:
   - **Nama**: `Server 2 (Portainer)`
   - **URL agent**: `http://IP-SERVER-2:8576`
   - **Token agent**: nilai `AGENT_TOKEN` (default: `stb-agent-secret-token-2026` atau sesuai yang Anda set di Portainer)
   - **URL Portainer**: link Portainer Anda (opsional)
3. Klik **Simpan**. Sekarang Anda bisa memantau kedua server sekaligus dan berpindah server lewat dropdown di **Dashboard** maupun **Terminal**.
