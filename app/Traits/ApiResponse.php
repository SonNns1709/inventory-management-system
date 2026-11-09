<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Khuôn phản hồi JSON theo báo cáo Chương 3 §III (Thiết kế API):
 *   thành công : { "data": ... }            (200 / 201)
 *   lỗi 422    : { "message": ..., "errors": {...} }   (do bootstrap/app.php xử lý tập trung)
 *   lỗi khác   : { "message": ... }         (401 / 403 / 404 / 409 / 429)
 *
 * Khác demo1: demo1 trả { success, message, data }; báo cáo chỉ quy định khóa "data".
 */
trait ApiResponse
{
    /** { "data": ... } kèm các khóa phụ (ví dụ "meta" khi phân trang). */
    protected function success(mixed $data = null, int $code = 200, array $extra = []): JsonResponse
    {
        return response()->json(['data' => $data] + $extra, $code);
    }

    /** 201 Created. */
    protected function created(mixed $data = null): JsonResponse
    {
        return $this->success($data, 201);
    }

    /**
     * { "data": { "message": "..." } } — cho thao tác không trả về bản ghi
     * (xóa, đăng xuất, đổi mật khẩu...: "{ "data": ... } thông báo ... thành công" trong bảng API).
     */
    protected function message(string $message, int $code = 200): JsonResponse
    {
        return $this->success(['message' => $message], $code);
    }

    /** { "message": ... } — dùng cho lỗi do Controller chủ động trả về. */
    protected function error(string $message, int $code = 400, array $errors = []): JsonResponse
    {
        return response()->json(
            $errors === [] ? ['message' => $message] : ['message' => $message, 'errors' => $errors],
            $code,
        );
    }

    /**
     * Danh sách đã phân trang: "data" là các phần tử của trang hiện tại, "meta" là thông tin phân trang.
     *
     * @param  class-string<JsonResource>  $resource
     */
    protected function successPaginated(string $resource, LengthAwarePaginator $paginator): JsonResponse
    {
        return $this->success(
            $resource::collection($paginator->getCollection()),
            extra: [
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        );
    }
}
