@props([
    'src',
    // Tautan dengan grup yang sama tampil berurutan di galeri (sebelumnya/berikutnya).
    'group' => 'galeri',
    'caption' => null,
])

{{-- Tautan pembuka galeri gambar (x-ui.gallery). Tidak memuat <img>: gambarnya baru
     diunduh saat tautan diklik. Isi tautan (ikon/teks) lewat slot. --}}
<a href="{{ $src }}" data-gallery="{{ $group }}" @if ($caption) data-caption="{{ $caption }}" @endif
    aria-haspopup="dialog" {{ $attributes }}>{{ $slot }}</a>
