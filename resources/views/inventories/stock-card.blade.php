@extends('layouts.app')

@section('title', 'Thẻ kho - '.$product->name)

@section('content')
    <div class="mx-auto max-w-6xl">
        <div class="mb-5">
            <a href="{{ route('inventory.index', ['product' => $product->id]) }}"
               class="mb-3 inline-flex text-sm text-slate-500 underline-offset-4 hover:text-brand hover:underline">
                ← Quay lại tồn kho sản phẩm
            </a>
            <h1 class="page-title">Thẻ kho: {{ $product->name }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $product->sku }} · Tổng tồn hiện tại:
                <span class="font-medium text-slate-700">{{ \App\Support\Format::number($product->inventories_sum_quantity ?? 0) }} {{ $product->unit }}</span>
            </p>
        </div>

        <form method="GET" action="{{ route('inventory.stock-card', $product) }}" class="card mb-4 flex flex-wrap items-end gap-3 p-4">
            <div>
                <label for="warehouse_id" class="label">Kho</label>
                <select id="warehouse_id" name="warehouse_id" class="input">
                    <option value="">Tất cả kho</option>
                    @foreach ($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" @selected((string) $warehouseId === (string) $warehouse->id)>
                            {{ $warehouse->code }} — {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="label">Từ ngày</label>
                <input id="from" type="date" name="from" value="{{ $from }}" class="input">
            </div>
            <div>
                <label for="to" class="label">Đến ngày</label>
                <input id="to" type="date" name="to" value="{{ $to }}" class="input">
            </div>
            <button type="submit" class="btn btn-primary">Lọc</button>
            @if ($warehouseId !== '' || $from !== '' || $to !== '')
                <a href="{{ route('inventory.stock-card', $product) }}" class="btn btn-secondary">Xóa bộ lọc</a>
            @endif
        </form>

        @if ($errors->any())
            <div role="alert" class="mb-4 rounded-md border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-800">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="mb-4 grid gap-3 sm:grid-cols-2">
            <div class="card p-4">
                <div class="text-sm text-slate-500">Tồn đầu kỳ</div>
                <div class="mt-1 text-xl font-semibold tabular-nums">{{ \App\Support\Format::number($card['opening_balance']) }} {{ $product->unit }}</div>
            </div>
            <div class="card p-4">
                <div class="text-sm text-slate-500">Tồn cuối kỳ</div>
                <div class="mt-1 text-xl font-semibold tabular-nums">{{ \App\Support\Format::number($card['closing_balance']) }} {{ $product->unit }}</div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="data-table">
                <thead>
                    <tr>
                        <th scope="col">Thời điểm</th>
                        <th scope="col">Loại biến động</th>
                        <th scope="col">Kho</th>
                        <th scope="col">Số phiếu</th>
                        <th scope="col" class="text-right">Thay đổi</th>
                        <th scope="col" class="text-right">Tồn sau giao dịch</th>
                        <th scope="col" class="text-right">Giá vốn</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($card['movements'] as $movement)
                        <tr wire:key="stock-movement-{{ $movement->id }}">
                            <td class="whitespace-nowrap">{{ $movement->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $movement->type->label() }}</td>
                            <td>{{ $movement->warehouse?->name ?? '—' }}</td>
                            <td class="font-mono text-sm">{{ $movement->reference_code ?? '—' }}</td>
                            <td @class([
                                'text-right tabular-nums font-medium',
                                'text-green-700' => $movement->qty_change > 0,
                                'text-red-700' => $movement->qty_change < 0,
                            ])>
                                {{ $movement->qty_change > 0 ? '+' : '' }}{{ \App\Support\Format::number($movement->qty_change) }} {{ $product->unit }}
                            </td>
                            <td class="text-right tabular-nums">{{ \App\Support\Format::number($movement->balance_after) }} {{ $product->unit }}</td>
                            <td class="text-right">{{ \App\Support\Format::money($movement->unit_cost, 4) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-500">
                                Không có biến động tồn kho trong khoảng lọc này.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
