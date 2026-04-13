# Sistem Verifikasi Interaksi Obat Berbahaya pada Resep Dokter dengan Analisis Perbandingan Metode Pencarian Data
# _Drug Interaction Verification System for Prescription Safety using Comparative Analysis of Data Search Methods_

## Deskripsi

Proyek ini merupakan implementasi sistem untuk **mendeteksi dan memverifikasi interaksi antar obat** pada resep dokter. Sistem ini dikembangkan sebagai bagian dari penelitian tugas akhir yang berfokus pada **perbandingan performa berbagai metode pencarian data** dalam mendeteksi interaksi obat.

Sistem mengintegrasikan:

* Aplikasi web (PHP & MySQL)
* Benchmarking performa (waktu & memori untuk setiap metode pencarian)
---

## Tujuan Penelitian

1. Mengembangkan sistem untuk mendeteksi interaksi obat secara otomatis dari masukan berupa resep dokter
2. Membandingkan performa beberapa metode pencarian data
3. Menganalisis efisiensi waktu eksekusi dan penggunaan memori untuk setiap metode pencarian data
4. Memberikan solusi optimal untuk implementasi sistem kesehatan digital
---

## Metode Pencarian data yang Dibandingkan

Beberapa pendekatan yang diuji dalam penelitian ini:

* Dictionary (Python)
* Nested Dictionary
* MultiIndex Pandas
* File-based Indexing
* Database Query (MySQL & PHP)

---

## Parameter Evaluasi

Pengujian dilakukan dengan variasi jumlah data:

* 5 obat
* 20 obat
* 50 obat
* 100 obat

Parameter yang dianalisis:

* Waktu eksekusi
* Penggunaan memori
---

## Struktur Proyek

```
.
├── analysis/        # Eksperimen & benchmarking metode
│   ├── code/        # Script Python & PHP pengujian metode pencarian
│   ├── results/     # Hasil benchmark (CSV & Excel)
│   ├── logs/        # Log hasil eksekusi
│   └── benchmarks/  # Skenario pengujian
│
├── system/          # Aplikasi utama
│   ├── main_code/   # Source code PHP (web system)
│   └── database/    # Schema & data SQL
│
├── docs/            # Dokumen penelitian (skripsi, paper, ppt)
├── diagrams/        # Diagram sistem (UML, arsitektur)
└── README.md
```

---

## Teknologi yang Digunakan

### Backend & Web

* PHP
* MySQL (XAMPP)

### Data Processing

* Python
* Pandas

### Frontend

* HTML
* CSS
* JavaScript

---

## Cara Menjalankan Sistem

### 1. Setup Database

* Import file SQL dari folder:

  ```
  system/database/schema.sql
  ```
* Jalankan di phpMyAdmin

---

### 2. Jalankan Aplikasi Web

* Pindahkan folder `system/main_code/` ke `htdocs` (XAMPP)
* Akses melalui browser:

  ```
  http://localhost/...
  ```

---

### 3. Jalankan Analisis (Opsional)

Masuk ke folder:

```
analysis/code/
```

Jalankan:

```bash
python cek_interaksi.py
```

---

## Hasil Penelitian

Hasil benchmarking menunjukkan bahwa:

* Metode berbasis php indexing memberikan performa lebih cepat pada dataset (besar) yang digunakan dalam pengembangan sistem pengecekan interaksi obat berbahaya ini
* Penggunaan struktur data yang tepat sangat mempengaruhi efisiensi sistem
* Trade-off antara kecepatan dan penggunaan memori perlu dipertimbangkan

(Hasil detail tersedia di folder `analysis/results/`)

---

##  Dokumentasi

Dokumen lengkap tersedia di:

```
docs/thesis.pdf
docs/paper_id.pdf
docs/ppt.pdf
```

---

## Pengembang

**Nama:** Maulida Adhifa Naila Muthi
**Program Studi:** Teknik Biomedis
**Universitas:** Institut Teknologi Bandung

---

## Catatan

Proyek ini dikembangkan untuk tujuan akademik dan penelitian.

---
