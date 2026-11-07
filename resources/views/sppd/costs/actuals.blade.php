@extends('layouts.app')
@section('title', 'Laporan Pengeluaran Riil')

@section('content')
    <livewire:sppd.actual-expenses :sppd="$sppd" />
@endsection
