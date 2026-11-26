@extends('layouts.app')

@section('title', 'Phiếu xuất kho')

@section('content')
    <h1 class="page-title">Phiếu xuất kho</h1>
    <p class="page-subtitle">Lập, tra cứu và quản lý phiếu bán hàng, chuyển kho.</p>

    <livewire:exports.export-index />
@endsection
