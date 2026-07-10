{{-- Hasil verifikasi BSrE. Menerima: $result (array dari BsreVerificationService::verify) --}}
@if (! ($result['ok'] ?? false))
  <div class="rounded border border-rose-200 bg-rose-50 p-4">
    <p class="flex items-center gap-1.5 text-sm font-medium text-rose-700">
      <i class="fa-solid fa-circle-exclamation"></i>
      {{ $result['error'] ?? 'Verifikasi gagal.' }}
    </p>
    @unless ($result['available'] ?? true)
      <p class="mt-1 text-xs text-rose-600/80">Verifikasi BSrE belum diaktifkan. Hubungi administrator untuk mengonfigurasi kredensial.</p>
    @endunless
  </div>
@else
  @php $bsreValid = $result['valid'] ?? null; @endphp
  <div class="overflow-hidden rounded border border-slate-200 bg-white shadow-sm">
    {{-- Banner ringkasan --}}
    <div class="flex flex-col items-center gap-3 px-6 py-8 text-center
      {{ $bsreValid === true ? 'bg-emerald-50' : ($bsreValid === false ? 'bg-rose-50' : 'bg-slate-50') }}">
      <div class="flex size-14 items-center justify-center rounded-full
        {{ $bsreValid === true ? 'bg-emerald-100 text-emerald-600' : ($bsreValid === false ? 'bg-rose-100 text-rose-600' : 'bg-slate-200 text-slate-600') }}">
        <i class="fa-solid {{ $bsreValid === true ? 'fa-circle-check' : ($bsreValid === false ? 'fa-circle-xmark' : 'fa-circle-info') }} text-2xl"></i>
      </div>
      <div>
        <h2 class="text-lg font-bold {{ $bsreValid === true ? 'text-emerald-700' : ($bsreValid === false ? 'text-rose-700' : 'text-slate-700') }}">
          {{ $result['summary'] ?? 'Hasil Verifikasi BSrE' }}
        </h2>
        <p class="mt-1 flex items-center justify-center gap-1.5 text-[11px] font-medium text-slate-500">
          <i class="fa-solid fa-certificate text-primary-500"></i> Diverifikasi langsung oleh BSrE
        </p>
      </div>
    </div>

    {{-- Ringkasan --}}
    <dl class="divide-y divide-slate-100 px-6 py-2 text-sm">
      @if ($result['document_name'])
        <div class="flex flex-col gap-0.5 py-3 sm:flex-row sm:items-center sm:justify-between">
          <dt class="font-medium text-slate-500">Nama Dokumen</dt>
          <dd class="break-all text-right text-slate-800">{{ $result['document_name'] }}</dd>
        </div>
      @endif
      @if (! is_null($result['signature_count']))
        <div class="flex flex-col gap-0.5 py-3 sm:flex-row sm:items-center sm:justify-between">
          <dt class="font-medium text-slate-500">Jumlah Tanda Tangan</dt>
          <dd class="font-semibold text-slate-800">
            {{ $result['signature_count'] }}
            <span class="font-normal text-slate-500">({{ strtolower(\App\Helpers\Terbilang::convert($result['signature_count'])) ?: 'nol' }})</span>
          </dd>
        </div>
      @endif
      @if ($result['notes'])
        <div class="flex flex-col gap-0.5 py-3 sm:flex-row sm:items-start sm:justify-between">
          <dt class="font-medium text-slate-500">Catatan</dt>
          <dd class="text-right text-slate-700 sm:max-w-md">{{ $result['notes'] }}</dd>
        </div>
      @endif
    </dl>

    {{-- Rincian per tanda tangan --}}
    @if (! empty($result['details']))
      <div class="border-t border-slate-100 px-6 py-4">
        <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-600">Rincian Tanda Tangan</p>
        <div class="space-y-2">
          @foreach ($result['details'] as $i => $detail)
            @php
              $signerName = data_get($detail, 'info_signer.signer_name');
              $issuer = data_get($detail, 'info_signer.issuer_dn');
              $certTrusted = data_get($detail, 'info_signer.cert_user_certified');
              $integrity = data_get($detail, 'signature_document.document_integrity');
              $signedIn = data_get($detail, 'signature_document.signed_in');
              $usingTsa = data_get($detail, 'signature_document.signed_using_tsa');
              $tsaName = data_get($detail, 'info_tsa.name');
            @endphp
            <div class="rounded border border-slate-200 bg-slate-50 p-3 text-[12px]">
              <p class="mb-1.5 font-semibold text-slate-700">
                Tanda tangan #{{ $i + 1 }}
                @if ($signerName) — {{ $signerName }} @endif
              </p>
              <dl class="space-y-1">
                @if ($issuer)
                  <div class="flex justify-between gap-3">
                    <dt class="text-slate-500">Penerbit Sertifikat</dt>
                    <dd class="break-all text-right text-slate-600">{{ $issuer }}</dd>
                  </div>
                @endif
                @if (! is_null($integrity))
                  <div class="flex justify-between gap-3">
                    <dt class="text-slate-500">Integritas Dokumen</dt>
                    <dd class="text-right font-semibold {{ $integrity ? 'text-emerald-600' : 'text-rose-600' }}">
                      {{ $integrity ? 'Utuh (tidak diubah)' : 'Berubah' }}
                    </dd>
                  </div>
                @endif
                @if (! is_null($certTrusted))
                  <div class="flex justify-between gap-3">
                    <dt class="text-slate-500">Sertifikat Tepercaya</dt>
                    <dd class="text-right font-semibold {{ $certTrusted ? 'text-emerald-600' : 'text-amber-600' }}">
                      {{ $certTrusted ? 'Ya' : 'Tidak' }}
                    </dd>
                  </div>
                @endif
                @if ($signedIn)
                  <div class="flex justify-between gap-3">
                    <dt class="text-slate-500">Ditandatangani</dt>
                    <dd class="text-right text-slate-600">{{ $signedIn }}</dd>
                  </div>
                @endif
                @if ($usingTsa && $tsaName)
                  <div class="flex justify-between gap-3">
                    <dt class="text-slate-500">Stempel Waktu (TSA)</dt>
                    <dd class="break-all text-right text-slate-600">{{ $tsaName }}</dd>
                  </div>
                @endif
              </dl>
            </div>
          @endforeach
        </div>
      </div>
    @endif
  </div>
@endif
