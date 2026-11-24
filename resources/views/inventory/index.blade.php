@extends('layouts.app')

@section('title', 'Tồn kho')

@section('content')
    <h1 class="page-title">Tồn kho</h1>
    <p class="page-subtitle">
        Số lượng tồn hiện tại theo từng sản phẩm và kho (UC04.01). Bấm tên sản phẩm để xem thẻ kho.
    </p>

    {{-- Toàn bộ thao tác lọc / sắp xếp / phân trang nằm trong Livewire component App\Livewire\Inventory\InventoryIndex --}}
    <livewire:inventory.inventory-index />
@endsection
