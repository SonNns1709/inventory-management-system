@extends('layouts.app')

@section('title', isset($importId) && $importId ? 'Sửa phiếu nhập' : 'Lập phiếu nhập')

@section('content')
    <h1 class="page-title">{{ isset($importId) && $importId ? 'Sửa phiếu nhập' : 'Lập phiếu nhập' }}</h1>

    <livewire:imports.import-form :import-id="$importId" />
@endsection
