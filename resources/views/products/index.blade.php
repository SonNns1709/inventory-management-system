@extends('layouts.app')

@section('title', 'Sản phẩm')

@section('content')
    <h1 class="page-title">Sản phẩm</h1>
    <p class="page-subtitle">
        Tìm kiếm và quản lý sản phẩm. Tồn được cộng từ tất cả kho; sản phẩm dưới ngưỡng được đánh dấu cảnh báo.
    </p>

    <livewire:products.product-index />
@endsection
