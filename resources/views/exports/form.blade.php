@extends('layouts.app')

@section('title', 'Phiếu xuất kho')

@section('content')
    <livewire:exports.export-form :export-id="$exportId" />
@endsection
