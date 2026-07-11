@extends('layouts.app')
@section('title', 'Rincian Biaya Perjalanan Dinas')

@section('content')
    <livewire:sppd.cost-details :sppd="$sppd" />
@endsection
