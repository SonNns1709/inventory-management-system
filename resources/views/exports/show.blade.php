@extends('layouts.app')

@section('title', 'Chi tiết phiếu xuất')

@section('content')
    <livewire:exports.export-show :export-id="$exportId" />
@endsection
