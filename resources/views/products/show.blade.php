@extends('layouts.app')

@section('title', $product->name)

@section('content')
    <div class="mx-auto max-w-6xl">
    <div class="mb-5">
        <a href="{{ route('products.index') }}"
           class="group mb-3 inline-flex items-center gap-1 text-sm text-slate-500 transition hover:text-brand focus:outline-none focus:underline">
            <svg aria-hidden="true" class="h-2.5 w-2.5 transition-transform group-hover:-translate-x-0.5" viewBox="0 0 12 12" fill="currentColor">
                <path d="M9 1.5 2.5 6 9 10.5v-9Z" />
            </svg>
            <span class="underline-offset-4 group-hover:underline">Danh sách sản phẩm</span>
        </a>
        <div class="flex flex-wrap items-end justify-between gap-2">
            <div>
                <h1 class="page-title">{{ $product->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $product->sku }} · {{ $product->category?->name }}</p>
            </div>
            <span @class([
                'mb-0.5 inline-flex rounded-full px-2.5 py-1 text-xs font-medium',
                'bg-green-100 text-green-800' => $product->is_active,
                'bg-slate-200 text-slate-700' => ! $product->is_active,
            ])>{{ $product->is_active ? 'Đang kinh doanh' : 'Ngừng kinh doanh' }}</span>
        </div>
    </div>

    @php($totalStock = (int) ($product->total_stock ?? 0))
    @php($isLowStock = $totalStock < (int) $product->min_stock_level)
    @if ($isLowStock)
        <div role="status" class="mb-4 rounded-md border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900">
            Sản phẩm đang dưới ngưỡng tồn thấp.
        </div>
    @endif

    <section class="card mb-5 p-4 sm:p-5">
        <h2 class="mb-3 text-base font-semibold text-brand">Thông tin sản phẩm</h2>
        <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="label">Danh mục</dt><dd class="text-sm">{{ $product->category?->name ?? '—' }}</dd></div>
            <div><dt class="label">Đơn vị tính</dt><dd class="text-sm">{{ $product->unit }}</dd></div>
            <div><dt class="label">Giá bán</dt><dd class="text-sm font-medium">{{ \App\Support\Format::money($product->price) }}</dd></div>
            <div><dt class="label">Giá vốn bình quân</dt><dd class="text-sm font-medium">{{ \App\Support\Format::money($product->avg_cost, 4) }}</dd></div>
            <div>
                <dt class="label">Tổng tồn</dt>
                <dd @class(['text-sm font-semibold tabular-nums', 'text-amber-700' => $isLowStock, 'text-slate-900' => ! $isLowStock])>
                    {{ \App\Support\Format::number($totalStock) }}
                </dd>
            </div>
            <div><dt class="label">Ngưỡng cảnh báo</dt><dd class="text-sm tabular-nums">{{ \App\Support\Format::number($product->min_stock_level) }}</dd></div>
            <div class="sm:col-span-2 lg:col-span-4">
                <dt class="label">Mô tả</dt>
                <dd class="whitespace-pre-line text-sm text-slate-700">{{ $product->description ?: '—' }}</dd>
            </div>
        </dl>
    </section>

    <section class="card p-4 sm:p-5">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-base font-semibold text-brand">Tồn theo từng kho</h2>
            <span class="text-xs text-slate-500">{{ $product->inventories->count() }} kho</span>
        </div>
        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Mã kho</th>
                        <th scope="col">Tên kho</th>
                        <th scope="col">Trạng thái kho</th>
                        <th scope="col" class="text-right">Số lượng</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($product->inventories as $inventory)
                        <tr wire:key="inventory-{{ $inventory->id }}">
                            <td>{{ $inventory->warehouse?->code ?? '—' }}</td>
                            <td>{{ $inventory->warehouse?->name ?? '—' }}</td>
                            <td>{{ $inventory->warehouse?->is_active ? 'Đang hoạt động' : 'Ngừng hoạt động' }}</td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($inventory->quantity) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-8 text-center text-slate-500">Sản phẩm chưa có tồn ở kho nào.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
    </div>
@endsection
