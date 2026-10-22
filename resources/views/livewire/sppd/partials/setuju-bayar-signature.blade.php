{{-- Blok tanda tangan Setuju Bayar di pratinjau modal. Isi awal dari server ($preview);
     setelah Alpine aktif, x-text/x-show mengikuti isian (signer, role, blank) tanpa request. --}}
<p>{{ $heading }}<br><span x-text="role">{{ $preview['role'] }}</span></p>
<div class="h-7"></div>
<p><span class="font-bold underline" x-text="signer?.name || blank">{{ $preview['name'] ?? $blank }}</span><span x-show="signer?.nip" @style(['display: none' => ! $preview['nip']])><br>NIP. <span x-text="signer?.nip">{{ $preview['nip'] }}</span></span></p>
