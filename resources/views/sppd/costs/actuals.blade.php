@extends('layouts.app')
@section('title', 'Laporan Pengeluaran Rill')

@section('content')
    <livewire:sppd.actual-expenses :sppd="$sppd" />
@endsection
