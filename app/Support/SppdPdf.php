<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Dompdf\Canvas;
use Dompdf\Frame;

/**
 * Render dokumen SPPD (kertas 33 x 24,14 cm: halaman depan kiri, halaman belakang kanan).
 *
 * Kotak tanda tangan II-V di halaman belakang dibuat setinggi mungkin, mengisi sisa ruang
 * sampai tepat di atas footer. Sisa ruang itu berubah-ubah: nama jabatan bisa 1-3 baris
 * (tercetak dua kali, di I dan VI), QR dan NIP bisa ada atau tidak, nama tempat bisa turun
 * baris. Karena itu dirender dua kali: render ukur dengan kotak minimum untuk mencatat posisi
 * akhir tabel dan posisi footer, lalu render akhir dengan sisa ruang dibagi rata ke empat kotak.
 */
class SppdPdf
{
    public const VIEW = 'exports.sppd';

    public const PAPER = [0, 0, 935.43, 684.45];

    /**
     * Jarak (px) garis nama kotak II-V dari dasar kotak. Ruang di bawah garis untuk NIP yang
     * ditulis tangan; semula 7 px dan terlalu sempit (masukan APIP Inspektorat, 2026-11-20).
     */
    public const SIGN_LINE_BOTTOM = 18;

    /**
     * Tinggi kotak (px) saat render ukur; sedikit di atas tinggi isi kotak (4 baris teks + garis
     * + ruang NIP di bawah garis). Tidak mengubah hasil akhir selama muat satu halaman: sisa ruang
     * tetap dibagi rata dari tinggi ini.
     */
    public const MIN_BOX_HEIGHT = 64 + (self::SIGN_LINE_BOTTOM - 7);

    /** Tinggi kotak (px) bila pengukuran gagal; sama dengan bawaan di view. */
    public const DEFAULT_BOX_HEIGHT = 92;

    /** Jarak (pt) antara akhir tabel dan garis footer. */
    private const GAP_ABOVE_FOOTER_PT = 8;

    /**
     * @param  array  $data  data view exports.sppd (sppd, user, is_main, pdfData)
     */
    public static function make(array $data): PdfDocument
    {
        return self::load($data, self::fittedBoxHeight($data));
    }

    /**
     * Tinggi kotak II-V (px) yang membuat tabel belakang berakhir tepat di atas footer.
     * Bila pengukuran gagal, kembali ke nilai bawaan view.
     */
    public static function fittedBoxHeight(array $data): int
    {
        $marks = [];
        $pdf = self::load($data + ['measureBackOnly' => true], self::MIN_BOX_HEIGHT);
        $dompdf = $pdf->getDomPDF();
        $dompdf->setCallbacks([[
            'event' => 'begin_frame',
            'f' => function (Frame $frame, Canvas $canvas) use (&$marks) {
                $node = $frame->get_node();
                if (! $node instanceof \DOMElement) {
                    return;
                }
                // Footer (position: fixed) dirender ulang di setiap halaman dan dompdf membuang
                // atribut id-nya, jadi dikenali dari tag <footer>; cukup yang pertama.
                $key = $node->nodeName === 'footer' ? 'footer' : $node->getAttribute('id');
                if (in_array($key, ['sppd-back-end', 'footer'], true) && ! isset($marks[$key])) {
                    $marks[$key] = ['page' => $canvas->get_page_number(), 'y' => (float) $frame->get_position('y')];
                }
            },
        ]]);
        $dompdf->render();

        $end = $marks['sppd-back-end'] ?? null;
        $footer = $marks['footer'] ?? null;
        if (! $end || ! $footer) {
            return self::DEFAULT_BOX_HEIGHT;
        }
        // Sudah meluap ke halaman berikutnya walau kotak minimum: tidak ada ruang untuk dibagi.
        if ($end['page'] > 1) {
            return self::MIN_BOX_HEIGHT;
        }

        $slackPt = $footer['y'] - self::GAP_ABOVE_FOOTER_PT - $end['y'];
        $slackPx = $slackPt * $dompdf->getOptions()->getDpi() / 72;

        return max(self::MIN_BOX_HEIGHT, self::MIN_BOX_HEIGHT + (int) floor($slackPx / 4));
    }

    private static function load(array $data, int $boxHeight): PdfDocument
    {
        return Pdf::loadView(self::VIEW, $data + ['signBoxHeight' => $boxHeight])
            ->setPaper(self::PAPER);
    }
}
