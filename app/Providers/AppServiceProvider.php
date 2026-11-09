<?php

namespace App\Providers;

use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsActive;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // BR01.03-2: chính sách mật khẩu — ít nhất 8 ký tự, có chữ và số. Dùng bằng Password::defaults() ở mọi Form Request.
        Password::defaults(fn () => Password::min((int) config('inventory.auth.password_min_length', 8))
            ->letters()
            ->numbers());

        // Quyền không gắn với một Model cụ thể: báo cáo chỉ dành cho Admin (BR05.02-1, BR05.03-1, BR05.04-1)
        Gate::define('view-reports', fn (User $user): bool => $user->isAdmin());

        // View composer: mỗi lần dựng sidebar thì nạp menu theo vai trò từ config/navigation.php,
        // nhờ đó không controller nào phải tự truyền menu xuống view.
        View::composer('partials.sidebar', function ($view) {
            $view->with('navigation', Navigation::forUser(Auth::user()));
        });

        $this->registerLivewirePersistentMiddleware();
    }

    /**
     * Livewire gửi thao tác của component tới một endpoint chung (/livewire/update), nên middleware đặt trên route
     * của TRANG (role, active, password.changed) không tự chạy lại ở các request đó. Đăng ký "persistent middleware"
     * để các middleware này vẫn được áp dụng cho mọi thao tác của component. Ngoài ra mỗi action Livewire
     * vẫn tự gọi $this->authorize() (Policy) như một lớp bảo vệ thứ hai.
     */
    private function registerLivewirePersistentMiddleware(): void
    {
        if (! $this->app->bound('livewire')) {
            return;
        }

        $livewire = $this->app->make('livewire');

        if (method_exists($livewire, 'addPersistentMiddleware')) {
            $livewire->addPersistentMiddleware([
                EnsureUserHasRole::class,
                EnsureUserIsActive::class,
                EnsurePasswordChanged::class,
            ]);
        }
    }
}
