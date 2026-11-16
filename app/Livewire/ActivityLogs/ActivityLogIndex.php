<?php

namespace App\Livewire\ActivityLogs;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * UC06.02 — Nhật ký hoạt động (view: resources/views/livewire/activity-logs/activity-log-index.blade.php).
 *
 * Chỉ đọc (BR06.02-6, BR06.02-7): dữ liệu lấy từ ActivityLogService::search(), không có action sửa / xóa nào.
 * Thứ tự luôn là mới nhất trước (BR06.02-4) nên không có sort / direction. Giá trị lọc do trình duyệt gửi lên đều đi qua
 * ActivityLogFilter::valid(): ngày sai định dạng hoặc id lạ bị bỏ qua chứ không gây lỗi 500.
 */
class ActivityLogIndex extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $keyword = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    // Id người dùng giữ dạng chuỗi vì thẻ <select> luôn gửi chuỗi; '' nghĩa là "Tất cả".
    #[Url(as: 'user', except: '')]
    public string $userId = '';

    #[Url(except: '')]
    public string $action = '';

    public int $perPage = 20;

    public function mount(): void
    {
        // Livewire gọi lại component qua /livewire/update, nên kiểm tra quyền ở đây chứ không chỉ ở controller.
        $this->authorize('viewAny', ActivityLog::class);

        $this->perPage = (int) config('inventory.pagination.default', 20);
    }

    /** Đổi bộ lọc thì quay về trang 1. */
    public function updated(string $property): void
    {
        if (in_array($property, ['keyword', 'from', 'to', 'userId', 'action', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['keyword', 'from', 'to', 'userId', 'action']);
        $this->resetPage();
    }

    public function render(): View
    {
        // Chuỗi rỗng phải đổi thành null: ActivityLogFilter coi "có khóa và khác null" là đang lọc.
        $logs = app(ActivityLogService::class)->search([
            'keyword' => $this->keyword,
            'from' => $this->from !== '' ? $this->from : null,
            'to' => $this->to !== '' ? $this->to : null,
            'user_id' => $this->userId !== '' ? $this->userId : null,
            'action' => $this->action !== '' ? $this->action : null,
            'per_page' => $this->perPage,
        ]);

        return view('livewire.activity-logs.activity-log-index', [
            'logs' => $logs,
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            // Chỉ liệt kê những hành động ĐÃ có trong sổ, để ô chọn không có lựa chọn cho ra kết quả rỗng
            'actions' => ActivityLog::query()->distinct()->orderBy('action')->pluck('action'),
            // from > to: bộ lọc bỏ qua "Đến ngày" (after_or_equal), nên báo cho người dùng biết
            'dateError' => $this->from !== '' && $this->to !== '' && $this->to < $this->from,
        ]);
    }
}
