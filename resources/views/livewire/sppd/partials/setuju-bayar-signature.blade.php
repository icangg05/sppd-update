{{-- Isi sel tanda tangan Setuju Bayar di pratinjau modal. Isi awal dari server ($preview);
     setelah Alpine aktif, x-text/x-show mengikuti isian (signer, role, blank) tanpa request.
     $gap = tinggi ruang tanda tangan (px) sesuai dokumen cetaknya.
     $roleExpr/$roleText opsional: label jabatan dengan aturan lain (mis. kuitansi). --}}
<p>{{ $heading }}<br><span x-text="{{ $roleExpr ?? 'role' }}">{{ $roleText ?? $preview['role'] }}</span></p>
<div style="height: {{ $gap }}px;"></div>
<p><span class="font-bold underline" x-text="signer?.name || blank">{{ $preview['name'] ?? $blank }}</span><span x-show="signer?.nip" @style(['display: none' => ! $preview['nip']])><br>NIP. <span x-text="signer?.nip">{{ $preview['nip'] }}</span></span></p>
