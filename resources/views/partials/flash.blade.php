{{-- Thông báo flash dùng chung: redirect()->with('success' | 'error' | 'warning' | 'info', '...') --}}
@php
    $flashTypes = [
        'success' => 'border-green-300 bg-green-50 text-green-800',
        'error' => 'border-red-300 bg-red-50 text-red-800',
        'warning' => 'border-amber-300 bg-amber-50 text-amber-800',
        'info' => 'border-sky-300 bg-sky-50 text-sky-800',
    ];
@endphp
@foreach ($flashTypes as $type => $classes)
    @if (session($type))
        <div role="alert" class="mb-4 rounded-md border px-4 py-3 text-sm {{ $classes }}">{{ session($type) }}</div>
    @endif
@endforeach
