<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Xung đột nghiệp vụ -> HTTP 409 (báo cáo Chương 3 §III): hết tồn khi xuất, trùng SKU,
 * xóa danh mục còn sản phẩm, sửa phiếu đã xác nhận, lỗi transaction...
 *
 * Tầng Service ném ngoại lệ này; bootstrap/app.php đổi thành JSON { "message": ... } (API)
 * hoặc quay lại trang trước kèm thông báo lỗi (web), nên Controller không cần try/catch.
 */
class BusinessConflictException extends RuntimeException {}
