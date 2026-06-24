@props(['ort' => null, 'hatTermin' => false])
@php
    // Allgemeiner Wochenverlauf (Erfahrungswerte, KEINE Live-Daten): Andrang 1 (ruhig) – 4 (voll).
    $tage = ['Mo' => 4, 'Di' => 2, 'Mi' => 2, 'Do' => 3, 'Fr' => 4];
    $label = [1 => 'ruhig', 2 => 'ruhig', 3 => 'mittel', 4 => 'voll'];
@endphp
<section class="section reveal" id="besuchszeit">
    <h2>Beste Besuchszeit{{ $ort ? ' – '.$ort : '' }}</h2>
    <p class="lead-intro">Wann ist am wenigsten los? Erfahrungsgemäß sind <strong>Dienstag und Mittwoch
        vormittags</strong> am ruhigsten, Montag und Freitag am vollsten.</p>

    <div class="andrang" role="img" aria-label="Typischer Andrang je Wochentag">
        @foreach($tage as $tag => $lvl)
            <div class="andrang-col">
                <span class="andrang-bar lvl-{{ $lvl }}" style="height:{{ 14 + $lvl * 20 }}px" title="{{ $label[$lvl] }}"></span>
                <b>{{ $tag }}</b>
            </div>
        @endforeach
    </div>

    <div class="box box-tipp"><strong>Tipp:</strong> Komm möglichst gleich zur Öffnung am Vormittag –
        @if($hatTermin)oder buche einen <a href="#termin">Online-Termin</a> und spar dir die Wartezeit ganz.@else buche, wenn möglich, vorab einen Online-Termin und spar dir die Wartezeit ganz.@endif</div>
    <p class="muted" style="font-size:.8rem">Allgemeine Erfahrungswerte zum typischen Wochenverlauf – keine Live-Auslastung.</p>
</section>
