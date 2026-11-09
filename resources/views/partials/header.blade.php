<header class="sticky top-0 z-20 flex items-center justify-between gap-3 bg-brand px-4 py-3 text-white md:col-span-2 md:px-6">
    <div class="flex items-center gap-3">
        {{-- Nút mở / đóng menu trên màn hình hẹp (biến `menu` khai báo bằng x-data ở layouts/app.blade.php) --}}
        <button type="button" class="rounded px-2 py-1 text-xl leading-none hover:bg-white/10 md:hidden"
                @click="menu = !menu" aria-label="Mở / đóng menu">☰</button>
        <a href="{{ route('dashboard') }}" class="text-lg font-bold tracking-wide">IMS<span class="text-accent">.Kho</span></a>
    </div>

    @auth
        <div class="flex items-center gap-3 text-sm">
            <span class="rounded-full bg-accent px-3 py-0.5 text-xs font-semibold text-slate-900">{{ auth()->user()->role->label() }}</span>
            <a href="{{ route('profile.show') }}" class="hidden hover:underline sm:inline">{{ auth()->user()->name }}</a>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="rounded border border-white/40 px-3 py-1 hover:bg-white/10">Đăng xuất</button>
            </form>
        </div>
    @endauth
</header>
