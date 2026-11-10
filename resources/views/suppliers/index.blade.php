@extends('layouts.app')

@section('title', 'Nhà cung cấp')

@section('content')
    <h1 class="page-title">Nhà cung cấp</h1>
    <p class="page-subtitle">
        Quản lý thông tin nhà cung cấp. Tên nhà cung cấp là duy nhất; nhà cung cấp đã có phiếu nhập thì chỉ được ngừng hợp tác, không xóa được.
    </p>

    <livewire:suppliers.supplier-index />
@endsection