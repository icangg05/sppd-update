@extends('layouts.app')
@section('title', 'Workflow SPPD')
@section('page-title', 'Workflow SPPD')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">Workflow SPPD</h1>
    <p class="page-subtitle">Atur alur persetujuan SPPD secara dinamis</p>
  </div>
  <a href="{{ route('master.workflows.create') }}" class="btn-primary">
    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
    </svg>
    Tambah Workflow
  </a>
</div>

<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>No</th>
        <th>Nama Workflow</th>
        <th>Instansi & Jabatan</th>
        <th>Tujuan</th>
        <th>Alur Persetujuan (Steps)</th>
        <th>Status</th>
        <th class="text-right">Aksi</th>
      </tr>
    </thead>
    <tbody>
      @forelse($workflows as $i => $w)
        <tr>
          <td class="text-slate-400">{{ $i + 1 }}</td>
          <td>
            <p class="font-medium text-slate-900">{{ $w->name }}</p>
          </td>
          <td class="text-sm">
            <span class="block text-slate-700">Tipe: {{ $w->department_type?->label() ?? 'Semua' }}</span>
            <span class="block text-slate-500">Pemohon: {{ $w->applicant_role ?? 'Semua' }}</span>
          </td>
          <td>
            @if(is_array($w->destination) && count($w->destination) > 0)
              <div class="flex flex-wrap gap-1">
                @foreach($w->destination as $d)
                  <span class="badge bg-blue-50 text-blue-700 border border-blue-100 text-[10px]">
                    {{ \App\Enums\SppdDomain::tryFrom($d)?->label() ?? $d }}
                  </span>
                @endforeach
              </div>
            @else
              <span class="badge bg-slate-100 text-slate-500">Semua</span>
            @endif
          </td>
          <td>
            <div class="flex flex-wrap gap-1.5 items-center">
              @foreach($w->steps as $idx => $role)
                <span class="badge bg-slate-100 border border-slate-200 text-slate-700">{{ $idx + 1 }}. {{ $role }}</span>
                @if(!$loop->last)
                  <svg class="w-3 h-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                @endif
              @endforeach
            </div>
          </td>
          <td>
            @if ($w->is_active)
              <span class="badge bg-emerald-100 text-emerald-800">Aktif</span>
            @else
              <span class="badge bg-red-100 text-red-800">Nonaktif</span>
            @endif
          </td>
          <td class="text-right">
            <div class="flex justify-end gap-2 items-center">
              <a href="{{ route('master.workflows.edit', $w->id) }}" class="btn-ghost p-1.5 text-amber-600 hover:bg-amber-50" title="Edit">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" /></svg>
              </a>
              <form action="{{ route('master.workflows.destroy', $w->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus workflow ini?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn-ghost p-1.5 text-rose-600 hover:bg-rose-50" title="Hapus">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="text-center py-12 text-slate-400">Belum ada pengaturan workflow</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
