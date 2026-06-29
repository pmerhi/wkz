var crt_model = '';

Helper = {

    /**
     * @function getViewBox
     *
     * Thanks to Appliness 
     * http://www.appliness.com/491/#codesyntax_11
     *
     * @param {int} x1
     * @param {int} y1
     * @param {int} x2
     * @param {int} y2
     *
     * @return {string} viewBox
     */
    getViewBox: function(x1, y1, x2, y2){

        // 0 0 1362.502 1856.012

        // viewBox data (from SVG file)
        var svgMinX = 0;
        var svgMinY = 0;
        var svgWidth = 1362.502;
        var svgHeight = 1856.012;
     
        // actual size (from SVG, but can also be set in HTML)
        var width = 1362.502;
        var height = 1856.012;
     
        // width and height ratio
        var wRatio = svgWidth / width;
        var hRatio = svgHeight / height;
     
        // desired width in pixels 
        var desiredWidth = x2 - x1;
     
        // desired height in pixels
        var desiredHeight = y2 - y1;
     
        // new viewBox values                
        var newWidth = Math.round(desiredWidth * wRatio);
        var newHeight = Math.round(desiredHeight * hRatio);
     
        // initial minX and minY must be added
        var newMinX = svgMinX + Math.round(x1 * wRatio);
        var newMinY = svgMinY + Math.round(y1 * hRatio);
     
        return newMinX + " " + newMinY + " " + newWidth + " " + newHeight;
    }

};


/**
 * @function Altkennzeichen
 * 
 * @param {int} id
 * @param {int} kgs
 * @param {JSON} data
 *
 * @return {object} model
 */
Altkennzeichen = function(id, kgs, data){
    
    // Initialisiere jQuery Objekt im SVG DOM
    var el = $('#' + id)[0];

    /**
     * Model der Klasse AltkennzeichenKreis
     *
     * id:                      das jQuery Objekt des Altkennzeichen im SVG DOM
     * kuerzel:                 das Kuerzel des Altkennzeichens
     * beschreibung:            Text-Beschreibung des Altkennzeichens
     * status:                  der Altkennzeichen-Status ( Standard: 0 )
     * setAttr:                 Funktion, welche Beschreibung und Status des Altkennzeichens setzt
     * showAltkennzeichen:      Funktion, welche die Altkennzeichen einblendet
     * initialize:              Konstruktor Funktion
     */
    var model = {

        id: el,
        kuerzel: '',
        beschreibung: '',
        status: 0,

        /**
         * @function setAttr
         *
         * @param {object} model
         * @param {JSON} data
         */
        setAttr: function(model, data){

            // Hole das Kennzeichenkuerzel des Models (DOM)
            model.kuerzel = model.id.textContent;
            
            // Hole die uebergebenen Altkennzeichendaten (DB) und ordne Status und Beschreibung dem Model zu
            $(data).each(function(kennzeichen){

                // Wenn das Kennzeichen gesetzt ist setze Status und Beschreibung, oder lasse die Bezeichnung leer und Setze Status auf 0
                if (typeof $(this)[0][model.kuerzel] != 'undefined'){

                    model.beschreibung = $(this)[0][model.kuerzel].beschreibung;
                    model.status = $(this)[0][model.kuerzel].status;

                    // Vereinheitliche den Status fuer alle Kennzeichen die eingefuehrt oder abgelehnt wurden
                    if(model.status >= 5 && model.status <= 8){

                        model.status = 8;

                    }
                    else if(model.status == 9 || model.status == 10){

                        model.status = 10;

                    }

                    // Im Falle eines abgeschafften Kennzeichen (Status 12), setze das Kuerzel in Klammern
                    if(data[model.kuerzel]) {
                        if(data[model.kuerzel].in_klammern) {
                            model.id.textContent = model.id.textContent = '(' + model.id.textContent + ')';
                        }
                    }
                }
                else {

                    model.beschreibung = '';
                    model.status = 0;

                }

            });

        },

        /**
         * function showAltkennzeichen
         *
         * @param object model
         */
        showAltkennzeichen: function(model){

            // Blende das Altkennzeichen ein
            $(model.id).css('display', 'block');

        }

    };


    /**
     * function initialize
     *
     * @param object model
     *
     * @return object model
     */
    var initialize = function(model){

        // Setze die Informationen zum Altkennzeichen
        model.setAttr(model, data[kgs].kennzeichen);

        // Gebe model zurueck
        return model;

    };
    
    // Gebe model zurueck
    return initialize(model);

};


