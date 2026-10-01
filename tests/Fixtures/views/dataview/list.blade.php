{{-- Stand-in for the admin shell's shared list: it shows the plain list description it was given. --}}
<section data-larena-fixture-list="{{ $list['id'] }}" data-page-id="{{ $list['page_id'] }}">
    <p>@foreach ($list['columns'] as $column){{ $column['label'] }} @endforeach</p>
    @foreach ($list['rows'] as $row)
        <p data-row-id="{{ $row['id'] }}">@foreach ($list['columns'] as $column){{ $row[$column['key']] }} | @endforeach</p>
    @endforeach
</section>
