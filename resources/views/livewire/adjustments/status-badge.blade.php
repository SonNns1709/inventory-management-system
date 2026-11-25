@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    [$label, $classes] = match ($value) {
        'confirmed' => ['Đã xác nhận', 'bg-green-100 text-green-800'],
        'cancelled' => ['Đã hủy', 'bg-slate-200 text-slate-600'],
        default => ['Nháp', 'bg-amber-100 text-amber-800'],
    };
@endphp
<span class="inline-block rounded-full px-2 py-0.5 text-xs font-semibold {{ $classes }}">{{ $label }}</span>
