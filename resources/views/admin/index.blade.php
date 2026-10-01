@extends('larena-admin::layouts.app')

@section('title', __('larena-audit::admin.title') . ' · Larena')
@section('eyebrow', __('larena-audit::admin.eyebrow'))
@section('heading', __('larena-audit::admin.heading'))
@section('description', __('larena-audit::admin.description'))

@section('content')
    <section class="larena-panel" aria-label="{{ __('larena-audit::admin.region_label') }}" data-larena-audit-history="persistent">
        @if ($events === [])
            <div data-larena-audit-empty>{!! $emptyUi !!}</div>
        @else
            @include('larena-admin::dataview.list', ['list' => $historyList])
        @endif
    </section>
@endsection
