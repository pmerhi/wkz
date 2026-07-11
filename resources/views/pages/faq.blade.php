<x-layout :title="$title" :description="$description" :canonical="$canonical" :schemas="$schemas">
    <nav class="breadcrumb">
        <a href="{{ url('/') }}">Start</a> › FAQ
    </nav>

    <h1>Kundenservice &amp; FAQ</h1>
    <p class="lead">Antworten auf häufige Fragen rund um Wunschkennzeichen, Reservierung,
        Schilderprägung, Versand und die Kfz-Zulassung.</p>

    @foreach($faq as $cat)
        <section class="section reveal faq">
            <h2>{{ $cat['cat'] }}</h2>
            @foreach($cat['items'] as [$frage, $antwort])
                <details>
                    <summary>{{ $frage }}</summary>
                    <div class="faq-answer">{!! $antwort !!}</div>
                </details>
            @endforeach
        </section>
    @endforeach
</x-layout>
