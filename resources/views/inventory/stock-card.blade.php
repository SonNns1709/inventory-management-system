@extends('layouts.app')

@section('title', 'Thẻ kho '.$product->sku)

@section('content')
    <h1 class="page-title">Tồn kho / Thẻ kho</h1>
    <p class="page-subtitle">
        Sổ ghi lại mọi lần nhập, xuất, chuyển kho và điều chỉnh của một sản phẩm (UC04.02).
    </p>

    {{-- Toàn bộ thao tác nằm trong Livewire component App\Livewire\Inventory\StockCard --}}
    <livewire:inventory.stock-card :product="$product" />
@endsection
