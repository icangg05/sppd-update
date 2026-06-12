@extends('layouts.app')
@section('title', 'Edit Role')
@section('page-title', 'Edit Role')

@section('content')
	<livewire:role-form :role="$role" />
@endsection
