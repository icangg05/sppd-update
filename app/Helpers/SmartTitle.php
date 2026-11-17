<?php

namespace App\Helpers;

/**
 * Smart Title Case untuk teks berbahasa Indonesia.
 *
 * Menangani:
 * - Kata hubung & kata depan tetap huruf kecil
 * - Akronim / singkatan tetap HURUF BESAR semua: yang ada di daftar, maupun yang
 *   diketik pengguna dengan huruf besar (mis. PDIP, HMI, II)
 * - Kata biasa diawali huruf kapital
 */
class SmartTitle
{
    /**
     * Kata hubung & kata depan yang tetap huruf kecil
     * (kecuali berada di posisi pertama kalimat).
     */
    private static array $lowercase = [
        // Konjungsi
        'dan', 'atau', 'tetapi', 'namun', 'melainkan', 'sedangkan',
        'serta', 'maupun', 'bahwa', 'agar', 'supaya', 'karena',
        'sebab', 'sehingga', 'maka', 'jika', 'apabila', 'kalau',
        'walaupun', 'meskipun', 'kendati', 'padahal', 'lalu',
        'kemudian', 'setelah', 'sebelum', 'ketika', 'saat', 'sambil',
        'seraya', 'bahkan', 'hingga', 'sampai',

        // Preposisi
        'di', 'ke', 'dari', 'pada', 'untuk', 'bagi', 'dengan',
        'dalam', 'oleh', 'tentang', 'terhadap', 'antara', 'atas',
        'tanpa', 'demi', 'per', 'via',

        // Kata sandang
        'sang', 'si', 'para', 'kaum',
    ];

    /**
     * Kata depan/sambung yang dibiarkan huruf kecil oleh capitalizeWords(). Sengaja
     * lebih sempit dari $lowercase agar nama tempat (mis. "Jl. Antara") tak ikut kecil.
     */
    private static array $minorWords = ['di', 'ke', 'dari', 'dan', 'yang'];

    /**
     * Akronim / singkatan yang harus selalu HURUF BESAR.
     * Tambahkan sesuai kebutuhan instansi / domain Anda.
     */
    private static array $acronyms = [
        // Lembaga pemerintahan
        'DPRD', 'DPR', 'MPR', 'DPD', 'KPU', 'KPK', 'BPK', 'MA',
        'MK', 'BUMN', 'BUMD', 'BPBD', 'BPKAD', 'BAPPEDA', 'BKPSDM',
        'SKPD', 'OPD', 'ASN', 'PNS', 'PPPK', 'TNI', 'POLRI',
        'UPTD', 'UPT', 'BLUD', 'APBD', 'DPA', 'PPTK', 'BPJS', 'PKK',

        // Kesehatan & pendidikan
        'RSUD', 'RSIA', 'IGD', 'ICU', 'PAUD', 'TK',

        // Dokumen SPPD
        'SPPD', 'SPT', 'TTE',

        // Jabatan & gelar
        'RT', 'RW', 'SD', 'SMP', 'SMA', 'SMK',
        'SE', 'ST', 'SH', 'MM', 'MT', 'MH', 'SKM',

        // Instansi & umum
        'CV', 'PT', 'UD', 'PD', 'RI', 'NKRI', 'DIY', 'DKI',
        'UGM', 'ITB', 'ITS', 'UNM', 'UNHAS', 'UIN', 'IAIN',
        'API', 'NIK', 'NIP', 'NPWP', 'KTP', 'KK',
        'ATM', 'PIN', 'HP', 'TV', 'CCTV', 'GPS', 'IT', 'SDM',
    ];

