# Vfos Development & Upgrade Roadmap

This is a comprehensive roadmap and checklist for Vfos development, capturing our vision for a 5-10 year financial freedom tool.

## 🟢 Fase 1: Kebutuhan Mendesak & Fondasi Keuangan Pribadi-Keluarga (Aktif / In-Progress)
- [ ] **1. Sinking Funds (Pelacakan Tujuan Finansial)**
  - Menjawab: "Uangku di Reksadana ini tujuannya untuk apa?"
  - Fitur: Mapping Asset/Investment ke "Goals" (Dana Darurat, Menikah, Beli Mobil).
  - UI: Progress bar pencapaian target tabungan vs total nilai saat ini.
- [ ] **2. RBAC Sederhana (Isolasi View Pasangan)**
  - Menjawab: "Pacarku harusnya cuma bisa lihat akunnya sendiri."
  - Fitur: Membatasi data dashboard/account berdasar Role sederhana, tanpa merombak arsitektur terlalu jauh. Pasangan yang login hanya melihat transaksi/net-worth *owner = pacar*.
- [ ] **3. Monthly Financial Reporting (P&L & Balance Sheet)**
  - Menjawab: "Aku butuh laporan rapi bulanan seperti mutasi rekening bank."
  - Fitur: Halaman Export ke PDF/Excel berisi Laba Rugi (Income Statement) dan Neraca (Harta vs Utang).
  - Bisa di-*filter* berdasarkan owner (Pribadi / Pacar / Bisnis).

## 🟡 Fase 2: Peningkatan Skala Bisnis & Otomasi Lanjutan (Menengah)
- [ ] **4. Multi-Tenancy (Workspaces) Sejati**
  - Isolasi total environment (Dashboard, Accounts, Categories) antara "Pribadi", "Bisnis A", "Bisnis B".
  - Mekanisme *Switch Workspace* lewat tombol navigasi tanpa harus logout email.
- [ ] **5. Manajemen Lampiran (Audit Pembuktian)**
  - Integrasi n8n untuk menarik PDF e-Statement / Foto Struk dari Email.
  - Upload & Attach PDF/Struk Image di entitas `transactions`. Memudahkan jika ada audit pajak.
- [ ] **6. Pengingat & Usia Utang/Piutang (Aging AR/AP)**
  - Notifikasi Telegram via cronjob/scheduler (otomasi): "Invoice Klien X sudah telat 30 hari."
  - Laporan umur piutang (15 hari, 30 hari, >90 hari macet).

## 🔴 Fase 3: Kebebasan Finansial & Investasi Cerdas (Jangka Panjang)
- [ ] **7. FIRE & SWR (Passive Income Tracker)**
  - Integrasi "Kaca Depan": Apakah persentase gain/dividen investasi bulan ini sudah mampu menutup Expense bulan ini?
  - Proyeksi *Net Worth* 10 tahun ke depan berdasarkan *saving rate* saat ini.
- [ ] **8. Tax Deductible vs Non-Deductible Tagging**
  - Menandai pengeluaran pribadi (Prive) vs Operasional Bisnis untuk memudahkan SPT Tahunan di bulan Maret. PPN masuk/keluar tracking.

---
*Dokumen ini dirancang pada Maret 2026. Diperbarui secara berkala bersama tim asisten AI (Antigravity).*
