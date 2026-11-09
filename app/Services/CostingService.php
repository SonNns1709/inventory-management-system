<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Giá vốn bình quân gia quyền (WAC — Weighted Average Cost) — báo cáo UC03.01 (BR03.01-5), BR03.02-5/6, BR05.01-3.
 *
 *   giá vốn mới = (tồn cũ × giá vốn cũ + số lượng nhập × đơn giá nhập) / (tồn cũ + số lượng nhập)
 *
 * "Tồn cũ" là TỔNG tồn của sản phẩm trên mọi kho, vì giá vốn (products.avg_cost) là giá của sản phẩm chứ không của từng kho.
 * Kết quả làm tròn 4 chữ số thập phân (DECIMAL(15,4) trong CSDL), trả về dạng chuỗi để không mất độ chính xác.
 *
 * Dùng bcmath khi extension có sẵn (không sai số); nếu thiếu thì dùng số thực với round() làm phương án dự phòng.
 */
class CostingService
{
    public function weightedAverage(int $oldQty, string|int|float $oldAvg, int $qtyIn, string|int|float $unitCost): string
    {
        $decimals = (int) config('inventory.decimals.cost', 4);
        $totalQty = $oldQty + $qtyIn;

        // Không có hàng để tính bình quân (số lượng nhập = 0 và tồn = 0): giữ nguyên giá vốn cũ.
        if ($totalQty <= 0) {
            return $this->format($oldAvg, $decimals);
        }

        if (function_exists('bcadd')) {
            $sum = bcadd(
                bcmul((string) $oldQty, $this->format($oldAvg, 8), 8),
                bcmul((string) $qtyIn, $this->format($unitCost, 8), 8),
                8,
            );

            // bcmath cắt cụt, nên cộng nửa đơn vị cuối rồi mới cắt để được làm tròn "half up"
            return bcadd(bcdiv($sum, (string) $totalQty, 8), '0.'.str_repeat('0', $decimals).'5', $decimals);
        }

        return $this->format(round((($oldQty * (float) $oldAvg) + ($qtyIn * (float) $unitCost)) / $totalQty, $decimals), $decimals);
    }

    /** Chuẩn hóa một giá trị tiền về chuỗi 4 chữ số thập phân (khớp DECIMAL(15,4) của unit_cost / avg_cost). */
    public function cost(string|int|float $value): string
    {
        return $this->format($value, (int) config('inventory.decimals.cost', 4));
    }

    /** Giá trị tồn kho = Σ (số lượng tồn × giá vốn bình quân) trên mọi sản phẩm và kho (BR05.01-3). Chuỗi 2 chữ số thập phân. */
    public function inventoryValue(): string
    {
        $value = DB::table('inventories')
            ->join('products', 'products.id', '=', 'inventories.product_id')
            ->selectRaw('COALESCE(SUM(inventories.quantity * products.avg_cost), 0) as total_value')
            ->value('total_value');

        return $this->format($value ?? 0, (int) config('inventory.decimals.money', 2));
    }

    private function format(string|int|float $value, int $decimals): string
    {
        return number_format((float) $value, $decimals, '.', '');
    }
}
