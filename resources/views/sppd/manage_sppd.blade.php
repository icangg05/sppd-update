@extends('layouts.app')
@section('title', 'Kelola SPPD')

@section('content')
<div class="p-1 space-y-6">

  {{-- Header --}}
  <div class="flex items-center justify-between">
    <div>
      <h1 class="text-lg font-bold text-slate-800 uppercase tracking-wide border-b-2 border-emerald-500 inline-block pb-1">
        <i class="fa-solid fa-file-contract mr-2 text-emerald-600"></i>Kelola SPPD
      </h1>
    </div>
    <a href="{{ route('sppd.next', $sppd) }}" class="inline-flex items-center gap-2 rounded border border-slate-300 bg-white px-4 py-2 text-xs font-bold text-slate-700 shadow-sm transition hover:bg-slate-50">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="rounded border border-slate-200 bg-white shadow-md overflow-hidden">
    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">

      {{-- Daftar Personel --}}
      <div class="space-y-3">
        <div class="flex items-center justify-between mb-2">
          <p class="text-[10px] font-bold uppercase text-slate-400">Daftar Pelaksana & Pengikut</p>
          <span class="text-[10px] font-bold bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">
            {{ 1 + $sppd->followers->count() }} Orang
          </span>
        </div>

        @if ($sppd->followers->count() > 4)
          <div class="relative mb-3">
            <input type="text" id="personel-search-input" placeholder="Cari nama personel..."
              class="w-full rounded border border-slate-300 bg-white pl-8 pr-3 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:border-cyan-500 focus:outline-hidden focus:ring-1 focus:ring-cyan-500" />
            <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-[10px]"></i>
          </div>
        @endif

        <div class="space-y-3 max-h-[380px] overflow-y-auto scrollbar-thin pr-1" id="personel-list">
          {{-- Main Pelaksana --}}
          <div class="flex items-center justify-between p-3 bg-slate-50 border border-slate-100 rounded-lg" data-search-term="pelaksana {{ strtolower($sppd->user->name) }}">
            <div class="flex items-center gap-3">
              <span class="text-xs font-bold text-slate-400 w-20">PELAKSANA:</span>
              <span class="text-sm font-bold text-slate-800">{{ $sppd->user->name }}</span>
            </div>
            <a href="{{ route('sppd.stream.sppd', $sppd) }}" target="_blank"
              class="inline-flex items-center gap-1.5 rounded bg-cyan-600 px-3 py-1.5 text-[10px] font-bold text-white transition hover:bg-cyan-700">
              <i class="fa-solid fa-print"></i> CETAK
            </a>
          </div>

          {{-- Pengikut --}}
          @foreach ($sppd->followers as $f)
            <div class="flex items-center justify-between p-3 bg-white border border-slate-100 rounded-lg" data-search-term="pengikut {{ strtolower($f->user->name) }}">
              <div class="flex items-center gap-3">
                <span class="text-xs font-bold text-slate-400 w-20">PENGIKUT:</span>
                <span class="text-sm font-semibold text-slate-700">{{ $f->user->name }}</span>
              </div>
              <a href="{{ route('sppd.stream.sppd', ['sppd' => $sppd->id, 'user_id' => $f->user_id]) }}" target="_blank"
                class="inline-flex items-center gap-1.5 rounded bg-slate-600 px-3 py-1.5 text-[10px] font-bold text-white transition hover:bg-slate-700">
                <i class="fa-solid fa-print"></i> CETAK
              </a>
            </div>
          @endforeach
        </div>
      </div>

      {{-- Status TTE --}}
      <div class="space-y-4">
        <div class="flex justify-between items-center py-2 border-b border-slate-100">
          <span class="text-xs font-bold text-slate-400 uppercase">Tanggal SPPD</span>
          <span class="text-sm font-bold text-slate-800">{{ $sppd->sppd_date?->translatedFormat('d F Y') ?? $sppd->created_at->translatedFormat('d F Y') }}</span>
        </div>

        @php $sppdSignature = $sppd->signatureFor('sppd'); @endphp

        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4" @if($sppdSignature) data-tte-signature-id="{{ $sppdSignature->id }}" @endif>
          <div class="flex justify-between items-start">
            <div>
              <p class="text-xs font-bold text-slate-500 uppercase">Status TTE SPPD</p>
              <div class="tte-badge-container mt-1.5">
                @if ($sppdSignature && $sppdSignature->status->value === 'signed')
                  <span class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                    Sudah Ditandatangani
                  </span>
                @elseif ($sppdSignature && $sppdSignature->status->value === 'processing')
                  <span class="bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5 animate-pulse">
                    <i class="fa-solid fa-spinner animate-spin text-[10px]"></i> Sedang Diproses
                  </span>
                @elseif ($sppdSignature && $sppdSignature->status->value === 'rejected')
                  <span class="bg-rose-100 text-rose-800 border border-rose-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                    Gagal TTE
                  </span>
                @else
                  <span class="bg-slate-100 text-slate-500 border border-slate-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">
                    Belum Diproses
                  </span>
                @endif
              </div>
            </div>

            @if ($sppdSignature)
              <form action="{{ route('sppd.reset-tte', ['sppd' => $sppd->id, 'type' => 'sppd']) }}" method="POST"
                onsubmit="return confirm('\u26a0\ufe0f PERINGATAN RESET TTE SPPD\n\nTindakan ini akan:\n\u2022 Menghapus SEMUA tanda tangan elektronik pada dokumen SPPD (Pelaksana + seluruh Pengikut)\n\u2022 Menghapus semua file PDF yang sudah bertanda tangan\n\u2022 Status SPPD akan dikembalikan ke antrian persetujuan\n\n Pejabat penandatangan harus mengulang proses TTE dari awal untuk semua dokumen.\n\nApakah Anda yakin ingin melanjutkan?')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded bg-rose-100 px-3 py-1.5 text-[10px] font-bold text-rose-700 hover:bg-rose-200 transition">
                  <i class="fa-solid fa-rotate-left"></i> RESET TTE
                </button>
              </form>
            @endif
          </div>

          @if ($sppdSignature?->signed_file_path)
            <div class="mt-4">
              <a href="{{ route('sppd.sign.download', ['sppd' => $sppd->id, 'signature' => $sppdSignature->id]) }}"
                class="inline-flex items-center gap-2 rounded bg-emerald-600 px-3 py-2 text-[11px] font-bold text-white hover:bg-emerald-700 transition">
                <i class="fa-solid fa-file-pdf"></i> DOWNLOAD PDF TTE
              </a>
            </div>
          @endif

          <div class="tte-error-container mt-2 {{ ($sppdSignature && $sppdSignature->error_message) ? '' : 'hidden' }}">
            <p class="text-[10px] text-rose-600 font-medium">
              <i class="fa-solid fa-circle-exclamation mr-1"></i> Error: <span class="error-message-text">{{ $sppdSignature?->error_message }}</span>
            </p>
          </div>
        </div>
      </div>
    </div>

    {{-- Footer Note --}}
    <div class="bg-slate-50 border-t border-slate-100 p-4 flex items-center gap-3 text-[11px] text-slate-500 italic">
      <i class="fa-solid fa-circle-info text-slate-400"></i>
      Sistem menghasilkan dokumen PDF yang sudah siap cetak atau ditandatangani secara elektronik.
    </div>
  </div>
