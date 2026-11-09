<?php

namespace App\Http\Requests\Concerns;

/**
 * Thông báo lỗi tiếng Việt dùng chung cho các Form Request (tương đương trait ThongBaoTiengViet của demo1).
 * Tên trường hiển thị (":attribute") lấy từ attributes() của từng request.
 */
trait VietnameseMessages
{
    /**
     * Luật max/min có thông báo riêng cho chuỗi (độ dài), số (giá trị) và mảng (số phần tử):
     * khai báo dạng mảng ['string' => ..., 'numeric' => ..., 'array' => ...].
     *
     * @return array<string, string|array<string, string>>
     */
    protected function commonMessages(): array
    {
        return [
            'required' => ':attribute không được để trống.',
            'required_if' => ':attribute không được để trống.',
            'required_with' => ':attribute không được để trống.',
            'string' => ':attribute phải là chuỗi ký tự.',
            'integer' => ':attribute phải là số nguyên.',
            'numeric' => ':attribute phải là số.',
            'boolean' => ':attribute không hợp lệ.',
            'email' => ':attribute không đúng định dạng email.',
            'date' => ':attribute không phải là ngày hợp lệ.',
            'date_format' => ':attribute không đúng định dạng :format.',
            'array' => ':attribute không hợp lệ.',
            'in' => ':attribute không hợp lệ.',
            'exists' => ':attribute đã chọn không tồn tại hoặc không khả dụng.',
            'unique' => ':attribute này đã tồn tại.',
            'confirmed' => 'Xác nhận :attribute không khớp.',
            'different' => ':attribute không được trùng với :other.',
            'distinct' => ':attribute bị trùng lặp.',
            'regex' => ':attribute không đúng định dạng.',
            'max' => [
                'string' => ':attribute không được vượt quá :max ký tự.',
                'numeric' => ':attribute không được lớn hơn :max.',
                'array' => ':attribute không được vượt quá :max phần tử.',
            ],
            'min' => [
                'string' => ':attribute phải có ít nhất :min ký tự.',
                'numeric' => ':attribute không được nhỏ hơn :min.',
                'array' => ':attribute phải có ít nhất :min phần tử.',
            ],
        ];
    }
}
