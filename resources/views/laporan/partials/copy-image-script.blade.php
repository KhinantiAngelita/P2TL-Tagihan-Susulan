<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
/**
 * Render elemen (id={elId}, harus berupa <div> pembungkus) jadi gambar
 * PNG lewat html2canvas, lalu disalin ke clipboard.
 *
 * PENDEKATAN: capture dilakukan dari SALINAN (clone) elemen sumber di
 * panggung offscreen (di luar layar), BUKAN memodifikasi elemen live
 * yang sedang ditampilkan ke user. Ini memperbaiki 2 masalah versi
 * sebelumnya:
 *
 * 1. Tabel kepotong di kanan — dulu wrapper (target) dikasih
 *    overflow:hidden dengan lebar TETAP (dari CSS grid card), padahal
 *    tabel di dalamnya dilebarkan ke max-content. Sekarang panggung
 *    offscreen lebarnya "fit-content" (mengikuti isi), jadi apapun
 *    lebar tabelnya, gak ada yang kepotong.
 * 2. Area kosong hitam/transparan yang gak rapi — dulu ukuran capture
 *    diambil dari elemen live yang gaya-nya baru saja diubah (rawan
 *    telat/reflow belum kelar). Sekarang ukuran diambil SETELAH clone
 *    selesai di-layout di panggung sendiri, presisi sama persis
 *    dengan kontennya.
 *
 * Elemen <canvas> (chart pie) butuh perlakuan khusus: cloneNode() TIDAK
 * menyalin isi gambar canvas, jadi tiap canvas asli dikonversi ke <img>
 * (via toDataURL) di dalam clone-nya sebelum di-capture.
 *
 * Jarak antara chart & tabel di bawahnya SENGAJA ditambah cuma di dalam
 * clone (bukan di halaman asli): elemen SPACER baru (bukan margin, biar
 * gak tergantung baca getComputedStyle pada node yang belum ter-attach
 * ke DOM — itu sebabnya percobaan margin-bottom sebelumnya efeknya nyaris
 * gak kelihatan) disisipkan tepat setelah tiap elemen ber-class
 * "*chart-wrap*" (mis. .goltarif-chart-wrap).
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

    var PADDING         = 24; // px — spasi putih di sekeliling hasil gambar
    var RADIUS           = 16; // px — kelengkungan sudut hasil gambar
    var CHART_SPACER_H   = 60; // px — tinggi spacer KHUSUS DI GAMBAR antara chart & tabel di bawahnya

    // ---- 1. Clone konten sumber, lepas semua batas scroll/tinggi &
    // lebarkan tabel ke max-content di dalam clone-nya saja. ----
    var clone = sumber.cloneNode(true);

    Array.prototype.forEach.call(clone.querySelectorAll('[class*="-scroll"]'), function (w) {
        w.style.overflow   = 'visible';
        w.style.overflowX  = 'visible';
        w.style.overflowY  = 'visible';
        w.style.maxHeight  = 'none';
    });
    Array.prototype.forEach.call(clone.querySelectorAll('table'), function (t) {
        t.style.width = 'max-content';
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

    // ---- 2. Ganti tiap <canvas> di clone dengan <img> berisi snapshot
    // canvas ASLI (yang masih tampil live), karena clone canvas kosong. ----
    var canvasAsli = sumber.querySelectorAll('canvas');
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

    // Bersihkan semua id dalam clone biar gak dobel sama versi live
    // yang masih tampil di halaman.
    if (clone.id) clone.removeAttribute('id');
    Array.prototype.forEach.call(clone.querySelectorAll('[id]'), function (el) {
        el.removeAttribute('id');
    });

    // ---- 3. Panggung offscreen: lebar auto mengikuti konten
    // (display:inline-block), bukan lebar tetap — jadi apapun lebar
    // tabel di dalamnya, gak ada yang kepotong oleh overflow:hidden. ----
    var infoEl   = document.getElementById('filter-info-text');
    var infoText = infoEl ? infoEl.textContent : '';

    var header = document.createElement('div');
    header.style.cssText =
        'padding:16px 20px; background:#0b3d91;' +
        'margin:-' + PADDING + 'px -' + PADDING + 'px 20px -' + PADDING + 'px;';
    header.innerHTML =
        '<div style="font-size:16px;font-weight:800;color:#fff;font-family:inherit;line-height:1.35;">' + (judul || '') + '</div>' +
        (infoText ? '<div style="font-size:11.5px;color:#cdd8f5;margin-top:4px;font-family:inherit;">' + infoText + '</div>' : '');

    var panggung = document.createElement('div');
    panggung.style.cssText =
        'position:fixed; left:-99999px; top:0; z-index:-1;' +
        'display:inline-block; background:#ffffff; padding:' + PADDING + 'px;' +
        'border-radius:' + RADIUS + 'px; border:1px solid #e2e6f0;' +
        'box-sizing:border-box; overflow:hidden;';

    panggung.appendChild(header);
    panggung.appendChild(clone);
    document.body.appendChild(panggung);

    // Kasih 1 frame biar browser selesai layout panggung (lebar tabel,
    // posisi elemen, dst) sebelum diukur & di-capture.
    requestAnimationFrame(function () {
        var lebar  = panggung.scrollWidth;
        var tinggi = panggung.scrollHeight;

        html2canvas(panggung, {
            scale: 2,
            backgroundColor: '#ffffff',
            useCORS: true,
            width: lebar,
            height: tinggi,
            windowWidth: lebar,
            windowHeight: tinggi,
        }).then(function (canvas) {
            document.body.removeChild(panggung);
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
            document.body.removeChild(panggung);
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