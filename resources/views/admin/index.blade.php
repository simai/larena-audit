@extends('larena-admin::layouts.app')

@section('title', __('larena-audit::admin.title') . ' · Larena')
@section('eyebrow', __('larena-audit::admin.eyebrow'))
@section('heading', __('larena-audit::admin.heading'))
@section('description', __('larena-audit::admin.description'))

@section('content')
    <section class="larena-panel" aria-label="{{ __('larena-audit::admin.region_label') }}" data-larena-audit-history="persistent">
        @if ($events === [])
            <div data-larena-audit-empty>{!! $historyUi !!}</div>
        @else
            {!! $historyUi !!}
        @endif
    </section>
@endsection
