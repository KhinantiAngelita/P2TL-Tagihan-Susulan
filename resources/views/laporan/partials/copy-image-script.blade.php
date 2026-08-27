<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
/**
 * Render elemen (id={elId}, harus berupa <div> pembungkus) jadi gambar
 * PNG lewat html2canvas, lalu disalin ke clipboard.
 *
 * PENDEKATAN: capture dilakukan dari SALINAN (clone) elemen sumber, yang
 * ditempel ke dalam <iframe> offscreen (di luar layar) — BUKAN
 * memodifikasi elemen live yang sedang ditampilkan ke user, dan BUKAN
 * cuma ditempel ke document.body utama. Riwayat masalah yang sudah
 * diperbaiki sampai versi ini:
 *
 * 1. Tabel kepotong di kanan — panggung offscreen lebarnya "fit-content"
 *    (mengikuti isi tabel), bukan lebar tetap dari CSS grid card.
 * 2. Area kosong hitam/transparan yang gak rapi — ukuran capture diambil
 *    SETELAH clone selesai di-layout, bukan dari elemen live yang gaya-
 *    nya baru saja diubah (rawan telat/reflow belum kelar).
 * 3. Tabel keremes sempit + kotak hitam kosong — gara-gara `position:
 *    sticky` (dipakai di header/footer/kolom pertama banyak tabel).
 *    html2canvas dikenal salah hitung layout kalau ada elemen sticky,
 *    jadi semua elemen sticky di clone diubah jadi `relative` (bukan
 *    `static`, biar pseudo-element ::after yang posisinya absolute
 *    tetap punya containing block yang benar) sebelum di-capture.
 * 4. [DIHAPUS] Sebelumnya ada langkah tambahan buat nyisipin label
 *    manual di tiap sel tabel (buat jaga-jaga kalau capture kepaksa
 *    render mode-kartu HP, karena label mode-kartu itu asalnya dari CSS
 *    `::before { content: attr(data-label) }` yang gak bisa dibaca
 *    html2canvas). Sekarang DIHAPUS total — sejak poin 5 di bawah
 *    (capture selalu dipaksa tampilan desktop lewat iframe), thead asli
 *    SELALU kepake, jadi langkah itu cuma bikin teks label dobel di
 *    tiap baris (sekali dari header asli, sekali dari span buatan).
 * 5. [BARU] Hasil capture ikut tampilan mode-kartu HP padahal maunya
 *    selalu tampilan tabel desktop (dengan header "PERIODE TERPILIH",
 *    kolom Target/Realisasi berwarna, dst) — soalnya `@media` query itu
 *    ngecek lebar VIEWPORT ASLI (layar HP), bukan lebar konten yang mau
 *    di-capture. Fix-nya: clone di-render di dalam <iframe> offscreen
 *    yang lebarnya di-set TETAP LEBAR (jauh di atas semua breakpoint
 *    mobile di CSS project ini), karena iframe punya viewport-nya
 *    SENDIRI yang terpisah dari window utama — jadi `@media` di
 *    dalamnya PASTI selalu ke-evaluate sebagai "desktop", apapun lebar
 *    layar aslinya.
 * 6. [BARU] Judul & info filter belum ikut ke-capture — elemen yang
 *    di-capture (id={elId}) itu cuma bungkus tabelnya doang, sedangkan
 *    header kartu asli (mis. `.trend-table-head`, `.goltarif-card-head`)
 *    itu SAUDARA (sibling), bukan anak dari situ, jadi nggak ikut ke-
 *    grab. Sebelumnya diakalin pakai banner biru buatan sendiri —
 *    sekarang header ASLINYA yang di-clone (dicari otomatis lewat
 *    konvensi class yang diakhiri "-head", konsisten dipakai di semua
 *    card di app ini), tombol "Salin Gambar"-nya dibuang, dan kalau ada
 *    dropdown pemilih tampilan (mis. Gol Tarif Prabayar) diganti jadi
 *    teks biasa. Info filter (Tahun/ULP aktif) ditambahkan sebagai satu
 *    baris kecil tambahan di bawah subjudulnya.
 *
 * Elemen <canvas> (chart pie) butuh perlakuan khusus: cloneNode() TIDAK
 * menyalin isi gambar canvas, jadi tiap canvas asli dikonversi ke <img>
 * (via toDataURL) di dalam clone-nya sebelum di-capture.
 */
