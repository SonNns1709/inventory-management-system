@extends('layouts.app')

@section('title', 'Báo cáo kiểm kê')

@section('content')
    <h1 class="page-title">Báo cáo / Báo cáo kiểm kê</h1>
    <p class="page-subtitle">
        Lịch sử các lần kiểm kê / điều chỉnh tồn kho đã xác nhận trong một khoảng thời gian, kèm tổng hao hụt, tổng dôi thừa và tỷ lệ sai lệch (UC05.03). Chỉ Admin truy cập được.
    </p>

    {{-- Toàn bộ thao tác lọc nằm trong Livewire component App\Livewire\Reports\AdjustmentReport --}}
    <livewire:reports.adjustment-report />
@endsection
