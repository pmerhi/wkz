@props(['ort' => null, 'reservUrl' => null])
<section class="section reveal" id="mitbringen">
    <h2>Was muss ich mitbringen?{{ $ort ? ' – '.$ort : '' }}</h2>
    <p class="lead-intro">Damit der Termin reibungslos läuft: die nötigen Unterlagen je Anliegen –
        zum Aufklappen.</p>

    @foreach(config('checklisten', []) as $liste)
        <details class="faq-item">
            <summary>{{ $liste['titel'] }}</summary>
            <ul class="check-list">
                @foreach($liste['items'] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </details>
    @endforeach

    <div class="box box-tipp"><strong>Praktische Tipps:</strong> Viele Zulassungsstellen nehmen
        <strong>nur EC-/Kartenzahlung</strong> oder Bargeld – informiere dich vorab. Und: die geprägten
        <strong>Schilder bekommst du online oft günstiger</strong> als am Schilderstand vor dem Amt.
        Die passenden <a href="{{ url('/formulare') }}">Formulare findest du hier zum Download</a>.</div>
</section>