function salinTabelGambar(elId, btnEl, judul) {
    var sumber = document.getElementById(elId);
    if (!sumber) return;

    if (typeof html2canvas === 'undefined') {
        alert('Komponen pembuat gambar belum termuat. Coba muat ulang halaman.');
        return;
    }

    var teksAsli = btnEl.innerHTML;
    btnEl.disabled = true;
    btnEl.innerHTML = '⏳ Memproses...';

    var PADDING          = 24;   // px — spasi putih di sekeliling hasil gambar
    var RADIUS            = 16;   // px — kelengkungan sudut hasil gambar
    var CHART_SPACER_H    = 60;   // px — tinggi spacer KHUSUS DI GAMBAR antara chart & tabel di bawahnya
    var IFRAME_WIDTH       = 1400; // px — lebih lebar dari semua breakpoint mobile di CSS project ini, biar @media selalu ke-anggap desktop

    // ---- 0. Iframe offscreen: viewport-nya sendiri, terpisah dari
    // window utama, dan disalin dulu semua stylesheet halaman ke
    // dalamnya biar semua CSS (termasuk breakpoint-nya) ikut aktif. ----
    var iframe = document.createElement('iframe');
    iframe.style.cssText =
        'position:fixed; left:-99999px; top:0; width:' + IFRAME_WIDTH + 'px; height:1200px; ' +
        'border:0; visibility:hidden;';
    document.body.appendChild(iframe);

    var iwin = iframe.contentWindow;
    var idoc = iframe.contentDocument;

    Array.prototype.forEach.call(document.querySelectorAll('link[rel="stylesheet"], style'), function (node) {
        idoc.head.appendChild(node.cloneNode(true));
    });
    idoc.body.style.cssText = 'margin:0; background:transparent;';

    // ---- 1. Cari header kartu ASLI (saudara dari elemen sumber, class
    // diakhiri "-head" — konvensi yang konsisten dipakai di semua card
    // di app ini: .trend-table-head, .goltarif-card-head, dst), lalu
    // clone dia. Tombol & dropdown di dalamnya dibersihkan biar gak ikut
    // muncul di gambar. ----
    var headerAsli = sumber.parentElement
        ? sumber.parentElement.querySelector('[class$="-head"]')
        : null;
    var headerWrap  = null;
    var headerClone = null;
    if (headerAsli) {
        headerClone = headerAsli.cloneNode(true);
        Array.prototype.forEach.call(headerClone.querySelectorAll('button, .copy-btn'), function (b) {
            b.remove();
        });
        // Dropdown pemilih tampilan (mis. "Gol Tarif Prabayar" / "Gol per
        // Daya Prabayar") diganti jadi teks biasa sesuai judul yang aktif.
        Array.prototype.forEach.call(headerClone.querySelectorAll('select'), function (sel) {
            var span = document.createElement('span');
            span.textContent = judul || sel.selectedOptions[0].text;
            span.style.cssText = sel.getAttribute('style') || '';
            span.style.fontWeight = '700';
            sel.parentNode.replaceChild(span, sel);
        });
        // Elemen dekoratif kayak svg panah dropdown udah gak relevan lagi
        // begitu select-nya diganti jadi teks; sisa svg lain (ikon) dibiarkan.
        Array.prototype.forEach.call(headerClone.querySelectorAll('.goltarif-title-select-wrap svg'), function (s) {
            s.remove();
        });
        // Header dibungkus wrapper POLOS (blok biasa, bukan flex) — biar
        // baris info filter yang ditambahkan nanti (lihat bawah) jadi
        // baris block BARU DI BAWAH header, bukan ikut kesedot masuk jadi
        // flex-item tambahan di baris header itu sendiri (itu penyebab
        // tampilannya mepet/dempetan sama badge tahun yang udah ada).
        headerWrap = document.createElement('div');
        headerWrap.appendChild(headerClone);
    }

    // Info filter aktif (Tahun/ULP dst — teks halaman, bukan bagian dari
    // header kartu) ditambahkan sebagai baris BARU di bawah header. Kalau
    // headernya sendiri udah nampilin badge tahun/filter (mis.
    // .goltarif-year-badge), baris ini di-skip biar gak dobel nampilin
    // info yang sama.
    var infoEl   = document.getElementById('filter-info-text');
    var infoText = infoEl ? infoEl.textContent.trim() : '';
    var infoBaris = null;
    var sudahAdaBadge = headerClone ? headerClone.querySelector('[class*="badge"]') : null;
    if (infoText && !sudahAdaBadge) {
        infoBaris = document.createElement('div');
        infoBaris.textContent = infoText;
        infoBaris.style.cssText = 'font-size:12px; color:#6b7690; margin:10px 0 0; font-family:inherit;';
    }

    // ---- 2. Clone konten tabel, lepas semua batas scroll/tinggi &
    // lebarkan tabel ke max-content di dalam clone-nya saja. ----
    var clone = sumber.cloneNode(true);

    Array.prototype.forEach.call(clone.querySelectorAll('[class*="-scroll"]'), function (w) {
        w.style.overflow   = 'visible';
        w.style.overflowX  = 'visible';
        w.style.overflowY  = 'visible';
        w.style.maxHeight  = 'none';
    });
    Array.prototype.forEach.call(clone.querySelectorAll('table'), function (t) {
        // PERBAIKAN: dulu dipaksa `width: max-content` biar tabel lebar
        // (mis. Gol Tarif/ULP) gak kepotong pas discroll. Tapi buat tabel
        // yang nggak lebar-lebar amat (mis. tabel Data Pencapaian, cuma 5
        // kolom), efeknya malah bikin tabel jadi LEBIH SEMPIT dari
        // konten lain di atasnya (mis. baris chip ringkasan) — background
        // warna baris row-best/row-worst jadi berhenti duluan, nyisain
        // area putih kosong di kanan sampai tepi kartu, kelihatan gak
        // nyambung. `width:auto` + `min-width:100%` lebih aman: tabel
        // tetap ngisi penuh minimal selebar container-nya (sama kayak
        // versi live yang pakai width:100%), tapi masih bisa melebar
        // lagi kalau kontennya genuinely butuh lebih lebar dari itu.
        t.style.width = 'auto';
        t.style.minWidth = '100%';
    });

    // ---- 1c. Beberapa tabel (mis. Gol Tarif Prabayar/Paskabayar) ngatur
    // warna header/footer-nya lewat selector yang butuh CLASS ANCESTOR
    // tertentu, contoh: `.goltarif-card.tone-prabayar .goltarif-table
    // thead th { background:#e4ebfb; color:#1d4ed8; }`. Elemen yang
    // di-capture (id={elId}) itu ANAK dari `.goltarif-card.tone-prabayar`,
    // BUKAN elemen itu sendiri — jadi begitu di-clone berdiri sendiri
    // (tanpa ikut parent card-nya), konteks class ancestor itu ilang,
    // aturan CSS di atas gak nyantol lagi, dan header balik ke gaya
    // dasar yang CUMA nyetel `color:#fff` tanpa background sama sekali
    // — hasilnya teks putih di atas background kosong, nyaris gak
    // kebaca. Fix-nya: salin warna background & teks ASLI (dibaca dari
    // elemen SUMBER yang masih live, masih punya konteks ancestor
    // lengkap) langsung jadi inline style di clone — jadi warnanya
    // kekunci, gak gantung ke ancestor context lagi sama sekali. ----
    var selSelWarna = 'thead th, tfoot td';
    var thTdAsli  = sumber.querySelectorAll(selSelWarna);
    var thTdClone = clone.querySelectorAll(selSelWarna);
    Array.prototype.forEach.call(thTdAsli, function (elAsli, i) {
        var elClone = thTdClone[i];
        if (!elClone) return;
        var cs = getComputedStyle(elAsli);
        elClone.style.backgroundColor = cs.backgroundColor;
        elClone.style.color           = cs.color;
    });

    // Sisipkan elemen spacer BARU (bukan mengandalkan margin) tepat
    // setelah tiap wrapper chart, biar jaraknya pasti kelihatan di
    // gambar — gak tergantung nilai margin CSS yang mungkin gak
    // terbaca benar dari node yang belum nempel ke DOM.
    Array.prototype.forEach.call(clone.querySelectorAll('[class*="chart-wrap"]'), function (w) {
        var spacer = document.createElement('div');
        spacer.style.cssText = 'height:' + CHART_SPACER_H + 'px; width:100%; flex-shrink:0;';
        w.parentNode.insertBefore(spacer, w.nextSibling);
    });

    // (Fallback label mode-kartu yang dulu ada di sini sudah DIHAPUS —
    // sejak capture selalu dipaksa tampilan desktop lewat iframe [lihat
    // poin 5 di komentar atas], thead asli SELALU kepake, jadi span
    // label manual itu cuma bikin teks dobel: sekali dari header tabel
    // asli, sekali lagi dari span buatan ini di tiap sel.)


    // canvas ASLI (yang masih tampil live), karena clone canvas kosong. ----
    var canvasAsli  = sumber.querySelectorAll('canvas');
    var canvasClone = clone.querySelectorAll('canvas');
    Array.prototype.forEach.call(canvasAsli, function (asli, i) {
        var target = canvasClone[i];
        if (! target) return;
        var img = document.createElement('img');
        img.src = asli.toDataURL('image/png');
        img.style.width  = asli.style.width  || (asli.width  + 'px');
        img.style.height = asli.style.height || (asli.height + 'px');
        target.parentNode.replaceChild(img, target);
    });

    // Bersihkan semua id dalam clone (header & tabel) biar gak dobel
    // sama versi live yang masih tampil di halaman.
    [headerClone, clone].forEach(function (root) {
        if (!root) return;
        if (root.id) root.removeAttribute('id');
        Array.prototype.forEach.call(root.querySelectorAll('[id]'), function (el) {
            el.removeAttribute('id');
        });
    });

    // ---- 4. Panggung di dalam iframe: lebar auto mengikuti konten
    // (display:inline-block), bukan lebar tetap — jadi apapun lebar
    // tabel di dalamnya, gak ada yang kepotong oleh overflow:hidden. ----
    var panggung = idoc.createElement('div');
    panggung.style.cssText =
        'display:inline-block; background:#ffffff; padding:' + PADDING + 'px;' +
        'border-radius:' + RADIUS + 'px; border:1px solid #e2e6f0;' +
        'box-sizing:border-box; overflow:hidden; font-family:inherit;';

    if (headerWrap) {
        headerWrap.style.margin = '-' + PADDING + 'px -' + PADDING + 'px ' + PADDING + 'px -' + PADDING + 'px';
        headerWrap.style.padding = PADDING + 'px ' + PADDING + 'px 0';
        panggung.appendChild(headerWrap);
        if (infoBaris) headerWrap.appendChild(infoBaris);
    } else if (infoBaris || judul) {
        // Fallback kalau header aslinya gak ketemu (mis. struktur halaman
        // beda) — tetap tampilkan minimal judul yang dikirim ke fungsi ini.
        var headerFallback = document.createElement('div');
        headerFallback.style.cssText = 'margin-bottom:16px;';
        headerFallback.innerHTML = '<div style="font-size:16px;font-weight:800;color:#1b2559;font-family:inherit;">' + (judul || '') + '</div>';
        if (infoBaris) headerFallback.appendChild(infoBaris);
        panggung.appendChild(headerFallback);
    }
    panggung.appendChild(clone);
    idoc.body.appendChild(panggung);

    // ---- 5. Lepas SEMUA position:sticky di dalam panggung (header +
    // tabel), ganti jadi position:relative (bukan static — biar
    // pseudo-element ::after yang posisinya absolute tetap punya
    // containing block yang benar, yaitu sel itu sendiri). html2canvas
    // dikenal salah hitung layout kalau ada elemen sticky. ----
    Array.prototype.forEach.call(panggung.querySelectorAll('*'), function (el) {
        if (iwin.getComputedStyle(el).position === 'sticky') {
            el.style.position = 'relative';
            el.style.top    = 'auto';
            el.style.left   = 'auto';
            el.style.right  = 'auto';
            el.style.bottom = 'auto';
            el.style.zIndex = 'auto';
        }
    });

    // Kasih 1 frame biar iframe selesai layout panggung (lebar tabel,
    // posisi elemen, breakpoint desktop, dst) sebelum diukur & di-capture.
    iwin.requestAnimationFrame(function () {
        var lebar  = panggung.scrollWidth;
        var tinggi = panggung.scrollHeight;

        html2canvas(panggung, {
            scale: 2,
            backgroundColor: '#ffffff',
            useCORS: true,
            width: lebar,
            height: tinggi,
            windowWidth: IFRAME_WIDTH,
            windowHeight: tinggi,
        }).then(function (canvas) {
            document.body.removeChild(iframe);
            canvas.toBlob(function (blob) {
                if (!blob) { gagal(); return; }

                if (navigator.clipboard && window.ClipboardItem && window.isSecureContext) {
                    navigator.clipboard.write([
                        new ClipboardItem({ 'image/png': blob })
                    ]).then(sukses, function () {
                        unduhGambar(blob);
                    });
                } else {
                    unduhGambar(blob);
                }
            }, 'image/png');
        }).catch(function (err) {
            document.body.removeChild(iframe);
            console.error('Gagal membuat gambar:', err);
            gagal();
        });
    });

    function sukses() {
        btnEl.innerHTML = '✅ Tersalin';
        setTimeout(function () {
            btnEl.innerHTML = teksAsli;
            btnEl.disabled = false;
        }, 1500);
    }

    function unduhGambar(blob) {
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = elId + '.png';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        btnEl.innerHTML = '⬇️ Diunduh';
        setTimeout(function () {
            btnEl.innerHTML = teksAsli;
            btnEl.disabled = false;
        }, 1500);
    }

    function gagal() {
        alert('Gagal membuat gambar. Silakan screenshot manual.');
        btnEl.innerHTML = teksAsli;
        btnEl.disabled = false;
    }
}
</script>