# Cara Pakai File Ini

Zip ini BUKAN project Laravel lengkap (tidak ada folder `vendor/`, `public/index.php`, dsb) —
isinya cuma file-file custom (migration, model, import, controller, route, view) yang tinggal
di-drop ke atas project Laravel kosong yang kamu buat sendiri lewat Composer.

## Langkah

1. Buat project Laravel baru dulu (lihat perintah di chat / panduan lengkap).
2. Extract isi zip ini, lalu **copy semua folder & file di dalamnya ke root project Laravel**
   kamu (timpa `routes/web.php` yang default).
3. Install package Excel: `composer require maatwebsite/excel`
4. Setting `.env` (DB_DATABASE, DB_USERNAME, DB_PASSWORD).
5. Jalankan `php artisan migrate`.
6. Jalankan `php artisan storage:link` lalu `php artisan serve`.

Detail lengkap tiap langkah ada di file `panduan-laravel-p2tl.md` yang sudah dikirim sebelumnya.