/**
 * function AltkennzeichenKreis
 *
 * @param var kgs
 * @param json data
 * 
 * @returen object model
 */
AltkennzeichenKreis = function(kgs, data){

    // Initialisiere jQuery Objekt im SVG DOM
    var el = $('#kreis__' + kgs)[0];
    
    /**
     * Model der Klasse AltkennzeichenKreis
     *
     * id:                      das jQuery Objekt des Kreises im SVG DOM
     * name:                    der Name des Kreises
     * kgs:                     der KGS des Kreises
     * status:                  der Altkennzeichen-Status des Kreises ( Standard: -1 )
     * parentId:                das jQuery Objekt des Elternelements im SVG DOM
     * parentName:              der Name des Elternelements im SVG DOM
     * altkennzeichen:          Funktion, welche die Kindelemente des Kreises (Altkennzeichen) instanziert
     * powertip:                Funktion, welche jedem Kindelement einen Tooltip anhaengt
     * showAltkennzeichen:      Funktion, welche die angezeigten Altkennzeichen des Kreises einblendet
     * infos:                   Funktion, welche die die data-Informationen fuer den Tooltip erstellt
     * initialize:              Konstruktor Funktion
     */
    var model = {

        id: el,
        name: data[kgs].kreisname,
        kgs: data[kgs].kgs,
        status: -1,
        parentId: $(el).parent()[0],
        parentName: data[kgs].bundesland,

        altkennzeichen: function(model, data){

            var altkennzeichen = [], // Initialisiere das Altkenzeichen-Array
                status = 0;          // Setze den internen Status
            
            // Hole alle Altkennzeichen des Kreises, erstelle neue Instanz der Klasse Altkennzeichen und ermittle den Gesamtstatus des Kreises
            $('text[id*="altkennzeichen__' + kgs + '"]').each(function(index, kennzeichen){

                // Hole die ID des Altkennzeichens( TEXT-Element )
                var id = $($(kennzeichen)[0]).attr('id');

                // Erstelle neue Instanz der Klasse Altkennzeichen. Uebergebe die ID des Altkennzeichens, den KGS und die JSON-Daten
                var it = new Altkennzeichen(id, model.kgs, data);

                // Pruefe ob das Altkennzeichen ein abgeschafftes Kennzeichen ist und ueberspringe die Statusaenderung, wenn das der Fall ist
                if(it.status != 12){

                    // Wenn sich der Status des weiteren Kennzeichens im Kreis aendert, setze den Status des Kreises auf 3 ( mehrere Status-Moeglichkeiten )
                    if(model.status != it.status){

                        if(model.status != -1){

                            status = 3;

                        }
                        
                    }

                    // Setze den Status des Altkennzeichens
                    model.status = it.status;

                }        

                // Fuege die Instanz des Altkennzeichens dem Altkennzeichen-Arrray hinzu
                altkennzeichen.push(it);

            });

            // Setze den Status des Models
            model.status = (status == 0) ? model.status : status;

            if(model.status == -1) model.status = 0;

            // Gebe Array mit den Altkennzeichen zurueck
            return altkennzeichen;

        },

        /**
         * function powertip
         *
         * @param object model
         * @param var manual
         */
        powertip: function(model, manual){

            // Haenge an alle Kindelemente des Kreises einen Tooltip
            $(model.id).children('text').powerTip({

                // http://stevenbenner.github.io/jquery-powertip/
                placement: 's',
                smartPlacement: true,
                manual: ((manual == 'on') ? false : true )

            });
        },

        /**
         * function showAltkennzeichen
         *
         * @param object model
         *
         */
        showAltkennzeichen: function(model){

            // Hole alle Kindelemente des Kreises
            $(model.altkennzeichen).each(function(index){

                // Fuehre die Funktion showAltkennzeichen aus, um die Kindelemnte einzublenden
                $(this)[0].showAltkennzeichen($(this)[0]);

            });

        },

        /**
         * function infos
         *
         * @param object model
         *
         * @return var infos
         */
        infos: function(model){
            
            // Initialisiere die Variable zur speicherung des HTML fuer den Inhalt des Tooltips
            var infos = '<b class="powertip__titel">Neue Kennzeichen:</b><ul class="powertip__list">';

            // Hole alle Altkennzeichen des Kreises und deren Status
            $(model.altkennzeichen).each(function(index){

                // Kennzeichenkuerzel des Altkennzeichens
                kuerzel = $(this)[0].kuerzel;

                // Status des Altkennzeichens
                status = $(this)[0].status;
                
                infos += '<li><b class="powertip__kuerzel">' + kuerzel + '</b>: <span class="powertip__status">';
                
                if(status >= 0 && status <= 2){

                    infos += 'Status ist unklar';

                }
                else if(status == 3){

                    infos += 'Einführung unsicher';

                }
                else if(status == 4){

                    infos += 'Wird wieder eingeführt';

                }
                else if(status >= 5 && status <= 8){

                    infos += 'Wurde eingeführt';

                }
                else if(status == 9 || status == 10){

                    infos += 'Einführung abgelehnt';

                }
                else if(status == 12){
                    infos += 'Nicht wiedereingeführt';
                }
                else {

                    infos += 'Unbekannt';

                }

                infos += '</span></li>';

            });

            infos += '</ul>';

            // Gebe Variable mit HTML zurueck
            return infos;

        }

    };
    
    /**
     * function initialize
     *
     * @param object model
     *
     * @return object model
     */
    var initialize = function(model){

        // Rufe die Funktion altkennzeichen auf, welche alle Kindelemente des Kreises zurueckgibt
        model.altkennzeichen = model.altkennzeichen(model, data);

        // Setzte die Klasse des jeweiligen Models um es per CSS anpassen zu koennen
        $(model.id).attr('class', 'kreis kreis--altkennzeichen status--' + model.status);

        // Setze das data Attribut fuer das Tooltip mit den Informationen zu den Altkennzeichen
        $(model.id).data('powertip', '<b class="powertip__kreistitel">Kreis ' + model.name + '</b>' + model.infos(model));

        // Gebe model zurueck
        return model;

    };
    
    // Gebe model zurueck
    return initialize(model);

};


