@extends('layouts.app')

@section('title', 'Tồn kho')

@section('content')
    <h1 class="page-title">Tồn kho</h1>
    <p class="page-subtitle">
        Số lượng tồn hiện tại theo từng sản phẩm và từng kho. Số liệu chỉ để tra cứu; tồn kho thay đổi qua phiếu nhập, phiếu xuất và kiểm kê.
    </p>

    <livewire:inventories.inventory-index />
@endsection