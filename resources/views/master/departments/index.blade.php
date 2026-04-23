@extends('layouts.app')
@section('title', $isSuperAdmin ? 'Data Instansi' : 'Kelola Unit Kerja')
@section('page-title', $isSuperAdmin ? 'Data Instansi' : 'Kelola Unit Kerja')

@section('content')
<div class="page-header">
  <div>
    <h1 class="page-title">{{ $isSuperAdmin ? 'Data Instansi' : 'Manajemen Unit Kerja' }}</h1>
    <p class="page-subtitle">
        {{ $isSuperAdmin ? 'Kelola OPD, kecamatan, kelurahan, dan unit kerja' : 'Kelola Bidang dan Seksi di lingkup ' . auth()->user()->department?->name }}
    </p>
  </div>
  <a href="{{ route('master.departments.create') }}" class="btn-primary">
    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
    {{ $isSuperAdmin ? 'Tambah Instansi' : 'Tambah Bidang/Seksi' }}
  </a>
</div>

{{-- Filters --}}
<div class="card p-4 mb-4">
  <form method="GET" action="{{ route('master.departments.index') }}" class="flex flex-col sm:flex-row gap-3">
    <div class="flex-1">
      <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="Cari nama atau kode unit...">
    </div>
    @if($isSuperAdmin)
    <select name="type" class="form-select w-full sm:w-44">
      <option value="">Semua Tipe</option>
      @foreach($types as $t)
        <option value="{{ $t->value }}" {{ request('type') === $t->value ? 'selected' : '' }}>{{ $t->label() }}</option>
      @endforeach
    </select>
    @endif
    <button type="submit" class="btn-secondary">Cari</button>
    @if(request()->hasAny(['search', 'type']))
      <a href="{{ route('master.departments.index') }}" class="btn-ghost">Reset</a>
    @endif
  </form>
</div>

{{-- Table --}}
<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th class="w-12">No</th>
        <th>Nama Unit Kerja</th>
        <th>Kode</th>
        <th>Pimpinan</th>
        @if($isSuperAdmin)
            <th>Tipe</th>
            <th>Induk</th>
            <th class="text-center">Pegawai</th>
            <th class="text-center">Sub-Unit</th>
        @endif
        <th class="text-right">Aksi</th>
      </tr>
    </thead>
    <tbody>
      @forelse($departments as $i => $dept)
        <tr class="{{ !$isSuperAdmin && $dept->id == auth()->user()->department_id ? 'bg-slate-50 font-bold' : '' }}">
          <td class="text-slate-400">{{ ($departments instanceof \Illuminate\Pagination\LengthAwarePaginator) ? $departments->firstItem() + $i : $i + 1 }}</td>
          <td>
            <div class="flex items-center">
                @if(!$isSuperAdmin)
                    @for($j = 0; $j < $dept->level - 1; $j++)
                        <span class="w-6 h-px bg-slate-200 inline-block mr-2 last:bg-slate-400"></span>
                    @endfor
                @endif
                <span class="{{ $dept->id == auth()->user()->department_id ? 'text-primary-700' : 'text-slate-900' }}">
                    {{ $dept->name }}
                </span>
            </div>
          </td>
          <td class="text-xs font-mono text-slate-500">{{ $dept->code ?? '—' }}</td>
          <td class="text-sm text-slate-600">{{ $dept->head?->name ?? '—' }}</td>
          @if($isSuperAdmin)
            <td>
                <span class="badge bg-slate-100 text-slate-700">{{ $dept->type->label() }}</span>
            </td>
            <td class="text-sm text-slate-500">{{ $dept->parent?->name ?? '—' }}</td>
            <td class="text-center">
                <span class="text-sm font-medium text-slate-700">{{ $dept->users_count }}</span>
            </td>
            <td class="text-center">
                <span class="text-sm font-medium text-slate-700">{{ $dept->children_count }}</span>
            </td>
          @endif
          <td class="text-right">
            <div class="flex justify-end gap-2">
              <a href="{{ route('master.departments.show', $dept->id) }}" class="btn-ghost p-2 text-primary-600 hover:bg-primary-50" title="Detail">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
              </a>
              <a href="{{ route('master.departments.edit', $dept->id) }}" class="btn-ghost p-2 text-amber-600 hover:bg-amber-50" title="Edit">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125" /></svg>
              </a>
              @if($isSuperAdmin || $dept->parent_id !== null)
              <form action="{{ route('master.departments.destroy', $dept->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus data ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-ghost p-2 text-rose-600 hover:bg-rose-50" title="Hapus">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                </button>
              </form>
              @endif
            </div>
          </td>
        </tr>
      @empty
        <tr><td colspan="10" class="text-center py-12 text-slate-400">Belum ada data instansi</td></tr>
      @endforelse
    </tbody>
  </table>
  @if($departments instanceof \Illuminate\Pagination\LengthAwarePaginator && $departments->hasPages())
    <div class="px-4 py-3 border-t border-slate-200">{{ $departments->links() }}</div>
  @endif
</div>
@endsection
