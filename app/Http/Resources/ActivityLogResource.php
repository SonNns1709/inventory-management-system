<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Biểu diễn JSON của Nhật ký hoạt động (audit log) — khuôn dữ liệu nằm trong khóa `data` của phản hồi.
 *
 * Quy tắc đối chiếu bổ sung:
 *  - BR06.02-5: `properties` là JSON giá trị cũ / mới của bản ghi để Admin xem chi tiết một dòng nhật ký.
 */
class ActivityLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'action' => $this->action,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'description' => $this->description,
            'properties' => $this->properties,
            'ip_address' => $this->ip_address,
            'user' => UserResource::make($this->whenLoaded('user')),
            'created_at' => $this->created_at,
        ];
    }
}
