<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" :ogType="$ogType" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/ratgeber') }}">Ratgeber</a> › {{ $artikel->titel }}
    </nav>

    <h1>{{ $artikel->titel }}</h1>
    @if($artikel->kategorie)<p class="muted">{{ $artikel->kategorie->name }}</p>@endif
    @if($artikel->intro)<p><strong>{{ $artikel->intro }}</strong></p>@endif

    <div class="content">
        {!! \Illuminate\Support\Str::markdown($artikel->body ?? '') !!}
    </div>

    @if($artikel->tags->isNotEmpty())
        <p>
            @foreach($artikel->tags as $t)
                <span class="badge">{{ $t->name }}</span>
            @endforeach
        </p>
    @endif

    @if($artikel->stand_datum || $artikel->quelle)
        <p class="muted">
            @if($artikel->stand_datum)Rechtsstand: {{ $artikel->stand_datum->format('d.m.Y') }}@endif
            @if($artikel->quelle) · Quelle: {{ $artikel->quelle }}@endif
        </p>
    @endif

    <x-ad-slot position="ratgeber_unten" />
</x-layout>
