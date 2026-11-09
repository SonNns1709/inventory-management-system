<?php

/*
 * Thông báo kiểm tra dữ liệu mặc định (tiếng Việt) cho các luật dùng trong dự án.
 * Các Form Request còn khai báo thông báo riêng bằng trait App\Http\Requests\Concerns\VietnameseMessages;
 * khóa nào thiếu ở đây sẽ tự lấy từ bản tiếng Anh của Laravel (APP_FALLBACK_LOCALE=en).
 */
return [
    'accepted' => ':attribute phải được chấp nhận.',
    'after' => ':attribute phải là ngày sau :date.',
    'after_or_equal' => ':attribute phải là ngày sau hoặc bằng :date.',
    'array' => ':attribute phải là một mảng.',
    'before' => ':attribute phải là ngày trước :date.',
    'before_or_equal' => ':attribute phải là ngày trước hoặc bằng :date.',
    'between' => [
        'array' => ':attribute phải có từ :min đến :max phần tử.',
        'numeric' => ':attribute phải nằm trong khoảng :min đến :max.',
        'string' => ':attribute phải có từ :min đến :max ký tự.',
    ],
    'boolean' => ':attribute phải là đúng hoặc sai.',
    'confirmed' => 'Xác nhận :attribute không khớp.',
    'current_password' => 'Mật khẩu hiện tại không đúng.',
    'date' => ':attribute không phải là ngày hợp lệ.',
    'date_format' => ':attribute không đúng định dạng :format.',
    'decimal' => ':attribute phải có :decimal chữ số thập phân.',
    'different' => ':attribute và :other phải khác nhau.',
    'distinct' => ':attribute bị trùng lặp.',
    'email' => ':attribute không đúng định dạng email.',
    'exists' => ':attribute đã chọn không tồn tại hoặc không khả dụng.',
    'filled' => ':attribute không được để trống.',
    'gt' => [
        'numeric' => ':attribute phải lớn hơn :value.',
        'string' => ':attribute phải dài hơn :value ký tự.',
    ],
    'gte' => [
        'numeric' => ':attribute phải lớn hơn hoặc bằng :value.',
        'string' => ':attribute phải dài hơn hoặc bằng :value ký tự.',
    ],
    'in' => ':attribute không hợp lệ.',
    'integer' => ':attribute phải là số nguyên.',
    'json' => ':attribute phải là chuỗi JSON hợp lệ.',
    'lt' => [
        'numeric' => ':attribute phải nhỏ hơn :value.',
        'string' => ':attribute phải ngắn hơn :value ký tự.',
    ],
    'lte' => [
        'numeric' => ':attribute phải nhỏ hơn hoặc bằng :value.',
        'string' => ':attribute phải ngắn hơn hoặc bằng :value ký tự.',
    ],
    'max' => [
        'array' => ':attribute không được vượt quá :max phần tử.',
        'numeric' => ':attribute không được lớn hơn :max.',
        'string' => ':attribute không được vượt quá :max ký tự.',
    ],
    'min' => [
        'array' => ':attribute phải có ít nhất :min phần tử.',
        'numeric' => ':attribute không được nhỏ hơn :min.',
        'string' => ':attribute phải có ít nhất :min ký tự.',
    ],
    'not_in' => ':attribute đã chọn không hợp lệ.',
    'numeric' => ':attribute phải là số.',
    'password' => [
        'letters' => ':attribute phải chứa ít nhất một chữ cái.',
        'mixed' => ':attribute phải chứa cả chữ hoa và chữ thường.',
        'numbers' => ':attribute phải chứa ít nhất một chữ số.',
        'symbols' => ':attribute phải chứa ít nhất một ký tự đặc biệt.',
        'uncompromised' => ':attribute đã xuất hiện trong dữ liệu bị rò rỉ, vui lòng chọn mật khẩu khác.',
    ],
    'present' => ':attribute phải có mặt.',
    'regex' => ':attribute không đúng định dạng.',
    'required' => ':attribute không được để trống.',
    'required_if' => ':attribute không được để trống khi :other là :value.',
    'required_with' => ':attribute không được để trống khi có :values.',
    'same' => ':attribute và :other phải giống nhau.',
    'string' => ':attribute phải là chuỗi ký tự.',
    'unique' => ':attribute này đã tồn tại.',

    'custom' => [],

    'attributes' => [
        'email' => 'Email',
        'password' => 'Mật khẩu',
        'password_confirmation' => 'Xác nhận mật khẩu',
        'current_password' => 'Mật khẩu hiện tại',
        'per_page' => 'Số dòng mỗi trang',
        'keyword' => 'Từ khóa',
        'from' => 'Từ ngày',
        'to' => 'Đến ngày',
    ],
];
