@extends('layouts.app')

@section('title', 'Cảnh báo tồn thấp')

@section('content')
    <h1 class="page-title">Cảnh báo tồn thấp</h1>
    <p class="page-subtitle">
        Sản phẩm có tổng tồn mọi kho thấp hơn ngưỡng của chính sản phẩm đó (UC04.04). Sắp xếp theo mức thiếu giảm dần.
    </p>

    @if ($items->isEmpty())
        <div class="card text-sm text-slate-500">Không có sản phẩm nào dưới ngưỡng tồn kho.</div>
    @else
        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Sản phẩm</th>
                        <th class="text-right">Tồn hiện tại</th>
                        <th class="text-right">Ngưỡng</th>
                        <th class="text-right">Còn thiếu</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $p)
                        <tr wire:key="low-{{ $p->id }}">
                            <td class="font-mono text-xs">{{ $p->sku }}</td>
                            <td class="font-medium">
                                <a href="{{ route('inventory.stock-card', $p) }}" class="text-brand hover:underline" title="Xem thẻ kho">{{ $p->name }}</a>
                            </td>
                            <td class="text-right font-bold text-red-700">{{ \App\Support\Format::number($p->total_stock) }} {{ $p->unit }}</td>
                            <td class="text-right">{{ \App\Support\Format::number($p->min_stock_level) }}</td>
                            <td class="text-right font-semibold text-red-600">{{ \App\Support\Format::number(max(0, $p->min_stock_level - $p->total_stock)) }}</td>
                            <td><span class="badge badge-danger">Tồn thấp</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="mt-3 text-xs text-slate-500">Tổng cộng {{ $items->count() }} sản phẩm dưới ngưỡng.</p>
    @endif
@endsection
