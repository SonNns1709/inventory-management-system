@extends('layouts.app')

@section('title', 'Kho')

@section('content')
    <h1 class="page-title">Kho</h1>
    <p class="page-subtitle">
        Quản lý kho và tổng tồn. Chỉ ngừng hoạt động khi kho đã hết hàng; chỉ xóa kho chưa từng phát sinh tồn hoặc chứng từ.
    </p>

    <livewire:warehouses.warehouse-index />
@endsection