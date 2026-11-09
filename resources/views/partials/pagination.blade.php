{{--
    Phân trang tiếng Việt cho các màn hình Livewire:  {{ $items->links('partials.pagination') }}
    Nút bấm gọi thẳng các action có sẵn của trait Livewire\WithPagination (previousPage / nextPage), nên component không cần viết thêm gì.
    Dùng view riêng thay vì view mặc định của Livewire để chữ tiếng Việt và các lớp Tailwind nằm trong resources/ (Tailwind tự quét).
--}}
@if ($paginator->total() > 0)
    @php($pageName = $paginator->getPageName())
    <nav role="navigation" aria-label="Phân trang" class="flex flex-wrap items-center justify-between gap-3 text-sm">
        <p class="text-slate-500">
            Hiển thị {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} trong tổng số {{ $paginator->total() }} bản ghi
        </p>

        @if ($paginator->hasPages())
            <div class="flex items-center gap-2">
                <button type="button" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled"
                        @disabled($paginator->onFirstPage()) class="btn btn-secondary">‹ Trước</button>

                <span class="px-2">Trang {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>

                <button type="button" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled"
                        @disabled(! $paginator->hasMorePages()) class="btn btn-secondary">Sau ›</button>
            </div>
        @endif
    </nav>
@endif
