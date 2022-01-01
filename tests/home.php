<p>
    Unprocessed: @{{ time() }}<br>
    Unprocessed: @{!! time() !!}<br>
    Sanitized: {{ time() }}<br>
    Unsanitized: {!! time() !!}
</p>

@php($content = "test")
@php
    $fruit = ['banana', 'tomatoes', 'kiwi'];
    $vegetables = ['lettuce', 'spinach'];
    $combined = [
        'fruit' => $fruit,
        'vegetable' => $vegetables
    ];
@endphp

@if($content)
    somethom
@endif

@if($content)
    somethom
@endif

@foreach($combined as $category=>$items)
    <b>{{ $category }}</b><br>
    <ul>
        @foreach($items as $item)
            <li>{{$item}} {{$loop->iteration}}</li>
        @endforeach
    </ul>
@endforeach

@section('hello')
<p>hello from the other side</p>
@show

<p>works?</p>
@yield('hello')


<x-button class="red">hello</x-button>
