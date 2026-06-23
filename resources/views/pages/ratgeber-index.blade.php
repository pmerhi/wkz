<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <nav class="breadcrumb"><a href="{{ url('/') }}">Start</a> › Ratgeber</nav>
    <h1>Ratgeber rund um die Kfz-Zulassung</h1>

    @if($artikel->isEmpty())
        <p class="muted">Noch keine Artikel veröffentlicht.</p>
    @else
        <ul>
            @foreach($artikel as $a)
                <li>
                    <a href="{{ url('/ratgeber/'.$a->slug) }}">{{ $a->titel }}</a>
                    @if($a->kategorie)<span class="muted"> · {{ $a->kategorie->name }}</span>@endif
                    @if($a->intro)<div class="muted">{{ \Illuminate\Support\Str::limit($a->intro, 140) }}</div>@endif
                </li>
            @endforeach
        </ul>
    @endif
</x-layout>
