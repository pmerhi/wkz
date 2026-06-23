@props(['position'])
@php
    $ads = \App\Models\Placement::with('partner')
        ->where('aktiv', true)
        ->where('typ', 'block')
        ->where('position', $position)
        ->get();
@endphp
@if($ads->isNotEmpty())
    <aside class="ad-slot" aria-label="Anzeige" style="margin:24px 0;">
        @foreach($ads as $ad)
            <a class="card js-affiliate" data-label="{{ $position }}:{{ $ad->name }}" style="display:block;border-style:dashed;"
               href="{{ url('/go/'.$ad->id) }}" rel="sponsored nofollow noopener" target="_blank">
                <span class="muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;">Anzeige</span>
                <div>{{ $ad->name }}@if($ad->partner) <span class="muted">· {{ $ad->partner->name }}</span>@endif</div>
            </a>
        @endforeach
    </aside>
@endif
