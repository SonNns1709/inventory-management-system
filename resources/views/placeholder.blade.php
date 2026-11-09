@extends('layouts.app')

@section('title', $screen['label'])

@section('content')
    @php($routeName = request()->route()?->getName())

    <h1 class="page-title">{{ $screen['label'] }}</h1>
    <p class="page-subtitle">
        Màn hình khung — chưa cài đặt. Route, quyền truy cập, Form Request, Policy, Service và API tương ứng đã được nối sẵn;
        phần giao diện dự kiến làm ở Sprint {{ $screen['sprint'] }}.
    </p>

    <div class="card max-w-3xl space-y-5">
        <dl class="grid grid-cols-1 gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="label">Use Case (Chương 2)</dt>
                <dd class="flex flex-wrap gap-1">
                    @foreach ($screen['uc'] as $uc)
                        <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs">{{ $uc }}</span>
                    @endforeach
                </dd>
            </div>
            <div>
                <dt class="label">Sprint dự kiến (Bảng 7)</dt>
                <dd>Sprint {{ $screen['sprint'] }}</dd>
            </div>
            <div>
                <dt class="label">Vai trò được truy cập</dt>
                <dd>{{ collect($screen['roles'])->map(fn (string $role) => \App\Enums\Role::from($role)->label())->implode(', ') }}</dd>
            </div>
            <div>
                <dt class="label">Lớp Service cần cài đặt</dt>
                <dd><code class="text-xs">App\Services\{{ $screen['service'] }}</code></dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="label">API REST tương ứng (Chương 3 §III)</dt>
                <dd>
                    <ul class="space-y-1">
                        @foreach ($screen['api'] as $endpoint)
                            <li><code class="text-xs">{{ $endpoint }}</code></li>
                        @endforeach
                    </ul>
                </dd>
            </div>
        </dl>

        <div class="rounded-md bg-slate-50 p-4 text-sm">
            <p class="font-semibold text-brand">Cách cài đặt màn hình này</p>
            <ol class="mt-2 list-decimal space-y-1 pl-5">
                <li>Sao khuôn từ màn hình tham chiếu: <code class="text-xs">app/Livewire/Categories/CategoryIndex.php</code> và <code class="text-xs">resources/views/livewire/categories/</code>.</li>
                <li>Trong <code class="text-xs">routes/web.php</code>, đổi route <code class="text-xs">{{ $routeName }}</code> từ <code class="text-xs">PlaceholderController</code> sang Controller / Livewire thật.</li>
                <li>Đặt <code class="text-xs">'ready' => true</code> cho mục menu này trong <code class="text-xs">config/navigation.php</code>.</li>
                <li>Cài đặt các phương thức Service còn ném <code class="text-xs">NotImplementedException</code> (HTTP 501) — xem chú thích TODO trong từng phương thức.</li>
            </ol>
        </div>
    </div>
@endsection