</div>
@endsection

@push('scripts')
  <script>
    @if ($sppdSignature && $sppdSignature->status->value === 'processing')
    (function() {
      let pollInterval = setInterval(checkTteStatus, 5000);

      function checkTteStatus() {
        fetch("{{ route('sppd.sign.batch-status', $sppd) }}")
          .then(response => response.json())
          .then(data => {
            if (data.signatures) {
              const sig = data.signatures.find(s => s.id == "{{ $sppdSignature->id }}");
              if (sig) {
                const container = document.querySelector(`[data-tte-signature-id="${sig.id}"]`);
                if (container) {
                  const badgeContainer = container.querySelector('.tte-badge-container');
                  const errorContainer = container.querySelector('.tte-error-container');
                  const errorMessageText = container.querySelector('.error-message-text');

                  if (sig.status === 'signed') {
                    badgeContainer.innerHTML = `<span class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">Sudah Ditandatangani</span>`;
                    errorContainer.classList.add('hidden');
                  } else if (sig.status === 'processing') {
                    badgeContainer.innerHTML = `<span class="bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5 animate-pulse"><i class="fa-solid fa-spinner animate-spin text-[10px]"></i> Sedang Diproses</span>`;
                    errorContainer.classList.add('hidden');
                  } else if (sig.status === 'rejected') {
                    badgeContainer.innerHTML = `<span class="bg-rose-100 text-rose-800 border border-rose-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">Gagal TTE</span>`;
                    if (sig.error_message) {
                      errorMessageText.textContent = sig.error_message;
                      errorContainer.classList.remove('hidden');
                    }
                  } else {
                    badgeContainer.innerHTML = `<span class="bg-slate-100 text-slate-500 border border-slate-200 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider">Belum Diproses</span>`;
                    errorContainer.classList.add('hidden');
                  }
                }
              }
            }

            if (!data.is_processing) {
              clearInterval(pollInterval);
              setTimeout(() => {
                window.location.reload();
              }, 1000);
            }
          })
          .catch(err => console.error("Error polling TTE status:", err));
      }
    })();
    @endif

    // Personnel search filter
    (function() {
      const searchInput = document.getElementById('personel-search-input');
      if (searchInput) {
        searchInput.addEventListener('input', function() {
          const query = this.value.toLowerCase().trim();
          const items = document.querySelectorAll('#personel-list > div');
          items.forEach(item => {
            const searchTerm = item.getAttribute('data-search-term') || '';
            if (searchTerm.includes(query)) {
              item.style.display = '';
            } else {
              item.style.display = 'none';
            }
          });
        });
      }
    })();
  </script>
@endpush
