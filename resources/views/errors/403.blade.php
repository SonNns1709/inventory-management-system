@extends('errors.layout')

@section('title', 'Không có quyền truy cập')
@section('code', '403')
@section('heading', 'Không có quyền truy cập')
@section('message')
    {{-- Thông điệp do middleware `role:` (abort(403, ...)) đặt sẵn tiếng Việt; thông điệp mặc định tiếng Anh của Policy thì bỏ qua --}}
    @php($detail = isset($exception) ? trim((string) $exception->getMessage()) : '')
    {{ $detail !== '' && $detail !== 'This action is unauthorized.' ? $detail : 'Tài khoản của bạn không được phép thực hiện thao tác này.' }}
@endsection
