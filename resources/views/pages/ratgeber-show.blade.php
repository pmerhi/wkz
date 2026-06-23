<x-layout :title="$title" :description="$description" :canonical="$canonical" :robots="$robots" :ogType="$ogType" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> ›
        <a href="{{ url('/ratgeber') }}">Ratgeber</a> › {{ $artikel->titel }}
    </nav>

    <section class="hero hero-sm reveal in">
        @if($artikel->kategorie)<p class="badge" style="background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.35);color:#fff">{{ $artikel->kategorie->name }}</p>@endif
        <h1>{{ $artikel->titel }}</h1>
        @if($artikel->intro)<p class="lead">{{ $artikel->intro }}</p>@endif
    </section>

    <article class="content wrap--narrow reveal">
        {!! \Illuminate\Support\Str::markdown($artikel->body ?? '') !!}
    </article>

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
