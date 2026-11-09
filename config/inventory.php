<?php

/*
 * Cấu hình nghiệp vụ riêng của hệ thống Quản lý hàng tồn kho
 * (tương đương config/gio_hanh_chinh.php của dự án Quản lý sinh viên).
 * Mỗi giá trị đều ghi rõ mã quy tắc trong báo cáo Nhóm 11 để dễ đối chiếu.
 */
return [

    /*
    | Admin có được lập / sửa / xác nhận phiếu nhập, xuất, kiểm kê không?
    | Báo cáo Bảng 3 (phân quyền): Admin "thực hiện được mọi nghiệp vụ của Nhân viên kho".
    | Nhưng BR03.01-1, BR03.02-1, BR04.03-1 ghi "chỉ Nhân viên kho được phép lập phiếu".
    | Hai chỗ chưa thống nhất nên khung để cấu hình: true (mặc định, theo Bảng 3) / false (theo BR).
    */
    'admin_can_operate' => (bool) env('INVENTORY_ADMIN_CAN_OPERATE', true),

    'auth' => [
        // BR01.01-2: tối đa 5 lần đăng nhập sai trong 15 phút đối với mỗi cặp email + địa chỉ IP (vượt -> HTTP 429)
        'login_max_attempts' => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'login_decay_minutes' => (int) env('LOGIN_DECAY_MINUTES', 15),

        // BR01.03-2: mật khẩu mới ít nhất 8 ký tự, có chữ và số
        'password_min_length' => 8,

        // BR06.01-9: độ dài mật khẩu tạm Admin cấp khi tạo/đặt lại mật khẩu (hiển thị đúng một lần)
        'temp_password_length' => 10,
    ],

    // Số chứng từ: <tiền tố><yyyymmdd>-<nnnn>, ví dụ PN20261102-0001 (bảng document_sequences, báo cáo §II.1.15)
    'documents' => [
        'prefixes' => [
            'import' => 'PN',
            'export' => 'PX',
            'adjustment' => 'KK',
        ],
        'sequence_digits' => 4,
    ],

    // Độ chính xác số thập phân khớp Data Dictionary: avg_cost/unit_cost DECIMAL(15,4), line_total/total DECIMAL(18,2)
    'decimals' => [
        'cost' => 4,
        'money' => 2,
    ],

    'pagination' => [
        'default' => 20,
        'max' => 100,
        'options' => [10, 20, 50, 100],
    ],

    'dashboard' => [
        // UC05.01 A1: danh sách rút gọn Low Stock trên Dashboard
        'low_stock_preview' => 5,
        // NFR05.01-2: không cache 5 chỉ số quá 1 phút (0 = không cache)
        'cache_seconds' => 0,
    ],
];