/**
 * function Bundesland
 *
 * @param var kgs
 * @param json data
 *
 * @return object model
 */
Bundesland = function(kgs, data){

    // Initialisiere jQuery Objekt im SVG DOM
    var el = $('#bundesland__' + kgs)[0];

    // @TODO Bundeslaender-Array erstellen und Daten aus der SVG-Grafik nehmen

    /**
     * Model der Klasse Bundesland
     *
     * id:                      das jQuery Objekt des Bundeslandes im SVG DOM
     * name:                    der Name des Bundeslandes
     * kreise:                  holt ALLE Kindelemente (Kreise) des Bundeslandes
     * kgs:                     der KGS des Bundeslandes
     * kreiseAltkennzeichen:    Funktion, welche die Kindelemente des Landes mit Altkennzeichen (Kreise) instanziert
     * click:                   Funktion, welche bei einem Klick auf das Bundesland aufgerufen wird
     * powertip:                Funktion, welche jedem Kindelement einen Tooltip anhaengt
     * toggleGrenze:            Verschiebt die SVG Ebene mit der Bundeslandgrenze ueber die Kreiselemente, damit diese aktiv werden
     * initialize:              Konstruktor Funktion
     */
    var model = {

        id: el,
        name: $(el).data('name'),
        kreise: $(el).children('path, polygon'),
        kgs: kgs,
        zoomed: 0,
        
        /**
         * function kreiseAltkennzeichen
         *
         * @param var kgs
         * @param json data
         *
         * @return array kreiseAltkennzeichen
         */
        kreiseAltkennzeichen: function(kgs, data){

            // Initialisiere das AltkenzeichenKreise-Array
            var kreiseAltkennzeichen = [];

            // Hole alle Altkennzeichen-Kindelemente (Kreise) und erstelle jeweils anhand des KGS eine neue Instanz der Klasse AltkennzeichenKreis
            $(el).children('path[id*="kreis"], polygon[id*="kreis"]').each(function(index, kreis){

                // Extrahiere den KGS aus der ID des Elements
                var kgs = $(kreis).attr('id').substr($(kreis).attr('id').lastIndexOf('__') + 2, 5);

                // Erstelle Instanz der Klasse AltkennzeichenKreis und uebergebe den KGS und die JSON-Daten mit den Altkennzeichen-Informationen
                if(typeof data[kgs] !== 'undefined')
                {
                    kreiseAltkennzeichen.push(new AltkennzeichenKreis(kgs, data));
                }
            });

            // Gebe Array mit Altkennzeichen-Kreisen zurueck
            return kreiseAltkennzeichen;

        },

        /**
         * function click
         * 
         * @param object model
         */
        click: function(model){

            crt_model = model;

            // Fuehre den zoom nur aus, wenn die Karte nicht bereits gezoomt ist
            if(deutschland.zoom == 0){

                // Hole die Abmessungs-Koordinaten des SVG-Elements
                var bbox = model.id.getBBox();

                    var elViewBox = {
                        x1: bbox.x,
                        y1: bbox.y,
                        x2: (bbox.x + bbox.width),
                        y2: (bbox.y + bbox.height)
                    };

                // Setze die ViewBox des SVG-Elements neu (zoom)
                deutschland.svg[0].setAttribute('viewBox', Helper.getViewBox(elViewBox.x1, elViewBox.y1, elViewBox.x2, elViewBox.y2));

                // Setze zoom-Klasse fuer das Elternelement
                $(deutschland.id)[0].setAttribute('class', 'karte--zoom');

                // Setze zoom-Klasse fuer das Element
                $(model.id)[0].setAttribute('class', 'bundesland--zoom');

                // Blende JEDEN Text innerhalb des SVG-Elements aus
                $('.text--altkennzeichen').css('display', 'none');

                // Blende Zurueck-Button ein
                $('#button--back').show();

                // Hole alle Altkennzeichen des Altkennzeichen-Kreises
                if(!$('#checkbox__kreiskennzeichen').is(':checked'))
                {
                    $(model.kreiseAltkennzeichen).each(function(index){
    
                        // Blende das Altkennzeichen ein
                        $(this)[0].showAltkennzeichen($(this)[0]);
    
                    });
                }

                // Setze den Tooltip fuer den Kreis. Uebergebe das Model und den Status des Tooltips
                model.powertip(model, 'on');

                model.zoomed = 1;

                // Verschiebe das SVG-Element mit dem Umriss des Bundesland des Kreises nach ganz oben, um es fuer hover inaktiv zu machen
                model.toggleGrenze(model);

                deutschland.powertip('off');

            }
        },

        /**
         * function powetip
         *
         * @param object model
         * @param var manual
         */
        powertip: function(model, manual){

            // Haenge an alle Kindelemente des Landes einen Tooltip
            $(model.id).children().powerTip({

                // http://stevenbenner.github.io/jquery-powertip/
                placement: 's',
                smartPlacement: true,
                manual: ((manual == 'on') ? false : true )

            });

        },

        /**
         * function toggleGrenze
         *
         * @param object model
         */
        toggleGrenze: function(model){

            //@TODO: Besser loesen!

            // Merke das gezoomte Element
            deutschland.zoom = model.id;
            
        }
    };
    
    /**
     * function initialize
     *
     * @param object model
     *
     * @return object model
     */
    var initialize = function(model){

        // Funktion, welche bei einem Klick auf das Bundesland aufgerufen wird
        $(model.id).click(function(){

            // Interne click Function des Models
            model.click(model);

        });

        // Rufe die Funktion kreiseAltkennzeichen auf, welche alle Kindelemente des Bundeslandes zurueckgibt
        model.kreiseAltkennzeichen = model.kreiseAltkennzeichen(kgs, data);

        // Setze die data-Information, welche vom Tooltip ausgelesen und angezeigt werden
        $(model.id).data('powertip', model.name + '<br /><br /><span style="font-size:.8em;">Zum Vergrößern bitte klicken</span>');

        // Gebe model zurueck
        return model;
    };
    
    // Gebe model zurueck
    return initialize(model);
    
};


