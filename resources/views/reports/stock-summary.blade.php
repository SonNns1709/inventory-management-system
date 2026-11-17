@extends('layouts.app')

@section('title', 'Báo cáo Nhập – Xuất – Tồn')

@section('content')
    <h1 class="page-title">Báo cáo / Nhập – Xuất – Tồn</h1>
    <p class="page-subtitle">
        Tổng hợp tồn đầu, nhập, xuất, điều chỉnh và tồn cuối của từng sản phẩm trong một khoảng thời gian (UC05.02). Chỉ Admin truy cập được.
    </p>

    {{-- Toàn bộ thao tác lọc nằm trong Livewire component App\Livewire\Reports\StockSummaryReport --}}
    <livewire:reports.stock-summary-report />
@endsection
