<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 28mm 18mm 22mm 18mm; }
        * { font-family: 'DejaVu Sans', sans-serif; }
        body { font-size: 11px; color: #1a1a1a; line-height: 1.5; }
        .brand { font-size: 10px; color: #1d4ed8; font-weight: bold; letter-spacing: .04em; }
        h1 { font-size: 17px; margin: 4px 0 2px; }
        .intro { font-size: 10.5px; color: #333; margin: 6px 0 14px; }
        .sec { margin: 0 0 12px; }
        .sec h2 { font-size: 11.5px; margin: 0 0 6px; padding-bottom: 3px; border-bottom: 1px solid #1d4ed8; color: #1e3a8a; }
        .field { margin: 0 0 10px; }
        .field .lbl { font-size: 9px; color: #555; margin-bottom: 9px; }
        .field .line { border-bottom: 1px solid #888; height: 1px; }
        .cbx { margin: 3px 0; }
        .box { display: inline-block; width: 11px; height: 11px; border: 1px solid #333; margin-right: 7px; }
        .sig { width: 100%; margin-top: 22px; }
        .sig td { width: 50%; padding-right: 18px; vertical-align: bottom; }
        .sig .l { border-bottom: 1px solid #333; height: 30px; }
        .sig .c { font-size: 9px; color: #555; padding-top: 4px; }
        .hint { margin-top: 26px; padding: 8px 10px; border: 1px solid #e2e8f0; background: #f8fafc;
                font-size: 8.5px; color: #555; }
        .foot { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 8px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="brand">{{ config('portal.site_name', 'Wunschkennzeichen-Portal') }}</div>
    <h1>{{ $form['titel'] }}</h1>
    <div class="intro">{{ $form['intro'] }}</div>

    @foreach($form['abschnitte'] as $abschnitt)
        <div class="sec">
            <h2>{{ $abschnitt['titel'] }}</h2>
            @foreach(($abschnitt['felder'] ?? []) as $feld)
                <div class="field">
                    @if($feld !== '')<div class="lbl">{{ $feld }}</div>@else<div style="height:9px"></div>@endif
                    <div class="line"></div>
                </div>
            @endforeach
            @foreach(($abschnitt['checkboxen'] ?? []) as $option)
                <div class="cbx"><span class="box"></span>{{ $option }}</div>
            @endforeach
        </div>
    @endforeach

    <table class="sig">
        <tr>
            @foreach($form['unterschriften'] as $u)
                <td><div class="l"></div><div class="c">{{ $u }}</div></td>
            @endforeach
        </tr>
    </table>

    <div class="hint">
        <strong>Hinweis:</strong> Dieses Formular ist ein kostenloses Muster zur Vorbereitung deines Behördengangs –
        kein amtliches Dokument und keine Rechtsberatung. Maßgeblich sind die Vorgaben deiner Zulassungsstelle;
        einzelne Behörden verlangen eigene Vordrucke. Angaben ohne Gewähr.
    </div>

    <div class="foot">{{ config('portal.site_name', 'Wunschkennzeichen-Portal') }} · {{ url('/formulare') }}</div>
</body>
</html>
