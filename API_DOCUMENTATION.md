5. API DOCUMENTATION — DOKUMENTASI API

Dokumentasi ini menjelaskan endpoint (titik akses) sistem backend pada Aplikasi Booking Wisata Walini. Sistem ini menggunakan arsitektur PHP Native dengan metode POST form-based untuk memproses data transaksi dan autentikasi.

A. DAFTAR ENDPOINT UTAMA

No | Method | Endpoint (File)             | Fungsi Utama               | Hak Akses
---|--------|-----------------------------|----------------------------|----------
1  | POST   | /api/proses_login.php       | Autentikasi User           | Public
2  | POST   | /api/proses_registrasi.php  | Pendaftaran Akun Baru      | Public
3  | POST   | /api/proses_booking.php     | Pembuatan Order Tiket      | Customer
4  | POST   | /api/proses_pembayaran.php  | Upload Bukti Transfer      | Customer
5  | POST   | /api/proses_konfirmasi.php  | Verifikasi Pembayaran      | Kasir/Admin
6  | POST   | /api/proses_validasi.php    | Cek Keaslian E-Tiket       | Kasir

B. DETAIL ENDPOINT & PARAMETER

1. Login User (/api/proses_login.php)
   - Tujuan: Memvalidasi email & password, lalu memulai session.
   - Input (POST): 
     * email (string, wajib)
     * password (string, wajib)
   - Output Sukses: Redirect ke dashboard sesuai role (Admin/Kasir/Customer).
   - Output Gagal: Redirect ke login dengan pesan error "Email atau password salah".
   - Keamanan: Password diverifikasi menggunakan password_verify() (bcrypt).

2. Booking Tiket (/api/proses_booking.php)
   - Tujuan: Menyimpan data pesanan tiket baru.
   - Input (POST):
     * id_destinasi (int, wajib)
     * tanggal_booking (date, wajib, harus >= hari ini)
     * jumlah_tiket (int, wajib, > 0)
   - Proses: 
     * Mengecek ketersediaan stok tiket.
     * Menghitung total harga (harga x jumlah).
     * Membuat kode booking unik (Format: WS + Tanggal + Random).
   - Catatan: Stok tiket BELUM berkurang di tahap ini. Stok berkurang saat kasir konfirmasi.

3. Upload Bukti Pembayaran (/api/proses_pembayaran.php)
   - Tujuan: Mengunggah bukti transfer untuk verifikasi kasir.
   - Input (POST + File):
     * id_booking (int, wajib)
     * bukti_pembayaran (file, wajib, format JPG/PNG, maks 2MB)
   - Proses:
     * Validasi tipe file dan ukuran.
     * Memindahkan file ke folder assets/img/bukti/.
     * Mengubah status booking menjadi 'menunggu_verifikasi'.

4. Konfirmasi Pembayaran (/api/proses_konfirmasi.php)
   - Tujuan: Kasir/Admin menyetujui pembayaran customer.
   - Input (POST):
     * id_booking (int, wajib)
     * action (string: 'confirm' atau 'reject')
   - Proses (Jika Confirm):
     * Status booking berubah jadi 'confirmed'.
     * Stok tiket berkurang sesuai jumlah booking.
     * Sistem otomatis membuat data E-Tiket dengan status 'valid'.

5. Validasi E-Tiket (/api/proses_validasi.php)
   - Tujuan: Memastikan tiket yang dibawa customer asli dan belum dipakai.
   - Input (POST):
     * kode_booking (string, wajib)
   - Output:
     * Jika Valid: Menampilkan detail booking & mengubah status E-Tiket jadi 'terpakai'.
     * Jika Invalid: Menampilkan pesan error (Kode salah / Sudah terpakai / Kedaluwarsa).

C. KEAMANAN & VALIDASI API

1. Autentikasi Session: Setiap endpoint sensitif (Booking, Pembayaran) mengecek apakah user sudah login via session_start() dan cek_login().
2. SQL Injection Prevention: Semua input user diproses menggunakan Prepared Statements (PDO) untuk mencegah manipulasi database.
3. File Upload Security: Validasi ketat terhadap MIME type dan ekstensi file untuk mencegah upload script berbahaya.
4. Error Handling: Menggunakan try-catch block. Pesan error teknis tidak ditampilkan langsung ke user, melainkan dicatat di server log.

D. CONTOH RESPONS SISTEM

Respons Sukses (Redirect):
Header Location: ../customer/riwayat.php?success=Booking+berhasil!

Respons Gagal (Redirect):
Header Location: ../customer/booking.php?error=Stok+tiket+habis!