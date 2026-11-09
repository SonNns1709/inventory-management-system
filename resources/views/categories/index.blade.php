@extends('layouts.app')

@section('title', 'Danh mục sản phẩm')

@section('content')
    <h1 class="page-title">Danh mục sản phẩm</h1>
    <p class="page-subtitle">
        Nhóm các sản phẩm cùng loại (UC02.01). Tên danh mục là duy nhất; danh mục còn sản phẩm thì không xóa được.
    </p>

    {{-- Toàn bộ thao tác (tìm kiếm, sắp xếp, phân trang, thêm / sửa / xóa) nằm trong Livewire component App\Livewire\Categories\CategoryIndex --}}
    <livewire:categories.category-index />
@endsection
