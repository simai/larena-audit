{{-- The admin shell's node renderer, as a fixture. --}}
@foreach ($nodes as $node){!! \Larena\Ui\Smart::render($node['component'], $node['props'])->html !!}@endforeach
