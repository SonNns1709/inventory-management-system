<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Đánh dấu chức năng CHƯA được cài đặt trong khung dự án (HTTP 501).
 *
 * Mọi phương thức Service còn ở dạng khung đều ném ngoại lệ này kèm mã Use Case, ví dụ:
 *   throw new NotImplementedException('UC03.01 — ImportService::confirm()');
 * Khi cài đặt xong một phương thức, chỉ việc thay dòng throw bằng logic thật —
 * Controller, Form Request, Resource và route phía trên đã được nối sẵn.
 */
class NotImplementedException extends HttpException
{
    public function __construct(string $feature = '')
    {
        parent::__construct(501, 'Chức năng chưa được cài đặt'.($feature !== '' ? ": {$feature}" : '').'.');
    }
}
