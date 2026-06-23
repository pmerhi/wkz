<x-layout :title="$title" :canonical="$canonical" :robots="$robots">
    @if($html)
        <div class="content">{!! $html !!}</div>
    @else
        <h1>{{ $heading }}</h1>
        <p class="muted">Platzhalter — Entwurf folgt aus Arbeitspaket WP-7 (Recht/Lex).</p>
    @endif
</x-layout>
