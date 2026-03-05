# 📱 Data Warga - Petunjuk Deployment

## Daftar File
- `index.php` - File utama aplikasi
- `.htaccess` - Konfigurasi keamanan (untuk Apache)
- `warga.json` - Database (akan dibuat otomatis)

## ✅ Cara Deploy ke Hosting

### Step 1: Upload File
1. Masuk ke File Manager hosting Anda
2. Upload kedua file ke folder public_html atau folder yang sesuai:
   - ✅ `index.php`
   - ✅ `.htaccess`
3. **JANGAN upload `warga.json`** - akan dibuat otomatis

### Step 2: Set Permission Folder
1. Di File Manager, klik kanan pada folder tempat upload
2. Pilih "Change Permissions" atau "Edit Permission"
3. Set ke **755** atau **775** agar bisa menyimpan file

### Step 3: Akses Aplikasi
- Buka di browser: `https://domain.com/` atau `https://domain.com/folder/`
- Aplikasi siap digunakan!
- File `warga.json` akan dibuat otomatis saat pertama kali menambah data

## 🔧 Jika Ada Error

**Error: "Folder tidak memiliki permission write"**
- Solusi: Hubungi hosting provider untuk set permission folder ke 755 atau 775

**File tidak ditemukan**
- Pastikan upload ke folder yang benar
- Cek nama file: `index.php` (huruf kecil)

## 🔐 Keamanan
- File `.htaccess` melindungi akses langsung ke `warga.json`
- Data tidak bisa diakses orang lain via direct URL
- Hanya bisa diakses melalui aplikasi PHP

## 💡 Tips
- Backup file `warga.json` secara berkala
- Tidak perlu setup database - semua data di JSON file
- Cocok untuk server shared hosting standar

---
**Pertanyaan?** Hubungi support hosting Anda untuk bantuan permission folder.
