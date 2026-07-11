@extends('layouts.app')
@section('title', 'Laporan Hasil Perjalanan Dinas')

@section('content')
  <livewire:sppd.travel-report :sppd="$sppd" />
@endsection
