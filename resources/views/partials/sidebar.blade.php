{{--
    Menu bên trái. Biến $navigation do view composer trong AppServiceProvider nạp (đã lọc theo vai trò từ config/navigation.php),
    nên không controller nào phải tự truyền menu xuống view. Mục chưa cài đặt (ready = false) có nhãn "S<sprint>".
--}}
<aside :class="{ 'max-md:block': menu }" class="hidden bg-brand-light py-3 text-sm text-slate-100 md:block">
    <nav aria-label="Menu chính">
        @foreach ($navigation as $group)
            <h3 class="px-5 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-white/60">{{ $group['title'] }}</h3>
            <ul>
                @foreach ($group['items'] as $item)
                    @php($active = request()->routeIs($item['route']))
                    <li>
                        <a href="{{ route($item['route']) }}"
                           @class([
                               'flex items-center justify-between gap-2 border-l-4 px-5 py-2 transition hover:bg-white/10',
                               'border-accent bg-white/10 font-semibold text-white' => $active,
                               'border-transparent' => ! $active,
                           ])>
                            <span>{{ $item['label'] }}</span>
                            @unless ($item['ready'])
                                <span class="rounded bg-white/15 px-1.5 py-0.5 text-[10px]"
                                      title="Màn hình khung — dự kiến Sprint {{ $item['sprint'] }}">S{{ $item['sprint'] }}</span>
                            @endunless
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>
</aside>
