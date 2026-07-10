@extends('larena-admin::layouts.app')

@section('title', 'Audit history · Larena')
@section('eyebrow', 'Operations')
@section('heading', 'Audit history')
@section('description', 'Review persistent Page activity recorded by Larena. Content bodies and sensitive payload fields are never shown here.')

@section('content')
    <section class="larena-panel" aria-label="Page audit history" data-larena-audit-history="persistent">
        @if ($events === [])
            <div class="larena-empty" data-larena-audit-empty>
                <h2>No Page activity yet</h2>
                <p>Create or update a page and its audit history will appear here.</p>
            </div>
        @else
            <table class="larena-table">
                <thead><tr><th>Operation</th><th>Page</th><th>Actor</th><th>Status</th><th>Time</th></tr></thead>
                <tbody>
                @foreach ($events as $event)
                    <tr data-larena-audit-event="{{ $event['id'] }}">
                        <td><strong>{{ $event['operation'] }}</strong></td>
                        <td>
                            @if ($event['slug'] !== null)
                                <code>/{{ $event['slug'] }}</code>
                            @else
                                <code>{{ $event['subject'] }}</code>
                            @endif
                            @if ($event['version'] !== null)<small>v{{ $event['version'] }}</small>@endif
                        </td>
                        <td><code>{{ $event['actor'] }}</code></td>
                        <td>
                            @if ($event['status'] !== null)
                                <span class="larena-status larena-status-{{ $event['status'] }}">{{ $event['status'] }}</span>
                            @else
                                <span aria-label="Not recorded">—</span>
                            @endif
                        </td>
                        <td><time datetime="{{ $event['occurred_at'] }}">{{ $event['occurred_at'] }}</time></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </section>
@endsection
