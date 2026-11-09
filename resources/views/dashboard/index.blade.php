@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $format = \App\Support\Format::class;

        // 5 chỉ số của UC05.01 (BR05.01-2); tên tiếng Anh ghi ở dòng phụ để đối chiếu báo cáo
        $cards = [
            [
                'label' => 'Sản phẩm đang kinh doanh',
                'value' => $format::number($summary['total_products']),
                'hint' => 'Total Products',
            ],
            [
                'label' => 'Sản phẩm dưới ngưỡng tồn',
                'value' => $format::number($summary['low_stock']),
                'hint' => 'Low Stock',
                'warn' => $summary['low_stock'] > 0,
            ],
            [
                'label' => 'Phiếu nhập hôm nay',
                'value' => $format::number($summary['todays_import']),
                'hint' => "Today's Import · ".$format::money($summary['todays_import_amount']),
            ],
            [
                'label' => 'Phiếu xuất hôm nay',
                'value' => $format::number($summary['todays_export']),
                'hint' => "Today's Export · giá vốn ".$format::money($summary['todays_export_cost']),
            ],
            [
                'label' => 'Giá trị tồn kho',
                'value' => $format::money($summary['inventory_value']),
                'hint' => 'Inventory Value',
            ],
        ];
    @endphp

    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Số liệu tồn kho tại thời điểm hiện tại (UC05.01).</p>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($cards as $card)
            <div @class(['card', 'border-amber-300 bg-amber-50' => $card['warn'] ?? false])>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $card['label'] }}</p>
                <p class="mt-2 text-2xl font-bold text-brand">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $card['hint'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- UC05.01 — Alternative Flow A1: danh sách rút gọn Low Stock (số dòng cấu hình ở config/inventory.php) --}}
    <section class="mt-8">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-brand">Sản phẩm sắp hết hàng</h2>
            <a href="{{ route('inventory.low-stock') }}" class="text-sm text-brand underline">Xem tất cả ({{ $summary['low_stock'] }})</a>
        </div>

        @if (count($summary['low_stock_items']) === 0)
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
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($summary['low_stock_items'] as $item)
                            <tr>
                                <td class="font-mono text-xs">{{ $item['sku'] }}</td>
                                <td>{{ $item['name'] }}</td>
                                <td class="text-right">{{ $format::number($item['total_stock']) }} {{ $item['unit'] }}</td>
                                <td class="text-right">{{ $format::number($item['min_stock_level']) }}</td>
                                <td class="text-right font-semibold text-red-600">{{ $format::number($item['shortage']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
