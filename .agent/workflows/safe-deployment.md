---
description: Safe deployment workflow - push code to production server without breaking database
---

# 🔒 Aturan Emas: Safe Production Deployment (VFOS)
// turbo-all

### Prinsip Utama
- **JANGAN PERNAH** jalankan `migrate:fresh` atau `migrate:rollback` di production
- **SELALU** backup database sebelum deploy jika ada migration baru
- **HANYA** jalankan `migrate` (tanpa flag) untuk migration baru di production
- Perubahan PHP/Blade biasa **TIDAK PERLU** migration

---

### Langkah Deployment

#### STEP 1: Commit & Push dari Local (Laragon)
Pastikan kamu berada di direktori project lokal.
```bash
cd c:\laragon\www\Vfos

# Cek perubahan
git status

# Add semua perubahan
git add -A

# Commit dengan pesan deskriptif
git commit -m "feat: tambahkan deskripsi singkat perubahan"

# Push ke GitHub (Remote origin)
git push origin main
```

#### STEP 2: Backup Database di Server (WAJIB jika ada migration baru)
Karena koneksi SSH / network WiFi ke server sering terputus, usahakan proses backup ini dilakukan di satu waktu yang stabil (jangan sambil download file berat lainnya). 
SSH ke server (`veronica@192.168.1.50`), lalu jalankan:
```bash
cd /srv/docker/apps/vfos

# Backup database sebelum pull
sudo docker compose exec db mysqldump -u vfos_user -puser_password_replace_me vfos > /home/veronica/backups/vfos_backup_$(date +%Y%m%d_%H%M%S).sql
```
*(Pastikan folder `/home/veronica/backups` sudah ada)*

#### STEP 3: Pull & Update di Server
```bash
cd /srv/docker/apps/vfos

# Pull kode terbaru dari GitHub
sudo git pull origin main

# KHUSUS VFOS SERVER: WAJIB RE-BUILD DOCKER
# Karena arsitekturnya mengkopi folder saat docker build (lihat Dockerfile: COPY . /var/www),
# maka perintah `docker compose build` MUTLAK DIPERLUKAN supaya kode baru ter-update di image.
sudo docker compose build app

# Restart container app dengan image yang baru di-build (bisa menggunakan -d untuk jalan di background)
sudo docker compose up -d app

# Clear semua cache Laravel
sudo docker compose exec app php artisan config:clear
sudo docker compose exec app php artisan view:clear
sudo docker compose exec app php artisan route:clear
sudo docker compose exec app php artisan cache:clear

# HANYA JIKA ADA MIGRATION BARU (BUKAN migrate:fresh!)
sudo docker compose exec app php artisan migrate --force

# Re-cache untuk production
sudo docker compose exec app php artisan config:cache
sudo docker compose exec app php artisan route:cache
```

#### STEP 4: Verifikasi
- Buka aplikasi di browser (Server: `http://192.168.1.50:8080/login` / Tailscale: `http://100.111.152.65:8080/login`)
- Cek apakah fitur baru berfungsi
- Cek apakah data-data lama masih ada dan aman

---

### ⚠️ PERINTAH BERBAHAYA - JANGAN GUNAKAN DI PRODUCTION
```bash
# ❌ JANGAN! Ini menghapus SEMUA data di database!
php artisan migrate:fresh
php artisan migrate:fresh --seed
php artisan migrate:rollback
php artisan db:wipe
```

---

### ☑️ Checklist Sebelum Deploy
- [ ] Sudah test fitur baru di local (Laragon)?
- [ ] Ada migration baru? Jika ya, backup database server dulu!
- [ ] Commit message sudah jelas memperlihatkan apa yang dirubah?
- [ ] Koneksi server (WiFi/Tailscale) sedang stabil untuk menghindari RTO saat build?
- [ ] Sudah menjalankan `docker compose build app` dan `docker compose up -d app` setelah pull?

---

### 🛠️ Recovery Jika Terjadi Masalah

**1. Restore database dari backup:**
```bash
sudo docker compose exec -T db mysql -u vfos_user -puser_password_replace_me vfos < /home/veronica/backups/vfos_backup_YYYYMMDD_HHMMSS.sql
```

**2. Rollback ke commit sebelumnya (gunakan dengan hati-hati):**
```bash
sudo git reset --hard HEAD~1
sudo docker compose build app
sudo docker compose up -d app
```
