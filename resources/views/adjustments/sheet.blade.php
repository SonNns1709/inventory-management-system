@extends('layouts.app')

@section('title', 'Phiếu kiểm kê')

@section('content')
    <livewire:adjustments.adjustment-sheet :adjustment-id="$adjustmentId" />
@endsection
