<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Thu hồi các phiên đăng nhập của một tài khoản: phiên web (bảng sessions, SESSION_DRIVER=database)
 * và token API (bảng personal_access_tokens của Sanctum).
 *
 * Dùng ở ba nơi theo báo cáo:
 *  - BR01.03-3: đổi mật khẩu xong thì thu hồi các phiên KHÁC (giữ lại phiên đang dùng);
 *  - BR06.01-8: khóa tài khoản thì thu hồi NGAY mọi phiên;
 *  - BR06.01-9: đặt lại mật khẩu tạm thì thu hồi mọi phiên cũ.
 */
final class SessionRevoker
{
    /**
     * @param  string|null  $exceptSessionId  id phiên web cần giữ lại (thường là $request->session()->getId())
     * @param  int|null  $exceptTokenId  id token API cần giữ lại (thường là $user->currentAccessToken()->id)
     */
    public static function revoke(User $user, ?string $exceptSessionId = null, ?int $exceptTokenId = null): void
    {
        // Token API (Sanctum)
        $user->tokens()
            ->when($exceptTokenId !== null, fn ($query) => $query->where('id', '!=', $exceptTokenId))
            ->delete();

        // Phiên web: chỉ có bảng sessions khi SESSION_DRIVER=database (cấu hình mặc định của dự án)
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())
                ->when($exceptSessionId !== null, fn ($query) => $query->where('id', '!=', $exceptSessionId))
                ->delete();
        }
    }
}
