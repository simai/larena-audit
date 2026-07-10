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
                <thead><tr><th>{{ __('larena-audit::admin.columns.operation') }}</th><th>{{ __('larena-audit::admin.columns.page') }}</th><th>{{ __('larena-audit::admin.columns.actor') }}</th><th>{{ __('larena-audit::admin.columns.status') }}</th><th>{{ __('larena-audit::admin.columns.time') }}</th></tr></thead>
                <tbody>
                @foreach ($events as $event)
                    <tr data-larena-audit-event="{{ $event['id'] }}">
                        <td data-label="{{ __('larena-audit::admin.columns.operation') }}"><strong>{{ __('larena-audit::admin.operations.' . $event['operation']) }}</strong></td>
                        <td data-label="{{ __('larena-audit::admin.columns.page') }}">
                            @if ($event['slug'] !== null)
                                <code>/{{ $event['slug'] }}</code>
                            @else
                                <code>{{ $event['subject'] }}</code>
                            @endif
                            @if ($event['version'] !== null)<small>{{ __('larena-audit::admin.version', ['version' => $event['version']]) }}</small>@endif
                        </td>
                        <td data-label="{{ __('larena-audit::admin.columns.actor') }}"><code>{{ $event['actor'] }}</code></td>
                        <td data-label="{{ __('larena-audit::admin.columns.status') }}">
                            @if ($event['status'] !== null)
                                <span class="larena-status larena-status-{{ $event['status'] }}">{{ __('larena-audit::admin.statuses.' . $event['status']) }}</span>
                            @else
                                <span aria-label="{{ __('larena-audit::admin.not_recorded') }}">—</span>
                            @endif
                        </td>
                        <td data-label="{{ __('larena-audit::admin.columns.time') }}"><time datetime="{{ $event['occurred_at'] }}">{{ $event['occurred_at'] }}</time></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
