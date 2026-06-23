@props(['placement'])
<a {{ $attributes->merge(['rel' => 'sponsored nofollow noopener', 'target' => '_blank', 'class' => 'js-affiliate']) }}
   data-label="intext:{{ $placement }}" href="{{ url('/go/'.$placement) }}">{{ $slot }}</a>
