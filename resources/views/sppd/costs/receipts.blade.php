@extends('layouts.app')
@section('title', 'Kuitansi')

@section('content')
    <livewire:sppd.advance-receipts :sppd="$sppd" />
@endsection