    /**
     * Konversi string menjadi judul cerdas berbahasa Indonesia.
     *
     * @param string $string Teks yang akan dikonversi
     * @return string Teks dengan kapitalisasi yang benar
     *
     * Kata yang diketik HURUF BESAR semua dianggap singkatan dan dibiarkan, karena
     * singkatan nama organisasi tidak mungkin didaftar semuanya. Pengecualiannya bila
     * seluruh teks diketik huruf besar (Caps Lock): teks dirapikan seperti biasa dan
     * hanya akronim di daftar yang tetap huruf besar.
     *
     * @example
     *   SmartTitle::convert('anggota dprd dan dewan perwakilan')
     *   // → "Anggota DPRD dan Dewan Perwakilan"
     *   SmartTitle::convert('kader PDIP')
     *   // → "Kader PDIP"
     */
    public static function convert(string $string): string
    {
        $acronymMap = self::buildAcronymMap();
        $allCaps    = ! preg_match('/\p{Ll}/u', $string);

        $words     = preg_split('/(\s+)/', trim($string), -1, PREG_SPLIT_DELIM_CAPTURE);
        $result    = [];
        $wordIndex = 0;

        foreach ($words as $part) {
            // Bagian spasi: simpan apa adanya
            if (preg_match('/^\s+$/', $part)) {
                $result[] = $part;
                continue;
            }

            $lower = strtolower($part);

            $result[] = match (true) {
                isset($acronymMap[$lower])          => $acronymMap[$lower],       // Akronim → HURUF BESAR
                ! $allCaps && self::isTypedAcronym($part) => $part,               // Diketik huruf besar → biarkan
                $wordIndex === 0                    => ucfirst($lower),            // Kata pertama → Kapital
                in_array($lower, self::$lowercase)  => $lower,                    // Kata hubung → kecil
                default                             => ucfirst($lower),            // Kata biasa → Kapital
            };

            $wordIndex++;
        }

        return implode('', $result);
    }

    /**
     * Kapitalkan huruf pertama setiap kata tanpa mengubah huruf lainnya, sehingga
     * akronim yang diketik pengguna (mis. KBRI, PEMDI) tetap utuh. Cocok untuk
     * isian bebas seperti alamat & nama tempat. Kata depan/sambung umum (di, ke,
     * dari, dan, yang) di tengah teks tetap huruf kecil.
     *
     * @example
     *   SmartTitle::capitalizeWords('kantor camat di poasia')
     *   // → "Kantor Camat di Poasia"
     */
    public static function capitalizeWords(?string $string): ?string
    {
        if ($string === null) {
            return null;
        }

        return preg_replace_callback(
            '/(^|[\s(\/])(\p{Ll})(\p{L}*)/u',
            function ($m) {
                if ($m[1] !== '' && in_array($m[2] . $m[3], self::$minorWords, true)) {
                    return $m[0];
                }

                return $m[1] . mb_strtoupper($m[2]) . $m[3];
            },
            trim($string),
        );
    }

    /**
     * Tambahkan akronim baru secara dinamis saat runtime.
     *
     * @param string|array $acronym Satu akronim atau array akronim
     * @return void
     *
     * @example
     *   SmartTitle::addAcronym('RSUD');
     *   SmartTitle::addAcronym(['PDAM', 'PLN']);
     */
    public static function addAcronym(string|array $acronym): void
    {
        $items = is_array($acronym) ? $acronym : [$acronym];

        foreach ($items as $item) {
            if (!in_array(strtoupper($item), self::$acronyms)) {
                self::$acronyms[] = strtoupper($item);
            }
        }
    }

    /**
     * Tambahkan kata hubung / kata depan baru secara dinamis saat runtime.
     *
     * @param string|array $word Satu kata atau array kata
     * @return void
     *
     * @example
     *   SmartTitle::addLowercase('pun');
     *   SmartTitle::addLowercase(['pula', 'adapun']);
     */
    public static function addLowercase(string|array $word): void
    {
        $items = is_array($word) ? $word : [$word];

        foreach ($items as $item) {
            if (!in_array(strtolower($item), self::$lowercase)) {
                self::$lowercase[] = strtolower($item);
            }
        }
    }

    /** Kata yang diketik huruf besar semua (minimal dua huruf), mis. PDIP, (RSUD), II. */
    private static function isTypedAcronym(string $word): bool
    {
        return preg_match('/\p{Lu}.*\p{Lu}/u', $word) === 1
            && preg_match('/\p{Ll}/u', $word) === 0;
    }

    /**
     * Buat map: lowercase(akronim) => akronim asli.
     *
     * @return array<string, string>
     */
    private static function buildAcronymMap(): array
    {
        $map = [];

        foreach (self::$acronyms as $acronym) {
            $map[strtolower($acronym)] = strtoupper($acronym);
        }

        return $map;
    }
}