/**
 * @function Land
 *
 * @param {int} id
 * @param {svg} svg
 * @param {json} data
 * 
 * @return {object} model
 */
Land = function(id, svg, data){

    /**
     * Model der Klasse Land
     *
     * id:              das jQuery Objekt des Landes im SVG DOM
     * svg:             das jQuery Objekt der uebergeordnetetn SVG Grafik
     * zoom:            der Zoom-Status des SVG-Objekts
     * bundeslaender:   Funktion, welche die Kindelemente des Landes (Bundeslaender) instanziert
     * powertip:        Funktion, welche jedem Kindelement einen Tooltip anhaengt
     * initialize:      Konstruktor Funktion
     */
    this.model = {

        id: id,
        svg: svg,
        zoom: 0,

        /**
         * @function bundeslaender
         * @param {json} data
         * @return {object} bundeslaender
         */
        bundeslaender: function(data){

            // Initialisiere das Bundeslaender-Array
            var bundeslaender = [];

            // Hole alle Kindelemente und erstelle jeweils anhand des KGS eine neue Instanz der Klasse Bundesland
            $(this.id).children().each(function(index, bundesland){

                // Extrahiere den KGS aus der ID des Elements
                var kgs = $(bundesland).attr('id').substr($(bundesland).attr('id').lastIndexOf('__') + 2, 8);

                // Erstelle Instanz der Klasse Bundesland und uebergebe den KGS und die JSON-Daten mit den Altkennzeichen-Informationen
                bundeslaender.push(new Bundesland(kgs, data));

            });

            // Gebe Objekt mit allen Kindelement-Objekten zurueck
            return $.extend({}, bundeslaender);

        },


	    /**
         * @function zoomToBundesland
         * @param {string} kgs
         */
        zoomToBundesland: function(kgs) {

           var found = false;

           $.each(deutschland.bundeslaender, function()
           {
              if(this.kgs == kgs){
                  found = this;
              }
           });

           var zoomer =  setTimeout( found.click(found) ,500 );

           return found;

        },

        /**
         * @function powertip
         * @param {int} manual
         */
        powertip: function (manual) {

            // Pruefe ob Tooltips deaktiviert werden sollen
            if (manual == 'off') {

                // Entferne alle Tooltips der Kindelemente
                $(this.id).children().powerTip('destroy');

            }
            else {

                // Haenge an alle Kindelemente des Landes einen Tooltip
                $(this.id).children().powerTip({

                    // http://stevenbenner.github.io/jquery-powertip/
                    placement: 's',
                    smartPlacement: true,
                    manual: ((manual == 'on') ? false : true )

                });
            }

        },

        /**
         * @function reset
         */
         reset: function () {
            
            //@TODO: Besser loesen -> Status im child direkt hinterlegen und ueber listener aendern
            //@TODO: button-zurueck -> integrienen in click-function!
            
            crt_model = '';

            // Setze zoom-Klasse fuer das Elternelement
            $(deutschland.id)[0].setAttribute('class', 'karte');

            // Setze zoom-Klasse fuer das Element
            $(deutschland.zoom)[0].setAttribute('class', 'bundesland');

            // Blende ALLE Altkennzeichen aus
            $('.text--altkennzeichen').css('display', 'none');

            // Setze den Zoom-Status des Elements wieder zurueck
            deutschland.zoom = 0;

            // Fuege wieder Tooltip an Bundeslaender an
            deutschland.powertip('on');

         },

        /**
         * @function initialize
         * @return object model
         */
        initialize: function () {

            // Rufe die Funktion bundeslaender auf, welche alle Kindelemente des Landes zurueckgibt
            this.bundeslaender = this.bundeslaender(data);

            // Rufe die Funktione powertip auf, welche an jedes Kindelement einen Tooltip haengt
            this.powertip('on');

            // Gebe model zurueck
            return this;
        }
    };
    
    // Gebe model zurueck
    return this.model.initialize();
};
