@if($preconnect)
{!! $getPreconnectTags() !!}
@endif

@foreach($getAssetUrls() as $url)
    @if($type === 'css')
        <link href="{{ $url }}" rel="stylesheet">
    @elseif($type === 'js')
        <script src="{{ $url }}"></script>
    @endif
@endforeach


