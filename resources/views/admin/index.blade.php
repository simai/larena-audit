@extends('larena-admin::layouts.app')

@section('title', __('larena-audit::admin.title') . ' · Larena')
@section('eyebrow', __('larena-audit::admin.eyebrow'))
@section('heading', __('larena-audit::admin.heading'))
@section('description', __('larena-audit::admin.description'))

@section('content')
    <section class="larena-panel" aria-label="{{ __('larena-audit::admin.region_label') }}" data-larena-audit-history="persistent">
        @if ($events === [])
            <div class="larena-empty" data-larena-audit-empty>
                <h2>{{ __('larena-audit::admin.empty_title') }}</h2>
                <p>{{ __('larena-audit::admin.empty_description') }}</p>
            </div>
        @else
            <table class="larena-table larena-table-stack">
                <thead><tr><th>{{ __('larena-audit::admin.columns.operation') }}</th><th>{{ __('larena-audit::admin.columns.subject') }}</th><th>{{ __('larena-audit::admin.columns.actor') }}</th><th>{{ __('larena-audit::admin.columns.detail') }}</th><th>{{ __('larena-audit::admin.columns.time') }}</th></tr></thead>
                <tbody>
                @foreach ($events as $event)
                    <tr data-larena-audit-event="{{ $event['id'] }}">
                        <td data-label="{{ __('larena-audit::admin.columns.operation') }}"><strong>{{ __('larena-audit::admin.operations.' . $event['operation']) }}</strong><br><small>{{ $event['operation_code'] }}</small></td>
                        <td data-label="{{ __('larena-audit::admin.columns.subject') }}"><code>{{ $event['subject'] }}</code></td>
                        <td data-label="{{ __('larena-audit::admin.columns.actor') }}"><code>{{ $event['actor'] }}</code></td>
                        <td data-label="{{ __('larena-audit::admin.columns.detail') }}">@forelse($event['detail'] as $key => $value)<small><strong>{{ $key }}:</strong> {{ $key === 'slug' ? '/'.$value : $value }}</small>@if(!$loop->last)<br>@endif @empty<span aria-label="{{ __('larena-audit::admin.not_recorded') }}">—</span>@endforelse</td>
                        <td data-label="{{ __('larena-audit::admin.columns.time') }}"><time datetime="{{ $event['occurred_at'] }}">{{ $event['occurred_at'] }}</time></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
