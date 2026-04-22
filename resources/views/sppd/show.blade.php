@extends('layouts.app')
@section('title', 'Detail SPPD')
@section('page-title', 'Detail SPPD')

@section('content')
	<div class="page-header">
		<div>
			<h1 class="page-title">Detail SPPD</h1>
			<p class="page-subtitle">{{ $sppd->document_number ?? 'Belum bernomor' }}</p>
		</div>
		<div class="flex gap-2">
			@if ($sppd->status->value === 'in_progress' && (auth()->id() === $sppd->creator_id || auth()->id() === $sppd->user_id))
				<form action="{{ route('sppd.destroy', $sppd) }}" method="POST"
					onsubmit="return confirm('Batalkan dan hapus pengajuan SPPD ini?')">
					@csrf
					@method('DELETE')
					<button type="submit" class="btn-ghost text-red-500 hover:bg-red-50 flex items-center gap-1">
						<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
							<path stroke-linecap="round" stroke-linejoin="round"
								d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
						</svg>
						Batalkan Pengajuan
					</button>
				</form>
			@endif
			<span class="badge-{{ $sppd->status->value }} text-sm px-3 py-1">{{ $sppd->status->label() }}</span>
			<a href="{{ route('sppd.index') }}" class="btn-secondary">← Kembali</a>
		</div>
	</div>

	<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
		{{-- Left: Main info --}}
		<div class="xl:col-span-2 space-y-6">
			{{-- Info Perjalanan --}}
			<div class="card p-6">
				<h3 class="font-semibold text-slate-900 mb-4">Informasi Perjalanan</h3>
				<div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8">
					<div>
						<p class="text-xs text-slate-500">Pelaksana</p>
						<p class="font-medium text-slate-900">{{ $sppd->user->name }}</p>
						<p class="text-xs text-slate-400">{{ $sppd->user->nip ?? '-' }}</p>
					</div>
					<div>
						<p class="text-xs text-slate-500">Instansi</p>
						<p class="font-medium text-slate-900">{{ $sppd->budget?->department?->name ?? '-' }}</p>
					</div>
					<div>
						<p class="text-xs text-slate-500">Kategori</p>
						<p class="font-medium text-slate-900">{{ $sppd->category?->name ?? '-' }}</p>
					</div>
					<div>
						<p class="text-xs text-slate-500">Domain</p>
						<p class="font-medium text-slate-900">{{ $sppd->domain->label() }}</p>
					</div>
					<div>
						<p class="text-xs text-slate-500">Tanggal</p>
						<p class="font-medium text-slate-900">{{ $sppd->start_date->format('d M Y') }} —
							{{ $sppd->end_date->format('d M Y') }}</p>
						<p class="text-xs text-slate-400">{{ $sppd->duration_days }} hari</p>
					</div>
					<div>
						<p class="text-xs text-slate-500">Pembuat</p>
						<p class="font-medium text-slate-900">{{ $sppd->creator?->name ?? '-' }}</p>
					</div>
					<div class="sm:col-span-2">
						<p class="text-xs text-slate-500">Maksud Perjalanan</p>
						<p class="font-medium text-slate-900">{{ $sppd->purpose }}</p>
					</div>
					@if ($sppd->notes)
						<div class="sm:col-span-2">
							<p class="text-xs text-slate-500">Catatan</p>
							<p class="text-slate-700">{{ $sppd->notes }}</p>
						</div>
					@endif
				</div>
			</div>

			{{-- Tujuan --}}
			@if ($sppd->destinations->count())
				<div class="card p-6">
					<h3 class="font-semibold text-slate-900 mb-4">Lokasi Tujuan</h3>
					<div class="space-y-3">
						@foreach ($sppd->destinations as $dest)
							<div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg">
								<div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
									<svg class="w-4 h-4 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
										stroke-width="1.5">
										<path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
										<path stroke-linecap="round" stroke-linejoin="round"
											d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
									</svg>
								</div>
								<div>
									<p class="font-medium text-slate-900">
										{{ $dest->province->name }}{{ $dest->regency ? ', ' . $dest->regency->name : '' }}</p>
									@if ($dest->address)
										<p class="text-xs text-slate-500">{{ $dest->address }}</p>
									@endif
								</div>
							</div>
						@endforeach
					</div>
				</div>
			@endif

			{{-- Pengikut --}}
			@if ($sppd->followers->count())
				<div class="card p-6">
					<h3 class="font-semibold text-slate-900 mb-4">Pengikut</h3>
					<div class="flex flex-wrap gap-2">
						@foreach ($sppd->followers as $f)
							<span class="inline-flex items-center gap-2 px-3 py-1.5 bg-slate-100 rounded-lg text-sm">
								<span
									class="w-6 h-6 bg-primary-500 rounded-full flex items-center justify-center text-[10px] font-bold text-white">{{ strtoupper(substr($f->user->name, 0, 1)) }}</span>
								{{ $f->user->name }}
							</span>
						@endforeach
					</div>
				</div>
			@endif

			{{-- Rincian Biaya --}}
			@if ($sppd->costDetails->count())
				<div class="card overflow-hidden">
					<div class="px-6 py-4 border-b border-slate-200">
						<h3 class="font-semibold text-slate-900">Rincian Biaya</h3>
					</div>
					<table class="data-table">
						<thead>
							<tr>
								<th>Uraian</th>
								<th class="text-right">Biaya Satuan</th>
								<th class="text-right">Qty</th>
								<th class="text-right">Subtotal</th>
							</tr>
						</thead>
						<tbody>
							@php $total = 0; @endphp
							@foreach ($sppd->costDetails as $c)
								@php
									$sub = $c->unit_cost * $c->quantity;
									$total += $sub;
								@endphp
								<tr>
									<td>{{ $c->description }}</td>
									<td class="text-right">Rp {{ number_format($c->unit_cost, 0, ',', '.') }}</td>
									<td class="text-right">{{ $c->quantity }}</td>
									<td class="text-right font-medium">Rp {{ number_format($sub, 0, ',', '.') }}</td>
								</tr>
							@endforeach
							<tr class="bg-slate-50">
								<td colspan="3" class="text-right font-semibold">Total</td>
								<td class="text-right font-bold text-primary-600">Rp {{ number_format($total, 0, ',', '.') }}</td>
							</tr>
						</tbody>
					</table>
				</div>
			@endif
		</div>

		{{-- Right: Timeline --}}
		<div class="space-y-6">
			{{-- Approval Timeline --}}
			<div class="card p-6">
				<h3 class="font-semibold text-slate-900 mb-5">Timeline Persetujuan</h3>
				@if ($sppd->approvals->count())
					<div>
						@foreach ($sppd->approvals->sortBy('step_order') as $ap)
							<div class="timeline-step">
								<div
									class="timeline-dot {{ $ap->status->value === 'approved' ? 'bg-emerald-500' : ($ap->status->value === 'rejected' ? 'bg-red-500' : ($ap->status->value === 'revision' ? 'bg-orange-500' : 'bg-slate-300')) }}">
									@if ($ap->status->value === 'approved')
										<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
											<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
										</svg>
									@elseif($ap->status->value === 'rejected')
										<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
											<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
										</svg>
									@else
										{{ $ap->step_order }}
									@endif
								</div>
								<div class="flex-1 min-w-0">
									<p class="uppercase font-medium text-sm text-slate-900">{{ $ap->role_label }}</p>
									<p class="text-xs text-slate-500">{{ $ap->approver->name }}</p>
									<span class="badge-{{ $ap->status->value }} mt-1">{{ $ap->status->label() }}</span>
									@if ($ap->notes)
										<p class="text-xs text-slate-500 mt-1 italic">"{{ $ap->notes }}"</p>
									@endif
									@if ($ap->acted_at)
										<p class="text-[11px] text-slate-400 mt-1">{{ $ap->acted_at->format('d M Y H:i') }}</p>
									@endif
								</div>
							</div>
						@endforeach
					</div>
				@else
					<p class="text-sm text-slate-400">Belum ada alur persetujuan</p>
				@endif
			</div>

			{{-- Approve/Reject actions --}}
			@php
				$myApproval = $sppd->approvals
				    ->where('approver_id', auth()->id())
				    ->where('status', \App\Enums\ApprovalStatus::PENDING)
				    ->first();
			@endphp
			@if ($myApproval)
				<div class="card p-6 border-amber-200 bg-amber-50">
					<h3 class="font-semibold text-amber-900 mb-3">Menunggu Keputusan Anda</h3>
					<p class="text-sm text-amber-700 mb-4">Sebagai <strong>{{ $myApproval->role_label }}</strong> (Step
						{{ $myApproval->step_order }})</p>

					<form action="{{ route('sppd.approve', $sppd) }}" method="POST" class="mb-3">
						@csrf
						<textarea name="notes" class="form-input mb-2 text-sm" rows="2" placeholder="Catatan (opsional)"></textarea>
						<button type="submit" class="btn-success w-full">
							<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
								<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
							</svg>
							Setujui
						</button>
					</form>
					<form action="{{ route('sppd.reject', $sppd) }}" method="POST">
						@csrf
						<input type="hidden" name="notes" id="reject-notes">
						<button type="button" onclick="rejectSppd(this.form)" class="btn-danger w-full">
							<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
								<path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
							</svg>
							Tolak
						</button>
					</form>
				</div>
			@endif
		</div>
	</div>
@endsection

@push('scripts')
	<script>
		function rejectSppd(form) {
			const reason = prompt('Alasan penolakan (wajib):');
			if (reason && reason.trim()) {
				form.querySelector('#reject-notes').value = reason;
				form.submit();
			}
		}
	</script>
@endpush
