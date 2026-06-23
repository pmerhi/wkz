@if($hatDaten)
<div class="oz" data-oz='{{ $dataJson }}'>
    <div class="oz-head oz-{{ $status['klasse'] }}">
        <span class="oz-dot"></span>
        <strong class="oz-status">{{ $status['text'] }}</strong>
    </div>

    {{-- Heute prominent mit Balken --}}
    @php $hf = $heute['fenster'] ?? []; @endphp
    <div class="oz-today">
        <div class="oz-today-top">
            <span class="oz-today-tag">Heute, {{ $heute['label'] ?? '' }}</span>
            <span class="oz-today-times">@forelse($hf as $f){{ !$loop->first ? ' · ' : '' }}{{ $f['von'] }}–{{ $f['bis'] }} Uhr @empty geschlossen @endforelse</span>
        </div>
        <div class="oz-track oz-track-lg">
            @foreach($hf as $f)
                <span class="oz-bar" style="left:{{ $f['left'] }}%;width:{{ $f['width'] }}%" title="{{ $f['von'] }}–{{ $f['bis'] }} Uhr"></span>
            @endforeach
            @if($nowPct !== null)<span class="oz-now" style="left:{{ $nowPct }}%" title="Jetzt"></span>@endif
        </div>
        <div class="oz-axis-lg" aria-hidden="true">
            @foreach($axisLabels as $a)<span style="left:{{ $a['left'] }}%">{{ $a['text'] }}</span>@endforeach
        </div>
    </div>

    {{-- Ganze Woche zum Aufklappen – normale Liste, ohne Balken --}}
    <details class="oz-week">
        <summary>Alle Öffnungszeiten anzeigen</summary>
        <table class="oz-table">
            @foreach($week as $t)
                <tr class="{{ $t['heute'] ? 'is-today' : '' }} {{ empty($t['fenster']) ? 'is-closed' : '' }}">
                    <th>{{ $t['label'] }}@if($t['heute']) <small>heute</small>@endif</th>
                    <td>@forelse($t['fenster'] as $f){{ $f['von'] }}–{{ $f['bis'] }} Uhr{{ !$loop->last ? ', ' : '' }}@empty geschlossen @endforelse</td>
                </tr>
            @endforeach
        </table>
    </details>
    <p class="oz-hint muted">Angaben ohne Gewähr – bitte vor dem Besuch prüfen.</p>
</div>

@once
<script>
(function(){
  var TAGE=['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
  function dayIdx(){return (new Date().getDay()+6)%7;}
  function pad(n){return (n<10?'0':'')+n;}
  function hhmm(m){return pad(Math.floor(m/60))+':'+pad(m%60);}
  function update(el){
    var data; try{data=JSON.parse(el.getAttribute('data-oz'));}catch(e){return;}
    var now=new Date(), nowMin=now.getHours()*60+now.getMinutes(), di=dayIdx();
    var head=el.querySelector('.oz-head'), txt=el.querySelector('.oz-status');
    var wins=(data.tage[TAGE[di]]||[]), offen=false, bis=null;
    wins.forEach(function(w){ if(nowMin>=w[0]&&nowMin<w[1]){offen=true;bis=w[1];} });
    var klasse,text;
    if(offen){ var bald=(bis-nowMin)<=60; klasse=bald?'bald':'offen';
      text=(bald?'Schließt bald':'Jetzt geöffnet')+' · bis '+hhmm(bis)+' Uhr'; }
    else { var found=null,wann=null;
      for(var i=0;i<7;i++){ var ws=data.tage[TAGE[(di+i)%7]]||[];
        for(var j=0;j<ws.length;j++){ if(i===0&&ws[j][0]<=nowMin)continue; found=ws[j]; wann=(i===0?'heute':i===1?'morgen':null); break; }
        if(found)break; }
      klasse='zu'; text=found?('Geschlossen · öffnet '+(wann||'bald')+' um '+hhmm(found[0])+' Uhr'):'Geschlossen'; }
    head.className='oz-head oz-'+klasse; if(txt)txt.textContent=text;
    // Jetzt-Marker nur in der Heute-Vorschau
    var span=Math.max(1,data.end-data.start), pct=(nowMin-data.start)/span*100;
    el.querySelectorAll('.oz-track-lg .oz-now').forEach(function(m){m.remove();});
    var t=el.querySelector('.oz-track-lg');
    if(t&&pct>=0&&pct<=100){var mk=document.createElement('span');mk.className='oz-now';mk.style.left=pct+'%';t.appendChild(mk);}
    el.querySelectorAll('.oz-table tr').forEach(function(r,i){ r.classList.toggle('is-today',i===di); });
  }
  function run(){document.querySelectorAll('.oz[data-oz]').forEach(update);}
  run(); setInterval(run,60000);
})();
</script>
@endonce
@endif
