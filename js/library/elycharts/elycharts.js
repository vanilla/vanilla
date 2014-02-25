/********* Source File: src/elycharts_defaults.js*********/
/*!*********************************************************************
 * ELYCHARTS v2.1.4-SNAPSHOT $Id: elycharts.js 52 2011-08-07 19:57:09Z stefano.bagnara@gmail.com $
 * A Javascript library to generate interactive charts with vectorial graphics.
 *
 * Copyright (c) 2010 Void Labs s.n.c. (http://void.it)
 * Licensed under the MIT (http://creativecommons.org/licenses/MIT/) license.
 **********************************************************************/

(function($) {
if (!$.elycharts)
  $.elycharts = {};

/***********************************************************************
 * DEFAULT OPTIONS
 **********************************************************************/

$.elycharts.templates = {

  common : {
    // Tipo di grafico
    // type : 'line|pie|funnel|barline'

    // Permette di specificare una configurazione di default da utilizzare (definita in $.elycharts.templates.NOME)
    // La configurazione completa ï¿½ quindi data da tutti i valori della conf di default alla quale viene unita (con sovrascrittura) la conf corrente
    // Il parametro ï¿½ ricorsivo (la configurazione di default puo' a sua volta avere una configurazione di default)
    // Se non specificato, la configurazione di default ï¿½ quella con lo stesso nome del tipo di grafico
    // template : 'NOME',

    /* DATI:
    // I valori associati a ogni serie del grafico. Ogni serie ï¿½ associata a una chiave dell'oggetto value, il cui
    // valore ï¿½ l'array di dati relativi
    values : {},

    // Label associate ai valori del grafico
    // Solo in caso di label gestite da labelmanager (quindi per pie e funnel) e per label.html = true e' possibile inserire
    // degli elementi DOM/JQUERY che verranno presi e posizionati correttament.
    labels : [],

    // Anchor per la gestione mediante anchormanager. Possono essere stringhe e oggetti DOM/JQUERY che verranno riposizionati
    anchors : {},

    tooltips : {},

    legend : [],
    */

    // Per impostare una dimensione diversa da quella del container settare width e height
    //width : x,
    //height : y

    // I margini del grafico rispetto al frame complessivo. Da notare che riguardano la posizione del grafico
    // principale, e NON degli elementi aggiuntivi (legenda, label e titoli degli assi...). Quindi i margini devono
    // essere impostati in genere proprio per lasciare lo spazio per questi elementi
    // Sintassi: [top, right, bottom, left]
    margins: [10, 10, 10, 10],

    // style : {},

    // Per gestire al meglio l'interattivita' del grafico (tooltip, highlight, anchor...) viene inserito un secondo
    // layer per le parti sensibili al mouse. Se si sa che il grafico non avra' alcuna interattivita' si puo' impostare
    // questo valore a false per evitare di creare il layer (ottimizzando leggermente la pagina)
    interactive : true,

    // Dati da applicare a tutte le serie del grafico
    defaultSeries : {
      // Impostare a false per disabilitare la visualizzazione della serie
      visible : true,

      // Impostare color qui permette di impostare velocemente plotProps.stroke+fill, tooltip.frameProps.stroke, dotProps.stroke e fillProps.fill (se non specificati)
      //color: 'blue',

      //plotProps : { },

      // Impostazioni dei tooltip
      tooltip : {
        active : true,
        // Se width ed height vengono impostati a 0 o ad "auto" (equivalenti) non vengono fissate dimensioni, quindi il contenuto si autodimensiona in funzione del tooltip
        // Impostare a 0|auto ï¿½ incompatibile con il frame SVG, quindi viene automaticamente disabilitato (come se frameProps = false)
        width: 100, height: 50,
        roundedCorners: 5,
        padding: [6, 6] /* y, x */,
        offset: [20, 0] /* y, x */,
        // Se frameProps = false non disegna la cornice del tooltip (ad es. per permettere di definire la propria cornice HTML)
        frameProps : { fill: "white", "stroke-width": 2 },
        contentStyle : { "font-family": "Arial", "font-size": "12px", "line-height": "16px", color: "black" }
      },

      // Highlight feature
      highlight : {
        // Cambia le dimensioni dell'elemento quando deve essere evidenziato
        //scale : [x, y],
        // Opzioni di animazione effetto "scale"
        scaleSpeed : 100, scaleEasing : '',
        // Cambia gli attributi dell'elemento quando evidenziato
        //newProps : { opacity : 1 },
        // Inserisce un layer con gli attributi specificati sopra quello da evidenziare
        //overlayProps : {"fill" : "white", "fill-opacity" : .3, "stroke-width" : 0}
        // Muove l'area evidenziata. E' possibile specificare un valore X o un array [X, Y]
        //move : 10,
        // Opzioni di animazione effetto "move"
        moveSpeed : 100, moveEasing : '',
        // Opzioni di animazione da usare per riportare l'oggetto alle situazione iniziale
        restoreSpeed : 0, restoreEasing : ''
      },

      anchor : {
        // Aggiunge alle anchor esterne la classe selezionata quando il mouse passa sull'area
        //addClass : "",
        // Evidenzia la serie al passaggio del mouse
        //highlight : "",
        // Se impostato a true usa gli eventi mouseenter/mouseleave invece di mouseover/mouseout per l'highlight
        //useMouseEnter : false,
      },

      // Opzioni per la generazione animata dei grafici
      startAnimation : {
        //active : true,
        type : 'simple',
        speed : 600,
        delay : 0,
        propsFrom : {}, // applicate a tutte le props di plot
        propsTo : {}, // applicate a tutte le props di plot
        easing : '' // easing raphael: >, <, <>, backIn, backOut, bounce, elastic

        // Opzionale per alcune animazioni, permette di specificare un sotto-tipo
        // subType : 0|1|2
      },

      // Opzioni per le transizioni dei grafici durante un cambiamento di configurazione
      /* stepAnimation : {
        speed : 600,
        delay : 0,
        easing : '' // easing raphael: >, <, <>, backIn, backOut, bounce, elastic
      },*/

      label : {
        // Disegna o meno la label interna al grafico
        active : false,
        // Imposta un offset [X,Y] per la label (le coordinate sono relative al sistema di assi dello specifico settore disegnato.
        // Ad es. per il piechart la X ï¿½ la distanza dal centro, la Y lo spostamento ortogonale
        //offset : [x, y],
        html : false,
        // Proprieta' della label (per HTML = false)
        props : { fill: 'black', stroke: "none", "font-family": 'Arial', "font-size": "16px" },
        // Stile CSS della label (per HTML = true)
        style : { cursor : 'default' }
        // Posizionamento della label rispetto al punto centrale (+offset) identificato
        //frameAnchor : ['start|middle|end', 'top|middle|bottom']
      }

      /*legend : {
        dotType : 'rect',
        dotWidth : 10, dotHeight : 10, dotR : 4,
        dotProps : { },
        textProps : { font: '12px Arial', fill: "#000" }
      }*/
    },

    series : {
      // Serie specifica usata quando ci sono "dati vuoti" (ad esempio quando un piechart e' a 0)
      empty : {
        //plotProps : { fill : "#D0D0D0" },
        label : { active : false },
        tooltip : { active : false }
      }
      /*root : {
        values : []
      }*/
    },

    features : {
      tooltip : {
        // Imposta una posizione fissa per tutti i tooltip
        //fixedPos : [ x,  y]
        // Velocita' del fade
        fadeDelay : 100,
        // Velocita' dello spostamento del tip da un'area all'altra
        moveDelay : 300
        // E' possibile specificare una funzione che filtra le coordinate del tooltip prima di mostrarlo, permettendo di modificarle
        // Nota: le coordinate del mouse sono in mouseAreaData.event.pageX/pageY, e nel caso va ritornato [mouseAreaData.event.pageX, mouseAreaData.event.pageY, true] per indicare che il sistema e' relativo alla pagina)
        //positionHandler : function(env, tooltipConf, mouseAreaData, suggestedX, suggestedY) { return [suggestedX, suggestedY] }
      },
      mousearea : {
        // 'single' le aree sensibili sono relative a ogni valore di ogni serie, se 'index' il mouse attiva tutte le serie per un indice
        type : 'single',
        // In caso di type = 'index', indica se le aree si basano sulle barre ('bar') o sui punti di una linea ('line'). Specificare 'auto' per scegliere automaticamente
        indexCenter : 'auto',
        // Quanto tempo puo' passare nel passaggio da un'area all'altra per considerarlo uno spostamento di puntatore
        areaMoveDelay : 500,
        // Se diversi chart specificano lo stesso syncTag quando si attiva l'area di uno si disattivano quelle degli altri
        syncTag: false,
        // Callback for mouse actions. Parameters passed: (env, serie, index, mouseAreaData)
        onMouseEnter : false,
        onMouseExit : false,
        onMouseChanged : false,
        onMouseOver : false,
        onMouseOut : false
      },
      highlight : {
        // Evidenzia tutto l'indice con una barra ("bar"), una linea ("line") o una linea centrata sulle barre ("barline"). Se "auto" decide in autonomia tra bar e line
        //indexHighlight : 'barline',
        indexHighlightProps : { opacity : 1 /*fill : 'yellow', opacity : .3, scale : ".5 1"*/ }
      },
      animation : {
        // Valore di default per la generazione animata degli elementi del grafico (anche per le non-serie: label, grid...)
        startAnimation : {
          //active : true,
          //propsFrom : {}, // applicate a tutte le props di plot
          //propsTo : {}, // applicate a tutte le props di plot
          speed : 600,
          delay : 0,
          easing : '' // easing raphael: >, <, <>, backIn, backOut, bounce, elastic
        },
        // Valore di default per la transizione animata degli elementi del grafico (anche per le non-serie: label, grid...)
        stepAnimation : {
          speed : 600,
          delay : 0,
          easing : '' // easing raphael: >, <, <>, backIn, backOut, bounce, elastic
        }
      },
      frameAnimation : {
        active : false,
        cssFrom : { opacity : 0},
        cssTo : { opacity: 1 },
        speed : 'slow',
        easing : 'linear' // easing jQuery: 'linear' o 'swing'
      },
      pixelWorkAround : {
        active : true
      },
      label : {},
      shadows : {
        active : false,
        offset : [2, 2], // Per attivare l'ombra, [y, x]
        props : {"stroke-width": 0, "stroke-opacity": 0, "fill": "black", "fill-opacity": .3}
      },
      // BALLOONS: Applicabile solo al funnel (per ora)
      balloons : {
        active : false,
        // Width: se non specificato e' automatico
        //width : 200,
        // Height: se non specificato e' automatico
        //height : 50,
        // Lo stile CSS da applicare a ogni balloon
        style : {  },
        // Padding
        padding : [ 5, 5 ],
        // La distanza dal bordo sinistro
        left : 10,
        // Percorso della linea: [ [ x, y iniziali (rispetto al punto di inizio standard)], ... [x, y intermedi (rispetto al punto di inizio standard)] ..., [x, y finale (rispetto all'angolo del balloon piï¿½ vicino al punto di inizio)] ]
        line : [ [ 0, 0 ], [0, 0] ],
        // Proprietï¿½ della linea
        lineProps : { }
      },
      legend : {
        horizontal : false,
        x : 'auto', // X | auto, (auto solo per horizontal = true)
        y : 10,
        width : 'auto', // X | auto, (auto solo per horizontal = true)
        height : 20,
        itemWidth : "fixed", // fixed | auto, solo per horizontal = true
        margins : [0, 0, 0, 0],
        dotMargins : [10, 5], // sx, dx
        borderProps : { fill : "white", stroke : "black", "stroke-width" : 1 },
        dotType : 'rect',
        dotWidth : 10, dotHeight : 10, dotR : 4,
        dotProps : { type : "rect", width : 10, height : 10 },
        textProps : { font: '12px Arial', fill: "#000" }
      },
      debug : {
        active : false
      }
    },

    nop : 0
  },

  line : {
    template : 'common',

    barMargins : 0,

    // Axis
    defaultAxis : {
      // [non per asse x] Normalizza il valore massimo dell'asse in modo che tutte le label abbiamo al massimo N cifre significative
      // (Es: se il max e' 135 e normalize = 2 verra' impostato il max a 140, ma se il numero di label in y e' 3 verrï¿½ impostato 150)
      normalize: 2,
      // Permette di impostare i valori minimi e massimi di asse (invece di autorilevarli)
      min: 0, //max: x,
      // Imposta un testo da usare come prefisso e suffisso delle label
      //prefix : "", suffix : "",
      // Visualizza o meno le label dell'asse
      labels: false,
      // Distanza tra le label e l'asse relativo
      labelsDistance: 8,
      // [solo asse x] Rotazione (in gradi) delle label. Se specificato ignora i valori di labelsAnchor e labelsProps['text-anchor']
      labelsRotate: 0,
      // Proprieta' grafiche delle label
      labelsProps : {font: '10px Arial', fill: "#000"},
      // Compatta il numero mostrato nella label usando i suffissi specificati per migliaia, milioni...
      //labelsCompactUnits : ['k', 'M'],
      // Permette di specificare una funzione esterna che si occupa di formattare (o in generale trasformare) la label
      //labelsFormatHandler : function (label,i) { return label },
      // Salta le prime N label
      //labelsSkip : 0,
      // Force alignment for the label. Auto will automatically center it for x axis (also considering labelsRotate), "end" for l axis, "start" for the right axis.
      //labelsAnchor : "auto"
      // [solo asse x] Force an alternative position for the X axis labels. Auto will automatically choose the right position depending on "labelsCenter", the type of charts (bars vs lines), and labelsRotate.
      //labelsPos : "auto",
      // Automatically hide labels that would overlap previous labels.
      //labelsHideCovered : true,
      // Inserisce un margine alla label (a sinistra se in asse x, in alto se in altri assi)
      //labelsMargin: 10,
      // [solo asse x] If labelsHideCovered = true, make sure each label have at least this space before the next one.
      //labelsMarginRight: 0,
      // Distanza del titolo dall'asse
      titleDistance : 25, titleDistanceIE : .75,
      // Proprieta' grafiche del titolo
      titleProps : {font: '12px Arial', fill: "#000", "font-weight": "bold"}
    },
    axis : {
      x : { titleDistanceIE : 1.2 }
    },

    defaultSeries : {
      // Tipo di serie, puo' essere 'line' o 'bar'
      type : 'line',
      // L'asse di riferimento della serie. Gli assi "l" ed "r" sono i 2 assi visibili destro e sinistro.
      // E' possibile inserire anche un asse arbitrario (che non sarï¿½ visibile)
      axis : 'l',
      // Specificare cumulative = true se i valori inseriti per la serie sono cumulativi
      cumulative : false,
      // In caso di type="line" indica l'arrotondamento della linea
      rounded : 1,
      // Mette il punto di intersezione al centro dell'intervallo invece che al limite (per allineamento con bars). Se 'auto' decide autonomamente
      lineCenter : 'auto',
      // Permette di impilare le serie (i valori di uno iniziano dove finiscono quelli del precedente) con un altra (purche' dello stesso tipo)
      // Specificare "true" per impilare con la serie visibile precedente, oppure il nome della serie sulla quale impilare
      // stacked : false,

      plotProps : {"stroke-width": 1, "stroke-linejoin": "round"},

      barWidthPerc: 100,
      //DELETED: barProps : {"width-perc" : 100, "stroke-width": 1, "fill-opacity" : .3},

      // Attiva o disattiva il riempimento
      fill : false,
      fillProps : {stroke: "none", "stroke-width" : 0, "stroke-opacity": 0, opacity: .3},

      dot : false,
      dotProps : {size: 4, stroke: "#000", zindex: 5},
      dotShowOnNull : false,

      mouseareaShowOnNull : false,

      startAnimation : {
        plotPropsFrom : false,
        // DELETED linePropsFrom : false,
        fillPropsFrom : false,
        dotPropsFrom : false,
        //DELETED barPropsFrom : false,
        shadowPropsFrom : false
      }

    },

    features : {
      grid : {
        // N. di divisioni sull'asse X. Se "auto" si basa sulla label da visualizzare. Se "0" imposta draw[vertical] = false
        // Da notare che se "auto" allora la prima e l'ultima linea (bordi) le fa vedere sempre (se ci sono le label). Se invece e' un numero si comporta come ny: fa vedere i bordi solo se forzato con forceBorder
        nx : "auto",
        // N. di divisione sull'asse Y. Se "0" imposta draw[horizontal] = false
        ny : 4,
        // Disegna o meno la griglia. Si puo' specificare un array [horizontal, vertical]
        draw : false,
        // Forza la visualizzazione dei bordi/assi. Se true disegna comunque i bordi (anche se draw = false o se non ci sono label),
        // altrimenti si basa sulle regole standard di draw e presenza label (per asse x)
        // Puo' essere un booleano singolo o un array di bordi [up, dx, down, sx]
        forceBorder : false,
        // Proprieta' di visualizzazione griglia
        props : {stroke: '#e0e0e0', "stroke-width": 1},
        // Dimensioni extra delle rette [up, dx, down, sx]
        extra : [0, 0, 0, 0],
        // Indica se le label (e le rispettive linee del grid) vanno centrate sulle barre (true), quindi tra 2 linee, o sui punti della serie (false), quindi su una sola linea
        // Se specificato "auto" decide in autonomia
        labelsCenter : "auto",

        // Display a rectangular region with properties specied for every even/odd vertical/horizontal grid division
        evenVProps : false,
        oddVProps : false,
        evenHProps : false,
        oddHProps : false,

        ticks : {
          // Attiva le barrette sugli assi [x, l, r]
          active : [false, false, false],
          // Dimensioni da prima dell'asse a dopo l'asse
          size : [10, 10],
          // Proprieta' di visualizzazione griglia
          props : {stroke: '#e0e0e0', "stroke-width": 1}
        }
      }
    },

    nop : 0
  },

  pie : {
    template : 'common',

    // Coordinate del centro, se non specificate vengono autodeterminate
    //cx : 0, cy : 0,
    // Raggio della torta, se non specificato viene autodeterminato
    //r : 0
    // Angolo dal quale iniziare a disegnare le fette, in gradi
    startAngle : 0,
    // Disegna la torta con le fette in senso orario (invece dell'orientamento standard per gradi, in senso antiorario)
    clockwise : false,
    // Soglia (rapporto sul totale) entro la quale una fetta non viene visualizzata
    valueThresold : 0.006,

    defaultSeries : {
      // r: .5, raggio usato solo per questo spicchio, se <=1 e' in rapporto al raggio generale
      // inside: X, inserisce questo spicchio dentro un altro (funziona solo inside: precedente, e non gestisce + spicchi dentro l'altro)
    }
  },

  funnel : {
    template : 'common',

    rh: 0, // height of ellipsis (for top and bottom cuts)
    method: 'width', // width/cutarea
    topSector: 0, // height factor of top cylinder
    topSectorProps : { fill: "#d0d0d0" },
    bottomSector: .1, // height factor of bottom cylinder
    bottomSectorProps : { fill: "#d0d0d0" },
    edgeProps : { fill: "#c0c0c0", "stroke-width": 1, opacity: 1 },

    nop : 0
  },

  barline : {
    template : 'common',

    // Imposta il valore massimo per la scala (altrimenti prende il valore + alto)
    // max : X

    // Impostare direction = rtl per creare un grafico che va da destra a sinistra
    direction : 'ltr'
  }
}

})(jQuery);
/********* Source File: src/elycharts_core.js*********/
/**********************************************************************
 * ELYCHARTS
 * A Javascript library to generate interactive charts with vectorial graphics.
 *
 * Copyright (c) 2010 Void Labs s.n.c. (http://void.it)
 * Licensed under the MIT (http://creativecommons.org/licenses/MIT/) license.
 **********************************************************************/

(function($) {
if (!$.elycharts)
  $.elycharts = {};

$.elycharts.lastId = 0;

/***********************************************************************
 * INITIALIZATION / MAIN CALL
 **********************************************************************/

$.fn.chart = function($options) {
  if (!this.length)
    return this;

  var $env = this.data('elycharts_env');

  if (typeof $options == "string") {
    if ($options.toLowerCase() == "config")
      return $env ? $env.opt : false;
    if ($options.toLowerCase() == "clear") {
      if ($env) {
        // TODO Bisogna chiamare il destroy delle feature?
        $env.paper.clear();
        this.html("");
        this.data('elycharts_env', false);
      }
    }
  }
  else if (!$env) {
    // First call, initialization

    if ($options)
      $options = _extendAndNormalizeOptions($options);

    if (!$options || !$options.type || !$.elycharts.templates[$options.type]) {
      alert('ElyCharts ERROR: chart type is not specified');
      return false;
    }
    $env = _initEnv(this, $options);

    _processGenericConfig($env, $options);
    $env.pieces = $.elycharts[$env.opt.type].draw($env);

    this.data('elycharts_env', $env);

  } else {
    $options = _normalizeOptions($options, $env.opt);

    // Already initialized
    $env.oldopt = common._clone($env.opt);
    $env.opt = $.extend(true, $env.opt, $options);
    $env.newopt = $options;

    _processGenericConfig($env, $options);
    $env.pieces = $.elycharts[$env.opt.type].draw($env);
  }

  return this;
}

/**
 * Must be called only in first call to .chart, to initialize elycharts environment.
 */
function _initEnv($container, $options) {
  if (!$options.width)
    $options.width = $container.width();
  if (!$options.height)
    $options.height = $container.height();

  var $env = {
    id : $.elycharts.lastId ++,
    paper : common._RaphaelInstance($container.get()[0], $options.width, $options.height),
    container : $container,
    plots : [],
    opt : $options
  };

  // Rendering a transparent pixel up-left. Thay way SVG area is well-covered (else the position starts at first real object, and that mess-ups everything)
  $env.paper.rect(0,0,1,1).attr({opacity: 0});

  $.elycharts[$options.type].init($env);

  return $env;
}

function _processGenericConfig($env, $options) {
  if ($options.style)
    $env.container.css($options.style);
}

/**
 * Must be called in first call to .chart, to build the full config structure and normalize it.
 */
function _extendAndNormalizeOptions($options) {
  var k;
  // Compatibility with old $.elysia_charts.default_options and $.elysia_charts.templates
  if ($.elysia_charts) {
    if ($.elysia_charts.default_options)
      for (k in $.elysia_charts.default_options)
        $.elycharts.templates[k] = $.elysia_charts.default_options[k];
    if ($.elysia_charts.templates)
      for (k in $.elysia_charts.templates)
        $.elycharts.templates[k] = $.elysia_charts.templates[k];
  }

  // TODO Optimize extend cicle
  while ($options.template) {
    var d = $options.template;
    delete $options.template;
    $options = $.extend(true, {}, $.elycharts.templates[d], $options);
  }
  if (!$options.template && $options.type) {
    $options.template = $options.type;
    while ($options.template) {
      d = $options.template;
      delete $options.template;
      $options = $.extend(true, {}, $.elycharts.templates[d], $options);
    }
  }

  return _normalizeOptions($options, $options);
}

/**
 * Normalize options passed (primarly for backward compatibility)
 */
function _normalizeOptions($options, $fullopt) {
  if ($options.type == 'pie' || $options.type == 'funnel') {
    if ($options.values && $.isArray($options.values) && !$.isArray($options.values[0]))
      $options.values = { root : $options.values };
    if ($options.tooltips && $.isArray($options.tooltips) && !$.isArray($options.tooltips[0]))
      $options.tooltips = { root : $options.tooltips };
    if ($options.anchors && $.isArray($options.anchors) && !$.isArray($options.anchors[0]))
      $options.anchors = { root : $options.anchors };
    if ($options.balloons && $.isArray($options.balloons) && !$.isArray($options.balloons[0]))
      $options.balloons = { root : $options.balloons };
    if ($options.legend && $.isArray($options.legend) && !$.isArray($options.legend[0]))
      $options.legend = { root : $options.legend };
  }

  if ($options.defaultSeries) {
    var deftype = $fullopt.type != 'line' ? $fullopt.type : ($options.defaultSeries.type ? $options.defaultSeries.type : ($fullopt.defaultSeries.type ? $fullopt.defaultSeries.type : 'line'));
    _normalizeOptionsColor($options.defaultSeries, deftype, $fullopt);
    if ($options.defaultSeries.stackedWith) {
      $options.defaultSeries.stacked = $options.defaultSeries.stackedWith;
      delete $options.defaultSeries.stackedWith;
    }
  }

  if ($options.series)
    for (var serie in $options.series) {
      var type = $fullopt.type != 'line' ? $fullopt.type : ($options.series[serie].type ? $options.series[serie].type : ($fullopt.series[serie].type ? $fullopt.series[serie].type : (deftype ? deftype : 'line')));
      _normalizeOptionsColor($options.series[serie], type, $fullopt);
      if ($options.series[serie].values)
        for (var value in $options.series[serie].values)
          _normalizeOptionsColor($options.series[serie].values[value], type, $fullopt);

      if ($options.series[serie].stackedWith) {
        $options.series[serie].stacked = $options.series[serie].stackedWith;
        delete $options.series[serie].stackedWith;
      }
    }

  if ($options.type == 'line') {
    if (!$options.features)
      $options.features = {};
    if (!$options.features.grid)
      $options.features.grid = {};

    if (typeof $options.gridNX != 'undefined') {
      $options.features.grid.nx = $options.gridNX;
      delete $options.gridNX;
    }
    if (typeof $options.gridNY != 'undefined') {
      $options.features.grid.ny = $options.gridNY;
      delete $options.gridNY;
    }
    if (typeof $options.gridProps != 'undefined') {
      $options.features.grid.props = $options.gridProps;
      delete $options.gridProps;
    }
    if (typeof $options.gridExtra != 'undefined') {
      $options.features.grid.extra = $options.gridExtra;
      delete $options.gridExtra;
    }
    if (typeof $options.gridForceBorder != 'undefined') {
      $options.features.grid.forceBorder = $options.gridForceBorder;
      delete $options.gridForceBorder;
    }

    if ($options.defaultAxis && $options.defaultAxis.normalize && ($options.defaultAxis.normalize == 'auto' || $options.defaultAxis.normalize == 'autony'))
      $options.defaultAxis.normalize = 2;

    if ($options.axis)
      for (var axis in $options.axis)
        if ($options.axis[axis] && $options.axis[axis].normalize && ($options.axis[axis].normalize == 'auto' || $options.axis[axis].normalize == 'autony'))
          $options.axis[axis].normalize = 2;
  }

  return $options;
}

/**
* Manage "color" attribute.
* @param $section Section part of external conf passed
* @param $type Type of plot (for line chart can be "line" or "bar", for other types is equal to chart type)
*/
function _normalizeOptionsColor($section, $type, $fullopt) {
  if ($section.color) {
    var color = $section.color;

    if (!$section.plotProps)
      $section.plotProps = {};

    if ($type == 'line') {
      if ($section.plotProps && !$section.plotProps.stroke && !$fullopt.defaultSeries.plotProps.stroke)
        $section.plotProps.stroke = color;
    } else {
      if ($section.plotProps && !$section.plotProps.fill && !$fullopt.defaultSeries.plotProps.fill)
        $section.plotProps.fill = color;
    }

    if (!$section.tooltip)
      $section.tooltip = {};
    // Is disabled in defaultSetting i should not set color
    if (!$section.tooltip.frameProps && $fullopt.defaultSeries.tooltip.frameProps)
      $section.tooltip.frameProps = {};
    if ($section.tooltip && $section.tooltip.frameProps && !$section.tooltip.frameProps.stroke && !$fullopt.defaultSeries.tooltip.frameProps.stroke)
      $section.tooltip.frameProps.stroke = color;

    if (!$section.legend)
      $section.legend = {};
    if (!$section.legend.dotProps)
      $section.legend.dotProps = {};
    if ($section.legend.dotProps && !$section.legend.dotProps.fill)
      $section.legend.dotProps.fill = color;

    if ($type == 'line') {
      if (!$section.dotProps)
        $section.dotProps = {};
      if ($section.dotProps && !$section.dotProps.fill && !$fullopt.defaultSeries.dotProps.fill)
        $section.dotProps.fill = color;

      if (!$section.fillProps)
        $section.fillProps = {};
      if ($section.fillProps && !$section.fillProps.fill && !$fullopt.defaultSeries.fillProps.fill)
        $section.fillProps.fill = color;
    }
  }
}

/***********************************************************************
 * COMMON
 **********************************************************************/

$.elycharts.common = {
  _RaphaelInstance : function(c, w, h) {
    var r = Raphael(c, w, h);

    r.customAttributes.slice = function (cx, cy, r, rint, aa1, aa2) {
      // Method body is for clockwise angles, but parameters passed are ccw
      a1 = 360 - aa2; a2 = 360 - aa1;
      //a1 = aa1; a2 = aa2;
      var flag = (a2 - a1) > 180;
      a1 = (a1 % 360) * Math.PI / 180;
      a2 = (a2 % 360) * Math.PI / 180;
      // a1 == a2  (but they where different before) means that there is a complete round (eg: 0-360). This should be shown
      if (a1 == a2 && aa1 != aa2)
        a2 += 359.99 * Math.PI / 180;

      return { path : rint ? [
        ["M", cx + r * Math.cos(a1), cy + r * Math.sin(a1)],
        ["A", r, r, 0, +flag, 1, cx + r * Math.cos(a2), cy + r * Math.sin(a2)],
        ["L", cx + rint * Math.cos(a2), cy + rint * Math.sin(a2)],
        //["L", cx + rint * Math.cos(a1), cy + rint * Math.sin(a1)],
        ["A", rint, rint, 0, +flag, 0, cx + rint * Math.cos(a1), cy + rint * Math.sin(a1)],
        ["z"]
      ] : [
        ["M", cx, cy],
        ["l", r * Math.cos(a1), r * Math.sin(a1)],
        ["A", r, r, 0, +flag, 1, cx + r * Math.cos(a2), cy + r * Math.sin(a2)],
        ["z"]
      ] };
    };

    return r;
  },

  _clone : function(obj){
    if(obj == null || typeof(obj) != 'object')
      return obj;
    if (obj.constructor == Array)
      return [].concat(obj);
    var temp = new obj.constructor(); // changed (twice)
    for(var key in obj)
      temp[key] = this._clone(obj[key]);
    return temp;
  },

  _mergeObjects : function(o1, o2) {
    return $.extend(true, o1, o2);
    /*
    if (typeof o1 == 'undefined')
      return o2;
    if (typeof o2 == 'undefined')
      return o1;

    for (var idx in o2)
      if (typeof o1[idx] == 'undefined')
        o1[idx] = this._clone(o2[idx]);
      else if (typeof o2[idx] == 'object') {
        if (typeof o1[idx] == 'object')
          o1[idx] = this._mergeObjects(o1[idx], o2[idx]);
        else
          o1[idx] = this._mergeObjects({}, o2[idx]);
      }
      else
        o1[idx] = this._clone(o2[idx]);
    return o1;*/
  },

  compactUnits : function(val, units) {
    for (var i = units.length - 1; i >= 0; i--) {
      var v = val / Math.pow(1000, i + 1);
      //console.warn(i, units[i], v, v * 10 % 10);
      if (v >= 1 && v * 10 % 10 == 0)
        return v + units[i];
    }
    return val;
  },

  getElementOriginalAttrs : function(element) {
    var attr = $(element.node).data('original-attr');
    if (!attr) {
      attr = element.attr();
      $(element.node).data('original-attr', attr);
    }
    return attr;
  },

  findInPieces : function(pieces, section, serie, index, subsection) {
    for (var i = 0; i < pieces.length; i++) {
      if (
        (typeof section == undefined || section == -1 || section == false || pieces[i].section == section) &&
        (typeof serie == undefined || serie == -1 || serie == false || pieces[i].serie == serie) &&
        (typeof index == undefined || index == -1 || index == false || pieces[i].index == index) &&
        (typeof subsection == undefined || subsection == -1 || subsection == false || pieces[i].subSection == subsection)
      )
        return pieces[i];
    }
    return false;
  },

  samePiecePath : function(piece1, piece2) {
    return (((typeof piece1.section == undefined || piece1.section == -1 || piece1.section == false) && (typeof piece2.section == undefined || piece2.section == -1 || piece2.section == false)) || piece1.section == piece2.section) &&
      (((typeof piece1.serie == undefined || piece1.serie == -1 || piece1.serie == false) && (typeof piece2.serie == undefined || piece2.serie == -1 || piece2.serie == false)) || piece1.serie == piece2.serie) &&
      (((typeof piece1.index == undefined || piece1.index == -1 || piece1.index == false) && (typeof piece2.index == undefined || piece2.index == -1 || piece2.index == false)) || piece1.index == piece2.index) &&
      (((typeof piece1.subSection == undefined || piece1.subSection == -1 || piece1.subSection == false) && (typeof piece2.subSection == undefined || piece2.subSection == -1 || piece2.subSection == false)) || piece1.subSection == piece2.subSection);
  },

  executeIfChanged : function(env, changes) {
    if (!env.newopt)
      return true;

    for (var i = 0; i < changes.length; i++) {
      if (changes[i][changes[i].length - 1] == "*") {
        for (var j in env.newopt)
          if (j.substring(0, changes[i].length - 1) + "*" == changes[i])
            return true;
      }
      else if (changes[i] == 'series' && (env.newopt.series || env.newopt.defaultSeries))
        return true;
      else if (changes[i] == 'axis' && (env.newopt.axis || env.newopt.defaultAxis))
        return true;
      else if (changes[i].substring(0, 9) == "features.") {
        changes[i] = changes[i].substring(9);
        if (env.newopt.features && env.newopt.features[changes[i]])
          return true;
      }
      else if (typeof env.newopt[changes[i]] != 'undefined')
        return true;
    }
    return false;
  },

  /**
   * Ottiene le proprietÃ  di una "Area" definita nella configurazione (options),
   * identificata da section / serie / index / subsection, e facendo il merge
   * di tutti i defaults innestati.
   */
  areaProps : function(env, section, serie, index, subsection) {
    var props;

    // TODO fare una cache e fix del toLowerCase (devono solo fare la prima lettera
    if (!subsection) {
      if (typeof serie == 'undefined' || !serie)
        props = env.opt[section.toLowerCase()];

      else {
        props = this._clone(env.opt['default' + section]);
        if (env.opt[section .toLowerCase()] && env.opt[section.toLowerCase()][serie])
          props = this._mergeObjects(props, env.opt[section.toLowerCase()][serie]);

        if ((typeof index != 'undefined') && index >= 0 && props['values'] && props['values'][index])
          props = this._mergeObjects(props, props['values'][index]);
      }

    } else {
      props = this._clone(env.opt[subsection.toLowerCase()]);

      if (typeof serie == 'undefined' || !serie) {
        if (env.opt[section.toLowerCase()] && env.opt[section.toLowerCase()][subsection.toLowerCase()])
          props = this._mergeObjects(props, env.opt[section.toLowerCase()][subsection.toLowerCase()]);

      } else {
        if (env.opt['default' + section] && env.opt['default' + section][subsection.toLowerCase()])
          props = this._mergeObjects(props, env.opt['default' + section][subsection.toLowerCase()]);

        if (env.opt[section .toLowerCase()] && env.opt[section.toLowerCase()][serie] && env.opt[section.toLowerCase()][serie][subsection.toLowerCase()])
          props = this._mergeObjects(props, env.opt[section.toLowerCase()][serie][subsection.toLowerCase()]);

        if (props && (typeof index != 'undefined') && index > 0 && props['values'] && props['values'][index])
          props = this._mergeObjects(props, props['values'][index]);
      }
    }

    return props;
  },

  absrectpath : function(x1, y1, x2, y2, r) {
    // TODO Supportare r
    return [['M', x1, y1], ['L', x1, y2], ['L', x2, y2], ['L', x2, y1], ['z']];
  },

  linepathAnchors : function(p1x, p1y, p2x, p2y, p3x, p3y, rounded) {
    var method = 1;
    if (rounded && rounded.length) {
      method = rounded[1];
      rounded = rounded[0];
    }
    if (!rounded)
      rounded = 1;
    var l1 = (p2x - p1x) / 2,
        l2 = (p3x - p2x) / 2,
        a = Math.atan((p2x - p1x) / Math.abs(p2y - p1y)),
        b = Math.atan((p3x - p2x) / Math.abs(p2y - p3y));
    a = p1y < p2y ? Math.PI - a : a;
    b = p3y < p2y ? Math.PI - b : b;
    if (method == 2) {
      // If added by Bago to avoid curves beyond min or max
      if ((a - Math.PI / 2) * (b - Math.PI / 2) > 0) {
        a = 0;
        b = 0;
      } else {
        if (Math.abs(a - Math.PI / 2) < Math.abs(b - Math.PI / 2))
          b = Math.PI - a;
        else
          a = Math.PI - b;
      }
    }

    var alpha = Math.PI / 2 - ((a + b) % (Math.PI * 2)) / 2,
        dx1 = l1 * Math.sin(alpha + a) / 2 / rounded,
        dy1 = l1 * Math.cos(alpha + a) / 2 / rounded,
        dx2 = l2 * Math.sin(alpha + b) / 2 / rounded,
        dy2 = l2 * Math.cos(alpha + b) / 2 / rounded;
    return {
      x1: p2x - dx1,
      y1: p2y + dy1,
      x2: p2x + dx2,
      y2: p2y + dy2
    };
  },

  linepathRevert : function(path) {
    var rev = [], anc = false;
    for (var i = path.length - 1; i >= 0; i--) {
      switch (path[i][0]) {
        case "M" : case "L" :
          if (!anc)
            rev.push( [ rev.length ? "L" : "M", path[i][1], path[i][2] ] );
          else
            rev.push( [ "C", anc[0], anc[1], anc[2], anc[3], path[i][1], path[i][2] ] );
          anc = false;

          break;
        case "C" :
          if (!anc)
            rev.push( [ rev.length ? "L" : "M", path[i][5], path[i][6] ] );
          else
            rev.push( [ "C", anc[0], anc[1], anc[2], anc[3], path[i][5], path[i][6] ] );
          anc = [ path[i][3], path[i][4], path[i][1], path[i][2] ];
      }
    }
    return rev;
  },

  linepath : function ( points, rounded ) {
    var path = [];
    if (rounded) {
      var anc = false;
      for (var j = 0, jj = points.length - 1; j < jj ; j++) {
        if (j) {
          var a = this.linepathAnchors(points[j - 1][0], points[j - 1][1], points[j][0], points[j][1], points[j + 1][0], points[j + 1][1], rounded);
          path.push([ "C", anc[0], anc[1], a.x1, a.y1, points[j][0], points[j][1] ]);
          anc = [ a.x2, a.y2 ];
        } else {
          path.push([ "M", points[j][0], points[j][1] ]);
          anc = [ points[j][0], points[j][1] ];
        }
      }
      if (anc)
        path.push([ "C", anc[0], anc[1], points[jj][0], points[jj][1], points[jj][0], points[jj][1] ]);

    } else
      for (var i = 0; i < points.length; i++) {
        var x = points[i][0], y = points[i][1];
        path.push([i == 0 ? "M" : "L", x, y]);
      }

    return path;
  },

  lineareapath : function (points1, points2, rounded) {
    var path = this.linepath(points1, rounded), path2 = this.linepathRevert(this.linepath(points2, rounded));

    for (var i = 0; i < path2.length; i++)
      path.push( !i ? [ "L", path2[0][1], path2[0][2] ] : path2[i] );

    if (path.length)
      path.push(['z']);

    return path;
  },

  /**
   * Prende la coordinata X di un passo di un path
   */
  getX : function(p, pos) {
    switch (p[0]) {
      case 'CIRCLE':
        return p[1];
      case 'RECT':
        return p[!pos ? 1 : 3];
      case 'SLICE':
        return p[1];
      default:
        return p[p.length - 2];
    }
  },

  /**
   * Prende la coordinata Y di un passo di un path
   */
  getY : function(p, pos) {
    switch (p[0]) {
      case 'CIRCLE':
        return p[2];
      case 'RECT':
        return p[!pos ? 2 : 4];
      case 'SLICE':
        return p[2];
      default:
        return p[p.length - 1];
    }
  },

  /**
   * Prende il centro di un path
   *
   * @param offset un offset [x,y] da applicare. Da notare che gli assi potrebbero essere dipendenti dalla figura
   *        (ad esempio per lo SLICE x e' l'asse che passa dal centro del cerchio, y l'ortogonale).
   */
  getCenter: function(path, offset) {
    if (!path.path)
      return false;
    if (path.path.length == 0)
      return false;
    if (!offset)
      offset = [0, 0];

    if (path.center)
      return [path.center[0] + offset[0], path.center[1] + offset[1]];

    var p = path.path[0];
    switch (p[0]) {
      case 'CIRCLE':
        return [p[1] + offset[0], p[2] + offset[1]];
      case 'RECT':
        return [(p[1] + p[2])/2 + offset[0], (p[3] + p[4])/2 + offset[1]];
      case 'SLICE':
        var popangle = p[5] + (p[6] - p[5]) / 2;
        var rad = Math.PI / 180;
        return [
          p[1] + (p[4] + ((p[3] - p[4]) / 2) + offset[0]) * Math.cos(-popangle * rad) + offset[1] * Math.cos((-popangle-90) * rad),
          p[2] + (p[4] + ((p[3] - p[4]) / 2) + offset[0]) * Math.sin(-popangle * rad) + offset[1] * Math.sin((-popangle-90) * rad)
        ];
    }

    // WARN Complex paths not supported
    alert('ElyCharts: getCenter with complex path not supported');

    return false;
  },

  /**
   * Sposta il path passato di un offset [x,y]
   * Il risultato e' il nuovo path
   *
   * @param offset un offset [x,y] da applicare. Da notare che gli assi potrebbero essere dipendenti dalla figura
   *        (ad esempio per lo SLICE x e' l'asse che passa dal centro del cerchio, y l'ortogonale).
   * @param marginlimit se true non sposta oltre i margini del grafico (applicabile solo su path standard o RECT)
   * @param simple se true lo spostamento e' sempre fatto sul sistema [x, y] complessivo (altrimenti alcuni elementi, come lo SLICE,
   *        si muovono sul proprio sistema di coordinate - la x muove lungo il raggio e la y lungo l'ortogonale)
   */
  movePath : function(env, path, offset, marginlimit, simple) {
    var p = [], i;
    if (path.length == 1 && path[0][0] == 'RECT')
      return [ [path[0][0], this._movePathX(env, path[0][1], offset[0], marginlimit), this._movePathY(env, path[0][2], offset[1], marginlimit), this._movePathX(env, path[0][3], offset[0], marginlimit), this._movePathY(env, path[0][4], offset[1], marginlimit)] ];
    if (path.length == 1 && path[0][0] == 'SLICE') {
      if (!simple) {
        var popangle = path[0][5] + (path[0][6] - path[0][5]) / 2;
        var rad = Math.PI / 180;
        var x = path[0][1] + offset[0] * Math.cos(- popangle * rad) + offset[1] * Math.cos((-popangle-90) * rad);
        var y = path[0][2] + offset[0] * Math.sin(- popangle * rad) + offset[1] * Math.cos((-popangle-90) * rad);
        return [ [path[0][0], x, y, path[0][3], path[0][4], path[0][5], path[0][6] ] ];
      }
      else
        return [ [ path[0][0], path[0][1] + offset[0], path[0][2] + offset[1], path[0][3], path[0][4], path[0][5], path[0][6] ] ];
    }
    if (path.length == 1 && path[0][0] == 'CIRCLE')
      return [ [ path[0][0], path[0][1] + offset[0], path[0][2] + offset[1], path[0][3] ] ];
    if (path.length == 1 && path[0][0] == 'TEXT')
      return [ [ path[0][0], path[0][1], path[0][2] + offset[0], path[0][3] + offset[1] ] ];
    if (path.length == 1 && path[0][0] == 'LINE') {
      for (i = 0; i < path[0][1].length; i++)
        p.push( [ this._movePathX(env, path[0][1][i][0], offset[0], marginlimit), this._movePathY(env, path[0][1][i][1], offset[1], marginlimit) ] );
      return [ [ path[0][0], p, path[0][2] ] ];
    }
    if (path.length == 1 && path[0][0] == 'LINEAREA') {
      for (i = 0; i < path[0][1].length; i++)
        p.push( [ this._movePathX(env, path[0][1][i][0], offset[0], marginlimit), this._movePathY(env, path[0][1][i][1], offset[1], marginlimit) ] );
      var pp = [];
      for (i = 0; i < path[0][2].length; i++)
        pp.push( [ this._movePathX(env, path[0][2][i][0], offset[0], marginlimit), this._movePathY(env, path[0][2][i][1], offset[1], marginlimit) ] );
      return [ [ path[0][0], p, pp, path[0][3] ] ];
    }

    var newpath = [];
    // http://www.w3.org/TR/SVG/paths.html#PathData
    for (var j = 0; j < path.length; j++) {
      var o = path[j];
      switch (o[0]) {
        case 'M': case 'm': case 'L': case 'l': case 'T': case 't':
          // (x y)+
          newpath.push([o[0], this._movePathX(env, o[1], offset[0], marginlimit), this._movePathY(env, o[2], offset[1], marginlimit)]);
          break;
        case 'A': case 'a':
          // (rx ry x-axis-rotation large-arc-flag sweep-flag x y)+
          newpath.push([o[0], o[1], o[2], o[3], o[4], o[5], this._movePathX(env, o[6], offset[0], marginlimit), this._movePathY(env, o[7], offset[1], marginlimit)]);
          break;
        case 'C': case 'c':
          // (x1 y1 x2 y2 x y)+
          newpath.push([o[0], o[1], o[2], o[3], o[4], this._movePathX(env, o[5], offset[0], marginlimit), this._movePathY(env, o[6], offset[1], marginlimit)]);
          break;
        case 'S': case 's': case 'Q': case 'q':
          // (x1 y1 x y)+
          newpath.push([o[0], o[1], o[2], this._movePathX(env, o[3], offset[0], marginlimit), this._movePathY(env, o[4], offset[1], marginlimit)]);
          break;
        case 'z': case 'Z':
          newpath.push([o[0]]);
          break;
      }
    }

    return newpath;
  },

  _movePathX : function(env, x, dx, marginlimit) {
    if (!marginlimit)
      return x + dx;
    x = x + dx;
    return dx > 0 && x > env.opt.width - env.opt.margins[1] ? env.opt.width - env.opt.margins[1] : (dx < 0 && x < env.opt.margins[3] ? env.opt.margins[3] : x);
  },

  _movePathY : function(env, y, dy, marginlimit) {
    if (!marginlimit)
      return y + dy;
    y = y + dy;
    return dy > 0 && y > env.opt.height - env.opt.margins[2] ? env.opt.height - env.opt.margins[2] : (dy < 0 && y < env.opt.margins[0] ? env.opt.margins[0] : y);
  },

  /**
   * Ritorna le proprieta SVG da impostare per visualizzare il path non SVG passato (se applicabile, per CIRCLE e TEXT non lo e')
   */
  getSVGProps : function(path, prevprops) {
    var props = prevprops ? prevprops : {};
    var type = 'path', value;

    if (path.length == 1 && path[0][0] == 'RECT')
      value = common.absrectpath(path[0][1], path[0][2], path[0][3], path[0][4], path[0][5]);
    else if (path.length == 1 && path[0][0] == 'SLICE') {
      type = 'slice';
      value = [ path[0][1], path[0][2], path[0][3], path[0][4], path[0][5], path[0][6] ];
    } else if (path.length == 1 && path[0][0] == 'LINE')
      value = common.linepath( path[0][1], path[0][2] );
    else if (path.length == 1 && path[0][0] == 'LINEAREA')
      value = common.lineareapath( path[0][1], path[0][2], path[0][3] );
    else if (path.length == 1 && (path[0][0] == 'CIRCLE' || path[0][0] == 'TEXT' || path[0][0] == 'DOMELEMENT' || path[0][0] == 'RELEMENT'))
      return prevprops ? prevprops : false;
    else
      value = path;

    if (type != 'path' || (value && value.length > 0))
      props[type] = value;
    else if (!prevprops)
      return false;
    return props;
  },

  /**
   * Disegna il path passato
   * Gestisce la feature pixelWorkAround
   */
  showPath : function(env, path, paper) {
    path = this.preparePathShow(env, path);

    if (!paper)
      paper = env.paper;
    if (path.length == 1 && path[0][0] == 'CIRCLE')
      return paper.circle(path[0][1], path[0][2], path[0][3]);
    if (path.length == 1 && path[0][0] == 'TEXT')
      return paper.text(path[0][2], path[0][3], path[0][1]);
    var props = this.getSVGProps(path);

    // Props must be with some data in it
    var hasdata = false;
    for (var k in props) {
      hasdata = true;
      break;
    }

    return props && hasdata ? paper.path().attr(props) : false;
  },

  /**
   * Applica al path le modifiche per poterlo visualizzare
   * Per ora applica solo pixelWorkAround
   */
  preparePathShow : function(env, path) {
    return env.opt.features.pixelWorkAround.active ? this.movePath(env, this._clone(path), [.5, .5], false, true) : path;
  },

  /**
   * Ritorna gli attributi Raphael completi di un piece
   * Per attributi completi si intende l'insieme di attributi specificato,
   * assieme a tutti gli attributi calcolati che determinano lo stato
   * iniziale di un piece (e permettono di farlo ritornare a tale stato).
   * In genere viene aggiunto il path SVG, per il circle vengono aggiunti
   * i dati x,y,r
   */
  getPieceFullAttr : function(env, piece) {
    if (!piece.fullattr) {
      piece.fullattr = this._clone(piece.attr);
      if (piece.path)
        switch (piece.path[0][0]) {
          case 'CIRCLE':
            var ppath = this.preparePathShow(env, piece.path);
            piece.fullattr.cx = ppath[0][1];
            piece.fullattr.cy = ppath[0][2];
            piece.fullattr.r = ppath[0][3];
            break;
          case 'TEXT': case 'DOMELEMENT': case 'RELEMENT':
            break;
          default:
            piece.fullattr = this.getSVGProps(this.preparePathShow(env, piece.path), piece.fullattr);
        }
      if (typeof piece.fullattr.opacity == 'undefined')
        piece.fullattr.opacity = 1;
    }
    return piece.fullattr;
  },


  show : function(env, pieces) {
    pieces = this.getSortedPathData(pieces);

    common.animationStackStart(env);

    var previousElement = false;
    for (var i = 0; i < pieces.length; i++) {
      var piece = pieces[i];

      if (typeof piece.show == 'undefined' || piece.show) {
        // If there is piece.animation.element, this is the old element that must be transformed to the new one
        piece.element = piece.animation && piece.animation.element ? piece.animation.element : false;
        piece.hide = false;

        if (!piece.path) {
          // Element should not be shown or must be hidden: nothing to prepare
          piece.hide = true;

        } else if (piece.path.length == 1 && piece.path[0][0] == 'TEXT') {
          // TEXT
          // Animation is not supported, so if there's an old element i must hide it (with force = true to hide it for sure, even if there's a new version of same element)
          if (piece.element) {
            common.animationStackPush(env, piece, piece.element, false, piece.animation.speed, piece.animation.easing, piece.animation.delay, true);
            piece.animation.element = false;
          }
          piece.element = this.showPath(env, piece.path);
          // If this is a transition i must position new element
          if (piece.element && env.newopt && previousElement)
            piece.element.insertAfter(previousElement);

        } else if (piece.path.length == 1 && piece.path[0][0] == 'DOMELEMENT') {
          // DOMELEMENT
          // Already shown
          // Animation not supported

        } else if (piece.path.length == 1 && piece.path[0][0] == 'RELEMENT') {
          // RAPHAEL ELEMENT
          // Already shown
          // Animation is not supported, so if there's an old element i must hide it (with force = true to hide it for sure, even if there's a new version of same element)
          if (piece.element) {
            common.animationStackPush(env, piece, piece.element, false, piece.animation.speed, piece.animation.easing, piece.animation.delay, true);
            piece.animation.element = false;
          }

          piece.element = piece.path[0][1];
          if (piece.element && previousElement)
            piece.element.insertAfter(previousElement);
          piece.attr = false;

        } else {
          // OTHERS
          if (!piece.element) {
            if (piece.animation && piece.animation.startPath && piece.animation.startPath.length)
              piece.element = this.showPath(env, piece.animation.startPath);
            else
              piece.element = this.showPath(env, piece.path);

            // If this is a transition i must position new element
            if (piece.element && env.newopt && previousElement)
              piece.element.insertAfter(previousElement);
          }
        }

        if (piece.element) {
          if (piece.attr) {
            if (!piece.animation) {
              // Standard piece visualization
              if (typeof piece.attr.opacity == 'undefined')
                piece.attr.opacity = 1;
              piece.element.attr(piece.attr);

            } else {
              // Piece animation
              if (!piece.animation.element)
                piece.element.attr(piece.animation.startAttr ? piece.animation.startAttr : piece.attr);
              //if (typeof animationAttr.opacity == 'undefined')
              //  animationAttr.opacity = 1;
              common.animationStackPush(env, piece, piece.element, this.getPieceFullAttr(env, piece), piece.animation.speed, piece.animation.easing, piece.animation.delay);
            }
          } else if (piece.hide)
            // Hide the piece
            common.animationStackPush(env, piece, piece.element, false, piece.animation.speed, piece.animation.easing, piece.animation.delay);

          previousElement = piece.element;
        }
      }
    }

    common.animationStackEnd(env);
  },

  /**
   * Given an array of pieces, return an array of single pathdata contained in pieces, sorted by zindex
   */
  getSortedPathData : function(pieces) {
    res = [];

    for (var i = 0; i < pieces.length; i++) {
      var piece = pieces[i];
      if (piece.paths) {
        for (var j = 0; j < piece.paths.length; j++) {
          piece.paths[j].pos = res.length;
          piece.paths[j].parent = piece;
          res.push(piece.paths[j]);
        }
      } else {
        piece.pos = res.length;
        piece.parent = false;
        res.push(piece);
      }
    }
    return res.sort(function (a, b) {
      var za = typeof a.attr == 'undefined' || typeof a.attr.zindex == 'undefined' ? ( !a.parent || typeof a.parent.attr == 'undefined' || typeof a.parent.attr.zindex == 'undefined' ? 0 : a.parent.attr.zindex ) : a.attr.zindex;
      var zb = typeof b.attr == 'undefined' || typeof b.attr.zindex == 'undefined' ? ( !b.parent || typeof b.parent.attr == 'undefined' || typeof b.parent.attr.zindex == 'undefined' ? 0 : b.parent.attr.zindex ) : b.attr.zindex;
      return za < zb ? -1 : (za > zb ? 1 : (a.pos < b.pos ? -1 : (a.pos > b.pos ? 1 : 0)));
    });
  },

  animationStackStart : function(env) {
    if (!env.animationStackDepth || env.animationStackDepth == 0) {
      env.animationStackDepth = 0;
      env.animationStack = {};
    }
    env.animationStackDepth ++;
  },

  animationStackEnd : function(env) {
    env.animationStackDepth --;
    if (env.animationStackDepth == 0) {
      for (var delay in env.animationStack) {
        this._animationStackAnimate(env.animationStack[delay], delay);
        delete env.animationStack[delay];
      }
      env.animationStack = {};
    }
  },

  /**
   * Inserisce l'animazione richiesta nello stack di animazioni.
   * Nel caso lo stack non sia inizializzato esegue subito l'animazione.
   */
  animationStackPush : function(env, piece, element, newattr, speed, easing, delay, force) {
    if (typeof delay == 'undefined')
      delay = 0;

    if (!env.animationStackDepth || env.animationStackDepth == 0) {
      this._animationStackAnimate([{piece : piece, object : element, props : newattr, speed: speed, easing : easing, force : force}], delay);

    } else {
      if (!env.animationStack[delay])
        env.animationStack[delay] = [];

      env.animationStack[delay].push({piece : piece, object : element, props : newattr, speed: speed, easing : easing, force : force});
    }
  },

  _animationStackAnimate : function(stack, delay) {
    var caller = this;
    var func = function() {
      var a = stack.pop();
      caller._animationStackAnimateElement(a);

      while (stack.length > 0) {
        var b = stack.pop();
        caller._animationStackAnimateElement(b, a);
      }
    }
    if (delay > 0)
      setTimeout(func, delay);
    else
      func();
  },

  _animationStackAnimateElement : function (a, awith) {
    //console.warn('call', a.piece.animationInProgress, a.force, a.piece.path, a.piece);

    if (a.force || !a.piece.animationInProgress) {

      // Metodo non documentato per bloccare l'animazione corrente
      a.object.stop();
      if (!a.props)
        a.props = { opacity : 0 }; // TODO Sarebbe da rimuovere l'elemento alla fine

      if (!a.speed || a.speed <= 0) {
        //console.warn('direct');
        a.object.attr(a.props);
        a.piece.animationInProgress = false;
        return;
      }

      a.piece.animationInProgress = true;
      //console.warn('START', a.piece.animationInProgress, a.piece.path, a.piece);

      // NOTA onEnd non viene chiamato se l'animazione viene bloccata con stop
      var onEnd = function() {
        //console.warn('END', a.piece.animationInProgress, a.piece);
        a.piece.animationInProgress = false
      }

      if (awith)
        a.object.animateWith(awith, a.props, a.speed, a.easing ? a.easing : 'linear', onEnd);
      else
        a.object.animate(a.props, a.speed, a.easing ? a.easing : 'linear', onEnd);
    }
    //else console.warn('SKIP', a.piece.animationInProgress, a.piece.path, a.piece);
  }
}

var common = $.elycharts.common;

/***********************************************************************
 * FEATURESMANAGER
 **********************************************************************/

$.elycharts.featuresmanager = {

  managers : [],
  initialized : false,

  register : function(manager, priority) {
    $.elycharts.featuresmanager.managers.push([priority, manager]);
    $.elycharts.featuresmanager.initialized = false;
  },

  init : function() {
    $.elycharts.featuresmanager.managers.sort(function(a, b) { return a[0] < b[0] ? -1 : (a[0] == b[0] ? 0 : 1) });
    $.elycharts.featuresmanager.initialized = true;
  },

  beforeShow : function(env, pieces) {
    if (!$.elycharts.featuresmanager.initialized)
      this.init();
    for (var i = 0; i < $.elycharts.featuresmanager.managers.length; i++)
      if ($.elycharts.featuresmanager.managers[i][1].beforeShow)
        $.elycharts.featuresmanager.managers[i][1].beforeShow(env, pieces);
  },

  afterShow : function(env, pieces) {
    if (!$.elycharts.featuresmanager.initialized)
      this.init();
    for (var i = 0; i < $.elycharts.featuresmanager.managers.length; i++)
      if ($.elycharts.featuresmanager.managers[i][1].afterShow)
        $.elycharts.featuresmanager.managers[i][1].afterShow(env, pieces);
  },

  onMouseOver : function(env, serie, index, mouseAreaData) {
    if (!$.elycharts.featuresmanager.initialized)
      this.init();
    for (var i = 0; i < $.elycharts.featuresmanager.managers.length; i++)
      if ($.elycharts.featuresmanager.managers[i][1].onMouseOver)
        $.elycharts.featuresmanager.managers[i][1].onMouseOver(env, serie, index, mouseAreaData);
  },

  onMouseOut : function(env, serie, index, mouseAreaData) {
    if (!$.elycharts.featuresmanager.initialized)
      this.init();
    for (var i = 0; i < $.elycharts.featuresmanager.managers.length; i++)
      if ($.elycharts.featuresmanager.managers[i][1].onMouseOut)
        $.elycharts.featuresmanager.managers[i][1].onMouseOut(env, serie, index, mouseAreaData);
  },

  onMouseEnter : function(env, serie, index, mouseAreaData) {
    if (!$.elycharts.featuresmanager.initialized)
      this.init();
    for (var i = 0; i < $.elycharts.featuresmanager.managers.length; i++)
      if ($.elycharts.featuresmanager.managers[i][1].onMouseEnter)
        $.elycharts.featuresmanager.managers[i][1].onMouseEnter(env, serie, index, mouseAreaData);
  },

  onMouseChanged : function(env, serie, index, mouseAreaData) {
    if (!$.elycharts.featuresmanager.initialized)
      this.init();
    for (var i = 0; i < $.elycharts.featuresmanager.managers.length; i++)
      if ($.elycharts.featuresmanager.managers[i][1].onMouseChanged)
        $.elycharts.featuresmanager.managers[i][1].onMouseChanged(env, serie, index, mouseAreaData);
  },

  onMouseExit : function(env, serie, index, mouseAreaData) {
    if (!$.elycharts.featuresmanager.initialized)
      this.init();
    for (var i = 0; i < $.elycharts.featuresmanager.managers.length; i++)
      if ($.elycharts.featuresmanager.managers[i][1].onMouseExit)
        $.elycharts.featuresmanager.managers[i][1].onMouseExit(env, serie, index, mouseAreaData);
  }
}

})(jQuery);

/***********************************************

* OGGETTI USATI:

PIECE:
Contiene un elemento da visualizzare nel grafico. E' un oggetto con queste proprietÃ :

- section,[serie],[index],[subsection]: Dati che permettono di identificare che tipo
  di elemento Ã¨ e a quale blocco della configurazione appartiene.
  Ad esempio gli elementi principali del chart hanno
  section="Series", serie=nome della serie, subSection = 'Plot'
- [paths]: Contiene un array di pathdata, nel caso questo piece Ã¨ costituito da
  piu' sottoelementi (ad esempio i Dots, o gli elementi di un Pie o Funnel)
- [PATHDATA.*]: Se questo piece e' costituito da un solo elemento, i suoi dati sono
  memorizzati direttamente nella root di PIECE.
- show: Proprieta' usata internamente per decidere se questo piece dovrÃ  essere
  visualizzato o meno (in genere nel caso di una transizione che non ha variato
  questo piece, che quindi puo' essere lasciato allo stato precedente)
- hide: Proprieta' usata internamente per decidere se l'elemento va nascosto,
  usato in caso di transizione se l'elemento non Ã¨ piu' presente.

PATHDATA:
I dati utili per visualizzare un path nel canvas:

- PATH: Il path che permette di disegnare l'elemento. Se NULL l'elemento Ã¨ vuoto/ da
  non visualizzare (instanziato solo come placeholder)
- attr: gli attributi Raphael dell'elemento. NULL se path Ã¨ NULL.
- [center]: centro del path
- [rect]: rettangolo che include il path

PATH:
Un array in cui ogni elemento determina un passo del percorso per disegnare il grafico.
E' una astrazione sul PATH SVG effettivo, e puo' avere alcuni valori speciali:
[ [ 'TEXT',  testo, x, y ] ]
[ [ 'CIRCLE', x, y, raggio ] ]
[ [ 'RECT', x1, y1, x2, y2, rounded ] ] (x1,y1 dovrebbero essere sempre le coordinate in alto a sx)
[ [ 'SLICE', x, y, raggio, raggio int, angolo1, angolo2 ] ] (gli angoli sono in gradi)
[ [ 'RELEMENT', element ] ] (elemento Raphael gia' disegnato)
[ [ 'DOMELEMENT', element ] ] (elemento DOM - in genere un DIV html - giÃ  disegnato)
[ ... Path SVG ... ]

------------------------------------------------------------------------

Z-INDEX:
0 : base
10 : tooltip
20 : interactive area (tutti gli elementi innescati dalla interactive area dovrebbero essere < 20)
25 : label / balloons (potrebbero essere resi cliccabili dall'esterno, quindi > 20)

------------------------------------------------------------------------

USEFUL RESOURCES:

http://docs.jquery.com/Plugins/Authoring
http://www.learningjquery.com/2007/10/a-plugin-development-pattern
http://dean.edwards.name/packer/2/usage/#special-chars

http://raphaeljs.com/reference.html#attr

TODO
* ottimizzare common.areaProps
* rifare la posizione del tooltip del pie
* ripristinare shadow

*********************************************/
/********* Source File: src/elycharts_manager_anchor.js*********/
/**********************************************************************
 * ELYCHARTS
 * A Javascript library to generate interactive charts with vectorial graphics.
 *
 * Copyright (c) 2010 Void Labs s.n.c. (http://void.it)
 * Licensed under the MIT (http://creativecommons.org/licenses/MIT/) license.
 **********************************************************************/

(function($) {

var featuresmanager = $.elycharts.featuresmanager;
var common = $.elycharts.common;

/***********************************************************************
 * FEATURE: ANCHOR
 *
 * Permette di collegare i dati del grafico con delle aree esterne,
 * identificate dal loro selettore CSS, e di interagire con esse.
 **********************************************************************/

$.elycharts.anchormanager = {

  afterShow : function(env, pieces) {
    // Prendo le aree gestite da mouseAreas, e metto i miei listener
    // Non c'e' bisogno di gestire il clean per una chiamata successiva, lo fa gia' il mouseareamanager
    // Tranne per i bind degli eventi jquery

    if (!env.opt.anchors)
      return;

    if (!env.anchorBinds)
      env.anchorBinds = [];

    while (env.anchorBinds.length) {
      var b = env.anchorBinds.pop();
      $(b[0]).unbind(b[1], b[2]);
    }

    for (var i = 0; i < env.mouseAreas.length; i++) {
      var serie = env.mouseAreas[i].piece ? env.mouseAreas[i].piece.serie : false;
      var anc;
      if (serie)
        anc = env.opt.anchors[serie][env.mouseAreas[i].index];
      else
        anc = env.opt.anchors[env.mouseAreas[i].index];

      if (anc && env.mouseAreas[i].props.anchor && env.mouseAreas[i].props.anchor.highlight) {

        (function(env, mouseAreaData, anc, caller) {

          var f1 = function() { caller.anchorMouseOver(env, mouseAreaData); };
          var f2 = function() { caller.anchorMouseOut(env, mouseAreaData); };
          if (!env.mouseAreas[i].props.anchor.useMouseEnter) {
            env.anchorBinds.push([anc, 'mouseover', f1]);
            env.anchorBinds.push([anc, 'mouseout', f2]);
            $(anc).mouseover(f1);
            $(anc).mouseout(f2);
          } else {
            env.anchorBinds.push([anc, 'mouseenter', f1]);
            env.anchorBinds.push([anc, 'mouseleave', f2]);
            $(anc).mouseenter(f1);
            $(anc).mouseleave(f2);
          }
        })(env, env.mouseAreas[i], anc, this);
      }
    }

    env.onAnchors = [];
  },

  anchorMouseOver : function(env, mouseAreaData) {
    $.elycharts.highlightmanager.onMouseOver(env, mouseAreaData.piece ? mouseAreaData.piece.serie : false, mouseAreaData.index, mouseAreaData);
  },

  anchorMouseOut : function(env, mouseAreaData) {
    $.elycharts.highlightmanager.onMouseOut(env, mouseAreaData.piece ? mouseAreaData.piece.serie : false, mouseAreaData.index, mouseAreaData);
  },

  onMouseOver : function(env, serie, index, mouseAreaData) {
    if (!env.opt.anchors)
      return;

    if (mouseAreaData.props.anchor && mouseAreaData.props.anchor.addClass) {
      //var serie = mouseAreaData.piece ? mouseAreaData.piece.serie : false;
      var anc;
      if (serie)
        anc = env.opt.anchors[serie][mouseAreaData.index];
      else
        anc = env.opt.anchors[mouseAreaData.index];
      if (anc) {
        $(anc).addClass(mouseAreaData.props.anchor.addClass);
        env.onAnchors.push([anc, mouseAreaData.props.anchor.addClass]);
      }
    }
  },

  onMouseOut : function(env, serie, index, mouseAreaData) {
    if (!env.opt.anchors)
      return;

    while (env.onAnchors.length > 0) {
      var o = env.onAnchors.pop();
      $(o[0]).removeClass(o[1]);
    }
  }
}

$.elycharts.featuresmanager.register($.elycharts.anchormanager, 30);

})(jQuery);
/********* Source File: src/elycharts_manager_animation.js*********/
/**********************************************************************
 * ELYCHARTS
 * A Javascript library to generate interactive charts with vectorial graphics.
 *
 * Copyright (c) 2010 Void Labs s.n.c. (http://void.it)
 * Licensed under the MIT (http://creativecommons.org/licenses/MIT/) license.
 **********************************************************************/

(function($) {

//var featuresmanager = $.elycharts.featuresmanager;
var common = $.elycharts.common;

/***********************************************************************
 * ANIMATIONMANAGER
 **********************************************************************/

$.elycharts.animationmanager = {

  beforeShow : function(env, pieces) {
    if (!env.newopt)
      this.startAnimation(env, pieces);
    else
      this.stepAnimation(env, pieces);
  },

  stepAnimation : function(env, pieces) {
    // env.pieces sono i vecchi pieces, ed e' sempre un array completo di tutte le sezioni
    // pieces sono i nuovi pezzi da mostrare, e potrebbe essere parziale
    //console.warn('from1', common._clone(env.pieces));
    //console.warn('from2', common._clone(pieces));
    pieces = this._stepAnimationInt(env, env.pieces, pieces);
    //console.warn('to', common._clone(pieces));
  },

  _stepAnimationInt : function(env, pieces1, pieces2, section, serie, internal) {
    // Se pieces2 == null deve essere nascosto tutto pieces1

    var newpieces = [], newpiece;
    var j = 0;
    for (var i = 0; i < pieces1.length; i ++) {
      var animationProps = common.areaProps(env, section ? section : pieces1[i].section, serie ? serie : pieces1[i].serie);
      if (animationProps && animationProps.stepAnimation)
        animationProps = animationProps.stepAnimation;
      else
        animationProps = env.opt.features.animation.stepAnimation;

      // Se il piece attuale c'e' solo in pieces2 lo riporto nei nuovi, impostando come gia' mostrato
      // A meno che internal = true (siamo in un multipath, nel caso se una cosa non c'e' va considerata da togliere)
      if (pieces2 && (j >= pieces2.length || !common.samePiecePath(pieces1[i], pieces2[j]))) {
        if (!internal) {
          pieces1[i].show = false;
          newpieces.push(pieces1[i]);
        } else {
          newpiece = { path : false, attr : false, show : true };
          newpiece.animation = {
            element : pieces1[i].element ? pieces1[i].element : false,
            speed : animationProps && animationProps.speed ? animationProps.speed : 300,
            easing : animationProps && animationProps.easing ? animationProps.easing : '',
            delay : animationProps && animationProps.delay ? animationProps.delay : 0
          }
          newpieces.push(newpiece);
        }
      }
      // Bisogna gestire la transizione dal vecchio piece al nuovo
      else {
        newpiece = pieces2 ? pieces2[j] : { path : false, attr : false };
        newpiece.show = true;
        if (typeof pieces1[i].paths == 'undefined') {
          // Piece a singolo path
          newpiece.animation = {
            element : pieces1[i].element ? pieces1[i].element : false,
            speed : animationProps && animationProps.speed ? animationProps.speed : 300,
            easing : animationProps && animationProps.easing ? animationProps.easing : '',
            delay : animationProps && animationProps.delay ? animationProps.delay : 0
          }
          // Se non c'era elemento precedente deve gestire il fadeIn
          if (!pieces1[i].element)
            newpiece.animation.startAttr = {opacity : 0};

        } else {
          // Multiple path piece
          newpiece.paths = this._stepAnimationInt(env, pieces1[i].paths, pieces2[j].paths, pieces1[i].section, pieces1[i].serie, true);
        }
        newpieces.push(newpiece);
        j++;
      }
    }
    // If there are pieces left in pieces2 i must add them unchanged
    if (pieces2)
      for (; j < pieces2.length; j++)
        newpieces.push(pieces2[j]);

    return newpieces;
  },

  startAnimation : function(env, pieces) {
    for (var i = 0; i < pieces.length; i++)
      if (pieces[i].paths || pieces[i].path) {
        var props = common.areaProps(env, pieces[i].section, pieces[i].serie);
        if (props && props.startAnimation)
          props = props.startAnimation;
        else
          props = env.opt.features.animation.startAnimation;

        if (props.active) {
          if (props.type == 'simple' || pieces[i].section != 'Series')
            this.animationSimple(env, props, pieces[i]);
          if (props.type == 'grow')
            this.animationGrow(env, props, pieces[i]);
          if (props.type == 'avg')
            this.animationAvg(env, props, pieces[i]);
          if (props.type == 'reg')
            this.animationReg(env, props, pieces[i]);
        }
      }
  },

  /**
   * Inserisce i dati base di animazione del piece e la transizione di attributi
   */
  _animationPiece : function(piece, animationProps, subSection) {
    if (piece.paths) {
      for (var i = 0; i < piece.paths.length; i++)
        this._animationPiece(piece.paths[i], animationProps, subSection);
    } else if (piece.path) {
      piece.animation = {
        speed : animationProps.speed,
        easing : animationProps.easing,
        delay : animationProps.delay,
        startPath : [],
        startAttr : common._clone(piece.attr)
      };
      if (animationProps.propsTo)
        piece.attr = common._mergeObjects(piece.attr, animationProps.propsTo);
      if (animationProps.propsFrom)
        piece.animation.startAttr = common._mergeObjects(piece.animation.startAttr, animationProps.propsFrom);
      if (subSection && animationProps[subSection.toLowerCase() + 'PropsFrom'])
        piece.animation.startAttr = common._mergeObjects(piece.animation.startAttr, animationProps[subSection.toLowerCase() + 'PropsFrom']);

      if (typeof piece.animation.startAttr.opacity != 'undefined' && typeof piece.attr.opacity == 'undefined')
        piece.attr.opacity = 1;
    }
  },

  animationSimple : function(env, props, piece) {
    this._animationPiece(piece, props, piece.subSection);
  },

  animationGrow : function(env, props, piece) {
    this._animationPiece(piece, props, piece.subSection);
    var i, npath, y;

    switch (env.opt.type) {
      case 'line':
        y = env.opt.height - env.opt.margins[2];
        switch (piece.subSection) {
          case 'Plot':
            if (!piece.paths) {
                npath = [ 'LINE', [], piece.path[0][2]];
                for (i = 0; i < piece.path[0][1].length; i++)
                  npath[1].push([ piece.path[0][1][i][0], y ]);
                piece.animation.startPath.push(npath);

            } else {
              for (i = 0; i < piece.paths.length; i++)
                if (piece.paths[i].path)
                  piece.paths[i].animation.startPath.push([ 'RECT', piece.paths[i].path[0][1], y, piece.paths[i].path[0][3], y ]);
            }
            break;
          case 'Fill':
            npath = [ 'LINEAREA', [], [], piece.path[0][3]];
            for (i = 0; i < piece.path[0][1].length; i++) {
              npath[1].push([ piece.path[0][1][i][0], y ]);
              npath[2].push([ piece.path[0][2][i][0], y ]);
            }
            piece.animation.startPath.push(npath);

            break;
          case 'Dot':
            for (i = 0; i < piece.paths.length; i++)
              if (piece.paths[i].path)
                piece.paths[i].animation.startPath.push(['CIRCLE', piece.paths[i].path[0][1], y, piece.paths[i].path[0][3]]);
            break;
        }
        break;

      case 'pie':
        if (piece.subSection == 'Plot')
          for (i = 0; i < piece.paths.length; i++)
            if (piece.paths[i].path && piece.paths[i].path[0][0] == 'SLICE')
              piece.paths[i].animation.startPath.push([ 'SLICE', piece.paths[i].path[0][1], piece.paths[i].path[0][2], piece.paths[i].path[0][4] + piece.paths[i].path[0][3] * 0.1, piece.paths[i].path[0][4], piece.paths[i].path[0][5], piece.paths[i].path[0][6] ]);

        break;

      case 'funnel':
        alert('Unsupported animation GROW for funnel');
        break;

      case 'barline':
        var x;
        if (piece.section == 'Series' && piece.subSection == 'Plot') {
          if (!props.subType)
            x = env.opt.direction != 'rtl' ? env.opt.margins[3] : env.opt.width - env.opt.margins[1];
          else if (props.subType == 1)
            x = env.opt.direction != 'rtl' ? env.opt.width - env.opt.margins[1] : env.opt.margins[3];
          for (i = 0; i < piece.paths.length; i++)
            if (piece.paths[i].path) {
              if (!props.subType || props.subType == 1)
                piece.paths[i].animation.startPath.push([ 'RECT', x, piece.paths[i].path[0][2], x, piece.paths[i].path[0][4], piece.paths[i].path[0][5] ]);
              else {
                y = (piece.paths[i].path[0][2] + piece.paths[i].path[0][4]) / 2;
                piece.paths[i].animation.startPath.push([ 'RECT', piece.paths[i].path[0][1], y, piece.paths[i].path[0][3], y, piece.paths[i].path[0][5] ]);
              }
            }
        }

        break;
    }
  },

  _animationAvgXYArray : function(arr) {
    var res = [], avg = 0, i;
    for (i = 0; i < arr.length; i++)
      avg += arr[i][1];
    avg = avg / arr.length;
    for (i = 0; i < arr.length; i++)
      res.push([ arr[i][0], avg ]);
    return res;
  },

  animationAvg : function(env, props, piece) {
    this._animationPiece(piece, props, piece.subSection);

    var avg = 0, i, l;
    switch (env.opt.type) {
      case 'line':
        switch (piece.subSection) {
          case 'Plot':
            if (!piece.paths) {
              // LINE
              piece.animation.startPath.push([ 'LINE', this._animationAvgXYArray(piece.path[0][1]), piece.path[0][2] ]);

            } else {
              // BAR
              l = 0;
              for (i = 0; i < piece.paths.length; i++)
                if (piece.paths[i].path) {
                  l ++;
                  avg += piece.paths[i].path[0][2];
                }
              avg = avg / l;
              for (i = 0; i < piece.paths.length; i++)
                if (piece.paths[i].path)
                  piece.paths[i].animation.startPath.push([ "RECT", piece.paths[i].path[0][1], avg, piece.paths[i].path[0][3], piece.paths[i].path[0][4] ]);
            }
            break;

          case 'Fill':
            piece.animation.startPath.push([ 'LINEAREA', this._animationAvgXYArray(piece.path[0][1]), this._animationAvgXYArray(piece.path[0][2]), piece.path[0][3] ]);

            break;

          case 'Dot':
            l = 0;
            for (i = 0; i < piece.paths.length; i++)
              if (piece.paths[i].path) {
                l ++;
                avg += piece.paths[i].path[0][2];
              }
            avg = avg / l;
            for (i = 0; i < piece.paths.length; i++)
              if (piece.paths[i].path)
                piece.paths[i].animation.startPath.push(['CIRCLE', piece.paths[i].path[0][1], avg, piece.paths[i].path[0][3]]);
            break;
        }
        break;

      case 'pie':
        var delta = 360 / piece.paths.length;

        if (piece.subSection == 'Plot')
          for (i = 0; i < piece.paths.length; i++)
            if (piece.paths[i].path && piece.paths[i].path[0][0] == 'SLICE')
              piece.paths[i].animation.startPath.push([ 'SLICE', piece.paths[i].path[0][1], piece.paths[i].path[0][2], piece.paths[i].path[0][3], piece.paths[i].path[0][4], i * delta, (i + 1) * delta ]);

        break;

      case 'funnel':
        alert('Unsupported animation AVG for funnel');
        break;

      case 'barline':
        alert('Unsupported animation AVG for barline');
        break;
    }
  },

  _animationRegXYArray : function(arr) {
    var res = [];
    var c = arr.length;
    var y1 = arr[0][1];
    var y2 = arr[c - 1][1];

    for (var i = 0; i < arr.length; i++)
      res.push([arr[i][0], y1 + (y2 - y1) / (c - 1) * i]);

    return res;
  },

  animationReg : function(env, props, piece) {
    this._animationPiece(piece, props, piece.subSection);
    var i, c, y1, y2;

    switch (env.opt.type) {
      case 'line':
        switch (piece.subSection) {
          case 'Plot':
            if (!piece.paths) {
              // LINE
              piece.animation.startPath.push([ 'LINE', this._animationRegXYArray(piece.path[0][1]), piece.path[0][2] ]);

            } else {
              // BAR
              c = piece.paths.length;
              if (c > 1) {
                for (i = 0; !piece.paths[i].path && i < piece.paths.length; i++) {}
                y1 = piece.paths[i].path ? common.getY(piece.paths[i].path[0]) : 0;
                for (i = piece.paths.length - 1; !piece.paths[i].path && i >= 0; i--) {}
                y2 = piece.paths[i].path ? common.getY(piece.paths[i].path[0]) : 0;

                for (i = 0; i < piece.paths.length; i++)
                  if (piece.paths[i].path)
                    piece.paths[i].animation.startPath.push([ "RECT", piece.paths[i].path[0][1], y1 + (y2 - y1) / (c - 1) * i, piece.paths[i].path[0][3], piece.paths[i].path[0][4] ]);
              }
            }
            break;

          case 'Fill':
            piece.animation.startPath.push([ 'LINEAREA', this._animationRegXYArray(piece.path[0][1]), this._animationRegXYArray(piece.path[0][2]), piece.path[0][3] ]);
            break;

          case 'Dot':
            c = piece.paths.length;
            if (c > 1) {
              for (i = 0; !piece.paths[i].path && i < piece.paths.length; i++) {}
              y1 = piece.paths[i].path ? common.getY(piece.paths[i].path[0]) : 0;
              for (i = piece.paths.length - 1; !piece.paths[i].path && i >= 0; i--) {}
              y2 = piece.paths[i].path ? common.getY(piece.paths[i].path[0]) : 0;

              for (i = 0; i < piece.paths.length; i++)
                if (piece.paths[i].path)
                  piece.paths[i].animation.startPath.push(['CIRCLE', piece.paths[i].path[0][1], y1 + (y2 - y1) / (c - 1) * i, piece.paths[i].path[0][3]]);
            }
            break;
        }
        break;

      case 'pie':
        alert('Unsupported animation REG for pie');
        break;

      case 'funnel':
        alert('Unsupported animation REG for funnel');
        break;

      case 'barline':
        alert('Unsupported animation REG for barline');
        break;
    }
  }
}

$.elycharts.featuresmanager.register($.elycharts.animationmanager, 10);

/***********************************************************************
 * FRAMEANIMATIONMANAGER
 **********************************************************************/

$.elycharts.frameanimationmanager = {

  beforeShow : function(env, pieces) {
    if (env.opt.features.frameAnimation.active)
      $(env.container.get(0)).css(env.opt.features.frameAnimation.cssFrom);
  },

  afterShow : function(env, pieces) {
    if (env.opt.features.frameAnimation.active)
      env.container.animate(env.opt.features.frameAnimation.cssTo, env.opt.features.frameAnimation.speed, env.opt.features.frameAnimation.easing);
  }
};

$.elycharts.featuresmanager.register($.elycharts.frameanimationmanager, 90);

})(jQuery);
/********* Source File: src/elycharts_manager_highlight.js*********/
/**********************************************************************
 * ELYCHARTS
 * A Javascript library to generate interactive charts with vectorial graphics.
 *
 * Copyright (c) 2010 Void Labs s.n.c. (http://void.it)
 * Licensed under the MIT (http://creativecommons.org/licenses/MIT/) license.
 **********************************************************************/

(function($) {

//var featuresmanager = $.elycharts.featuresmanager;
var common = $.elycharts.common;

/***********************************************************************
 * FEATURE: HIGHLIGHT
 *
 * Permette di evidenziare in vari modi l'area in cui si passa con il
 * mouse.
 **********************************************************************/

$.elycharts.highlightmanager = {

  removeHighlighted : function(env, full) {
    if (env.highlighted)
      while (env.highlighted.length > 0) {
        var o = env.highlighted.pop();
        if (o.piece) {
          if (full)
            common.animationStackPush(env, o.piece, o.piece.element, common.getPieceFullAttr(env, o.piece), o.cfg.restoreSpeed, o.cfg.restoreEasing, 0, true);
        } else
          o.element.remove();
      }
  },

  afterShow : function(env, pieces) {
    if (env.highlighted && env.highlighted.length > 0)
      this.removeHighlighted(env, false);
    env.highlighted = [];
  },

  onMouseOver : function(env, serie, index, mouseAreaData) {
    var path, element;
    // TODO Se non e' attivo l'overlay (per la serie o per tutto) e' inutile fare il resto

    // Cerco i piece da evidenziare (tutti quelli che sono costituiti da path multipli)
    for (var i = 0; i < mouseAreaData.pieces.length; i++)

      // Il loop sotto estrae solo i pieces con array di path (quindi non i line o i fill del linechart ... ma il resto si)
      if (mouseAreaData.pieces[i].section == 'Series' && mouseAreaData.pieces[i].paths
        && (!serie || mouseAreaData.pieces[i].serie == serie)
        && mouseAreaData.pieces[i].paths[index] && mouseAreaData.pieces[i].paths[index].element) {
        var piece = mouseAreaData.pieces[i].paths[index];
        element = piece.element;
        path = piece.path;
        var attr = common.getElementOriginalAttrs(element);
        var newattr = false; // In caso la geometria dell'oggetto Ã¨ modificata mediante attr (es: per circle) qui memorizza i nuovi attributi
        var props = serie ? mouseAreaData.props : common.areaProps(env, mouseAreaData.pieces[i].section, mouseAreaData.pieces[i].serie);
        var pelement, ppiece, ppath;
        if (path && props.highlight) {
          if (props.highlight.scale) {
            var scale = props.highlight.scale;
            if (typeof scale == 'number')
              scale = [scale, scale];

            if (path[0][0] == 'RECT') {
              var w = path[0][3] - path[0][1];
              var h = path[0][4] - path[0][2];
              path = [ [ 'RECT', path[0][1], path[0][2] - h * (scale[1] - 1), path[0][3] + w * (scale[0] - 1), path[0][4] ] ];
              common.animationStackPush(env, piece, element, common.getSVGProps(common.preparePathShow(env, path)), props.highlight.scaleSpeed, props.highlight.scaleEasing);
            }
            else if (path[0][0] == 'CIRCLE') {
              // I pass directly new radius
              newattr = {r : path[0][3] * scale[0]};
              common.animationStackPush(env, piece, element, newattr, props.highlight.scaleSpeed, props.highlight.scaleEasing);
            }
            else if (path[0][0] == 'SLICE') {
              // Per lo slice x e' il raggio, y e' l'angolo
              var d = (path[0][6] - path[0][5]) * (scale[1] - 1) / 2;
              if (d > 90)
                d = 90;
              path = [ [ 'SLICE', path[0][1], path[0][1], path[0][3] * scale[0], path[0][4], path[0][5] - d, path[0][6] + d ] ];
              common.animationStackPush(env, piece, element, common.getSVGProps(common.preparePathShow(env, path)), props.highlight.scaleSpeed, props.highlight.scaleEasing);

            } else if (env.opt.type == 'funnel') {
              var dx = (piece.rect[2] - piece.rect[0]) * (scale[0] - 1) / 2;
              var dy = (piece.rect[3] - piece.rect[1]) * (scale[1] - 1) / 2;

              // Specifico di un settore del funnel
              common.animationStackStart(env);
              path = [ common.movePath(env, [ path[0]], [-dx, -dy])[0],
                common.movePath(env, [ path[1]], [+dx, -dy])[0],
                common.movePath(env, [ path[2]], [+dx, +dy])[0],
                common.movePath(env, [ path[3]], [-dx, +dy])[0],
                path[4] ];
              common.animationStackPush(env, piece, element, common.getSVGProps(common.preparePathShow(env, path)), props.highlight.scaleSpeed, props.highlight.scaleEasing, 0, true);

              // Se c'e' un piece precedente lo usa, altrimenti cerca un topSector per la riduzione
              pelement = false;
              if (index > 0) {
                ppiece = mouseAreaData.pieces[i].paths[index - 1];
                pelement = ppiece.element;
                ppath = ppiece.path;
              } else {
                ppiece = common.findInPieces(mouseAreaData.pieces, 'Sector', 'top');
                if (ppiece) {
                  pelement = ppiece.element;
                  ppath = ppiece.path;
                }
              }
              if (pelement) {
                //pattr = common.getElementOriginalAttrs(pelement);
                ppath = [
                  ppath[0], ppath[1],
                  common.movePath(env, [ ppath[2]], [+dx, -dy])[0],
                  common.movePath(env, [ ppath[3]], [-dx, -dy])[0],
                  ppath[4] ];
                common.animationStackPush(env, ppiece, pelement, common.getSVGProps(common.preparePathShow(env, ppath)), props.highlight.scaleSpeed, props.highlight.scaleEasing, 0, true);
                env.highlighted.push({piece : ppiece, cfg : props.highlight});
              }

              // Se c'e' un piece successivo lo usa, altrimenti cerca un bottomSector per la riduzione
              pelement = false;
              if (index < mouseAreaData.pieces[i].paths.length - 1) {
                ppiece = mouseAreaData.pieces[i].paths[index + 1];
                pelement = ppiece.element;
                ppath = ppiece.path;
              } else {
                ppiece = common.findInPieces(mouseAreaData.pieces, 'Sector', 'bottom');
                if (ppiece) {
                  pelement = ppiece.element;
                  ppath = ppiece.path;
                }
              }
              if (pelement) {
                //var pattr = common.getElementOriginalAttrs(pelement);
                ppath = [
                  common.movePath(env, [ ppath[0]], [-dx, +dy])[0],
                  common.movePath(env, [ ppath[1]], [+dx, +dy])[0],
                  ppath[2], ppath[3],
                  ppath[4] ];
                common.animationStackPush(env, ppiece, pelement, common.getSVGProps(common.preparePathShow(env, ppath)), props.highlight.scaleSpeed, props.highlight.scaleEasing, 0, true);
                env.highlighted.push({piece : ppiece, cfg : props.highlight});
              }

              common.animationStackEnd(env);
            }
            /* Con scale non va bene
            if (!attr.scale)
              attr.scale = [1, 1];
            element.attr({scale : [scale[0], scale[1]]}); */
          }
          if (props.highlight.newProps) {
            for (var a in props.highlight.newProps)
              if (typeof attr[a] == 'undefined')
                attr[a] = false;
            common.animationStackPush(env, piece, element, props.highlight.newProps);
          }
          if (props.highlight.move) {
            var offset = $.isArray(props.highlight.move) ? props.highlight.move : [props.highlight.move, 0];
            path = common.movePath(env, path, offset);
            common.animationStackPush(env, piece, element, common.getSVGProps(common.preparePathShow(env, path)), props.highlight.moveSpeed, props.highlight.moveEasing);
          }

          //env.highlighted.push({element : element, attr : attr});
          env.highlighted.push({piece : piece, cfg : props.highlight});

          if (props.highlight.overlayProps) {
            // NOTA: path e' il path modificato dai precedenti (cosi' l'overlay tiene conto della cosa), deve guardare anche a newattr
            //BIND: mouseAreaData.listenerDisabled = true;
            element = common.showPath(env, path);
            if (newattr)
              element.attr(newattr);
            element.attr(props.highlight.overlayProps);
            //BIND: $(element.node).unbind().mouseover(mouseAreaData.mouseover).mouseout(mouseAreaData.mouseout);
            // Se metto immediatamente il mouseAreaData.listenerDisabled poi va comunque un mouseout dalla vecchia area e va
            // in loop. TODO Rivedere e sistemare anche per tooltip
            //BIND: setTimeout(function() { mouseAreaData.listenerDisabled = false; }, 10);
            attr = false;
            env.highlighted.push({element : element, attr : attr, cfg : props.highlight});
          }
        }
      }

    if (env.opt.features.highlight.indexHighlight && env.opt.type == 'line') {
      var t = env.opt.features.highlight.indexHighlight;
      if (t == 'auto')
        t = (env.indexCenter == 'bar' ? 'bar' : 'line');

      var delta1 = (env.opt.width - env.opt.margins[3] - env.opt.margins[1]) / (env.opt.labels.length > 0 ? env.opt.labels.length : 1);
      var delta2 = (env.opt.width - env.opt.margins[3] - env.opt.margins[1]) / (env.opt.labels.length > 1 ? env.opt.labels.length - 1 : 1);
      var lineCenter = true;

      switch (t) {
        case 'bar':
          path = [ ['RECT', env.opt.margins[3] + index * delta1, env.opt.margins[0] ,
            env.opt.margins[3] + (index + 1) * delta1, env.opt.height - env.opt.margins[2] ] ];
          break;

        case 'line':
          lineCenter = false;
        case 'barline':
          var x = Math.round((lineCenter ? delta1 / 2 : 0) + env.opt.margins[3] + index * (lineCenter ? delta1 : delta2));
          path = [[ 'M', x, env.opt.margins[0]], ['L', x, env.opt.height - env.opt.margins[2]]];
      }
      if (path) {
        //BIND: mouseAreaData.listenerDisabled = true;
        element = common.showPath(env, path).attr(env.opt.features.highlight.indexHighlightProps);
        //BIND: $(element.node).unbind().mouseover(mouseAreaData.mouseover).mouseout(mouseAreaData.mouseout);
        //BIND: setTimeout(function() { mouseAreaData.listenerDisabled = false; }, 10);
        env.highlighted.push({element : element, attr : false, cfg : env.opt.features.highlight});
      }
    }
  },

  onMouseOut : function(env, serie, index, mouseAreaData) {
    this.removeHighlighted(env, true);
  }

};

$.elycharts.featuresmanager.register($.elycharts.highlightmanager, 21);

})(jQuery);
/********* Source File: src/elycharts_manager_label.js*********/
/**********************************************************************
 * ELYCHARTS
 * A Javascript library to generate interactive charts with vectorial graphics.
 *
 * Copyright (c) 2010 Void Labs s.n.c. (http://void.it)
 * Licensed under the MIT (http://creativecommons.org/licenses/MIT/) license.
 **********************************************************************/

(function($) {

//var featuresmanager = $.elycharts.featuresmanager;
var common = $.elycharts.common;

/***********************************************************************
 * FEATURE: LABELS
 *
 * Permette di visualizzare in vari modi le label del grafico.
 * In particolare per pie e funnel permette la visualizzazione all'interno
 * delle fette.
 * Per i line chart le label sono visualizzate giÃ  nella gestione assi.
 *
 * TODO:
 * - Comunque per i line chart si potrebbe gestire la visualizzazione
 *   all'interno delle barre, o sopra i punti.
 **********************************************************************/

$.elycharts.labelmanager = {

  beforeShow : function(env, pieces) {

    if (!common.executeIfChanged(env, ['labels', 'values', 'series']))
      return;

    if (env.opt.labels && (env.opt.type == 'pie' || env.opt.type == 'funnel')) {
      var /*lastSerie = false, */lastIndex = false;
      var paths;

      for (var i = 0; i < pieces.length; i++) {
        if (pieces[i].section == 'Series' && pieces[i].subSection == 'Plot') {
          var props = common.areaProps(env, 'Series', pieces[i].serie);
          if (env.emptySeries && env.opt.series.empty)
            props.label = $.extend(true, props.label, env.opt.series.empty.label);
          if (props && props.label && props.label.active) {
            paths = [];
            for (var index = 0; index < pieces[i].paths.length; index++)
              if (pieces[i].paths[index].path) {
                //lastSerie = pieces[i].serie;
                lastIndex = index;
                paths.push(this.showLabel(env, pieces[i], pieces[i].paths[index], pieces[i].serie, index, pieces));
              } else
                paths.push({ path : false, attr : false });
            pieces.push({ section : pieces[i].section, serie : pieces[i].serie, subSection : 'Label', paths: paths });
          }
        }
        else if (pieces[i].section == 'Sector' && pieces[i].serie == 'bottom' && !pieces[i].subSection && lastIndex < env.opt.labels.length - 1) {
          paths = [];
          paths.push(this.showLabel(env, pieces[i], pieces[i], 'Series', env.opt.labels.length - 1, pieces));
          pieces.push({ section : pieces[i].section, serie : pieces[i].serie, subSection : 'Label', paths: paths });
        }
      }

    }
  },

  showLabel : function(env, piece, path, serie, index, pieces) {
    var pp = common.areaProps(env, 'Series', serie, index);
    if (env.opt.labels[index] || pp.label.label) {
      var p = path;
      var label = pp.label.label ? pp.label.label : env.opt.labels[index];
      var center = common.getCenter(p, pp.label.offset);
      if (!pp.label.html) {
        var attr = pp.label.props;
        if (pp.label.frameAnchor) {
          attr = common._clone(pp.label.props);
          attr['text-anchor'] = pp.label.frameAnchor[0];
          attr['alignment-baseline'] = pp.label.frameAnchor[1];
        }
        /*pieces.push({
          path : [ [ 'TEXT', label, center[0], center[1] ] ], attr : attr,
          section: 'Series', serie : serie, index : index, subSection : 'Label'
        });*/
        return { path : [ [ 'TEXT', label, center[0], center[1] ] ], attr : attr };

      } else {
        var opacity = 1;
        var style = common._clone(pp.label.style);
        var set_opacity = (typeof style.opacity != 'undefined')
        if (set_opacity) {
          opacity = style.opacity;
          style.opacity = 0;
        }
        style.position = 'absolute';
        style['z-index'] = 25;

        var el;
        if (typeof label == 'string')
          el = $('<div>' + label + '</div>').css(style).prependTo(env.container);
        else
          el = $(label).css(style).prependTo(env.container);

        // Centramento corretto label
        if (env.opt.features.debug.active && el.height() == 0)
          alert('DEBUG: Al gestore label e\' stata passata una label ancora senza dimensioni, quindi ancora non disegnata. Per questo motivo il posizionamento potrebbe non essere correto.');
        var posX = center[0];
        var posY = center[1];
        if (!pp.label.frameAnchor || pp.label.frameAnchor[0] == 'middle')
          posX -= el.width() / 2;
        else if (pp.label.frameAnchor && pp.label.frameAnchor[0] == 'end')
          posX -= el.width();
        if (!pp.label.frameAnchor || pp.label.frameAnchor[1] == 'middle')
          posY -= el.height() / 2;
        else if (pp.label.frameAnchor && pp.label.frameAnchor[1] == 'top')
          posY -= el.height();
        if (set_opacity)
          el.css({ margin: posY + 'px 0 0 ' + posX + 'px', opacity : opacity});
        else
          el.css({ margin: posY + 'px 0 0 ' + posX + 'px'});

        /*pieces.push({
          path : [ [ 'DOMELEMENT', el ] ], attr : false,
          section: 'Series', serie : serie, index : index, subSection : 'Label'
        });*/
        return { path : [ [ 'DOMELEMENT', el ] ], attr : false };

      }
    }
    return false;
  }
}

$.elycharts.featuresmanager.register($.elycharts.labelmanager, 5);

})(jQuery);
/********* Source File: src/elycharts_manager_legend.js*********/
/**********************************************************************
 * ELYCHARTS
 * A Javascript library to generate interactive charts with vectorial graphics.
 *
 * Copyright (c) 2010 Void Labs s.n.c. (http://void.it)
 * Licensed under the MIT (http://creativecommons.org/licenses/MIT/) license.
 **********************************************************************/

(function($) {

//var featuresmanager = $.elycharts.featuresmanager;
var common = $.elycharts.common;

/***********************************************************************
 * FEATURE: LEGEND
 **********************************************************************/

$.elycharts.legendmanager = {

  afterShow : function(env, pieces) {
    if (!env.opt.legend || env.opt.legend.length == 0)
      return;

    var props = env.opt.features.legend;

    if (props.x == 'auto') {
      var autox = 1;
      props.x = 0;
    }
    if (props.width == 'auto') {
      var autowidth = 1;
      props.width = env.opt.width;
    }

    var borderPath = [ [ 'RECT', props.x, props.y, props.x + props.width, props.y + props.height, props.r ] ];
    var border = common.showPath(env, borderPath).attr(props.borderProps);
    if (autox || autowidth)
      border.hide();

    var wauto = 0;
    var items = [];
    // env.opt.legend normalmente Ã¨ { serie : 'Legend', ... }, per i pie invece { serie : ['Legend', ...], ... }
    var legendCount = 0;
    var serie, data, h, w, x, y, xd;
    for (serie in env.opt.legend) {
      if (env.opt.type != 'pie')
        legendCount ++;
      else
        legendCount += env.opt.legend[serie].length;
    }
    var i = 0;
    for (serie in env.opt.legend) {
      if (env.opt.type != 'pie')
        data = [ env.opt.legend[serie] ];
      else
        data = env.opt.legend[serie];

      for (var j = 0; j < data.length; j++) {
        var sprops = common.areaProps(env, 'Series', serie, env.opt.type == 'pie' ? j : false);
        var dprops = $.extend(true, {}, props.dotProps);
        if (sprops.legend && sprops.legend.dotProps)
          dprops = $.extend(true, dprops, sprops.legend.dotProps);
        if (!dprops.fill && env.opt.type == 'pie') {
          if (sprops.color)
            dprops.fill = sprops.color;
          if (sprops.plotProps && sprops.plotProps.fill)
            dprops.fill = sprops.plotProps.fill;
        }
        var dtype = sprops.legend && sprops.legend.dotType ? sprops.legend.dotType : props.dotType;
        var dwidth = sprops.legend && sprops.legend.dotWidth ? sprops.legend.dotWidth : props.dotWidth;
        var dheight = sprops.legend && sprops.legend.dotHeight ? sprops.legend.dotHeight : props.dotHeight;
        var dr = sprops.legend && sprops.legend.dotR ? sprops.legend.dotR : props.dotR;
        var tprops = sprops.legend && sprops.legend.textProps ? sprops.legend.textProps : props.textProps;

        if (!props.horizontal) {
          // Posizione dell'angolo in alto a sinistra
          h = (props.height - props.margins[0] - props.margins[2]) / legendCount;
          w = props.width - props.margins[1] - props.margins[3];
          x = Math.floor(props.x + props.margins[3]);
          y = Math.floor(props.y + props.margins[0] + h * i);
        } else {
          h = props.height - props.margins[0] - props.margins[2];
          if (!props.itemWidth || props.itemWidth == 'fixed') {
            w = (props.width - props.margins[1] - props.margins[3]) / legendCount;
            x = Math.floor(props.x + props.margins[3] + w * i);
          } else {
            w = (props.width - props.margins[1] - props.margins[3]) - wauto;
            x = props.x + props.margins[3] + wauto;
          }
          y = Math.floor(props.y + props.margins[0]);
        }

        if (dtype == "rect") {
          items.push(common.showPath(env, [ [ 'RECT', props.dotMargins[0] + x, y + Math.floor((h - dheight) / 2), props.dotMargins[0] + x + dwidth, y + Math.floor((h - dheight) / 2) + dheight, dr ] ]).attr(dprops));
          xd = props.dotMargins[0] + dwidth + props.dotMargins[1];
        } else if (dtype == "circle") {
          items.push(common.showPath(env, [ [ 'CIRCLE', props.dotMargins[0] + x + dr, y + (h / 2), dr ] ]).attr(dprops));
          xd = props.dotMargins[0] + dr * 2 + props.dotMargins[1];
        }

        var text = data[j];
        var t = common.showPath(env, [ [ 'TEXT', text, x + xd, y + Math.ceil(h / 2) + (/msie/.test(navigator.userAgent.toLowerCase()) ? 2 : 0) ] ]).attr({"text-anchor" : "start"}).attr(tprops); //.hide();
        items.push(t);
        while (t.getBBox().width > (w - xd) && t.getBBox().width > 10) {
          text = text.substring(0, text.length - 1);
          t.attr({text : text});
        }
        t.show();

        if (props.horizontal && props.itemWidth == 'auto')
          wauto += xd + t.getBBox().width + 4;
        else if (!props.horizontal && autowidth)
          wauto = t.getBBox().width + xd > wauto ? t.getBBox().width + xd : wauto;
        else
          wauto += w;

        i++;
      }
    }

    if (autowidth)
      props.width = wauto + props.margins[3] + props.margins[1] - 1;
    if (autox) {
      props.x = Math.floor((env.opt.width - props.width) / 2);
      for (i in items) {
        if (items[i].attrs.x)
          items[i].attr('x', items[i].attrs.x + props.x);
        else
          items[i].attr('path', common.movePath(env, items[i].attrs.path, [props.x, 0]));
      }
    }
    if (autowidth || autox) {
      borderPath = [ [ 'RECT', props.x, props.y, props.x + props.width, props.y + props.height, props.r ] ];
      border.attr(common.getSVGProps(common.preparePathShow(env, borderPath)));
      //border.attr({path : common.preparePathShow(env, common.getSVGPath(borderPath))});
      border.show();
    }
  }
}

$.elycharts.featuresmanager.register($.elycharts.legendmanager, 90);

})(jQuery);
/********* Source File: src/elycharts_manager_mouse.js*********/
/**********************************************************************
 * ELYCHARTS
 * A Javascript library to generate interactive charts with vectorial graphics.
 *
 * Copyright (c) 2010 Void Labs s.n.c. (http://void.it)
 * Licensed under the MIT (http://creativecommons.org/licenses/MIT/) license.
 **********************************************************************/

(function($) {

var featuresmanager = $.elycharts.featuresmanager;
var common = $.elycharts.common;

/***********************************************************************
 * MOUSEMANAGER
 **********************************************************************/

$.elycharts.mousemanager = {

  afterShow : function(env, pieces) {
    if (!env.opt.interactive)
      return;

    if (env.mouseLayer) {
      env.mouseLayer.remove();
      env.mouseLayer = null;
      env.mousePaper.remove();
      env.mousePaper = null;
      env.mouseTimer = null;
      env.mouseAreas = null;
      // Meglio fare anche l'unbind???
    }

    env.mouseLayer = $('<div></div>').css({position : 'absolute', 'z-index' : 20, opacity : 0}).prependTo(env.container);
    env.mousePaper = common._RaphaelInstance(env.mouseLayer.get(0), env.opt.width, env.opt.height);
    var paper = env.mousePaper;

    if (env.opt.features.debug.active && typeof DP_Debug != 'undefined') {
      env.paper.text(env.opt.width, env.opt.height - 5, 'DEBUG').attr({ 'text-anchor' : 'end', stroke: 'red', opacity: .1 });
      paper.text(env.opt.width, env.opt.height - 5, 'DEBUG').attr({ 'text-anchor' : 'end', stroke: 'red', opacity: .1 }).click(function() {
        DP_Debug.dump(env.opt, '', false, 4);
      });
    }

    var i, j;

    // Adding mouseover only in right area, based on pieces
    env.mouseAreas = [];
    if (env.opt.features.mousearea.type == 'single') {
      // SINGLE: Every serie's index is an area
      for (i = 0; i < pieces.length; i++) {
        if (pieces[i].mousearea) {
          // pathstep
          if (!pieces[i].paths) {
            // path standard, generating an area for each point
            if (pieces[i].path.length >= 1 && (pieces[i].path[0][0] == 'LINE' || pieces[i].path[0][0] == 'LINEAREA'))
              for (j = 0; j < pieces[i].path[0][1].length; j++) {
                var props = common.areaProps(env, pieces[i].section, pieces[i].serie);
                if (props.mouseareaShowOnNull || pieces[i].section != 'Series' || env.opt.values[pieces[i].serie][j] != null)
                  env.mouseAreas.push({
                    path : [ [ 'CIRCLE', pieces[i].path[0][1][j][0], pieces[i].path[0][1][j][1], 10 ] ],
                    piece : pieces[i],
                    pieces : pieces,
                    index : j,
                    props : props
                  });
              }

            else // Code below is only for standard path - it should be useless now (now there are only LINE and LINEAREA)
              // TODO DELETE
              for (j = 0; j < pieces[i].path.length; j++) {
                env.mouseAreas.push({
                  path : [ [ 'CIRCLE', common.getX(pieces[i].path[j]), common.getY(pieces[i].path[j]), 10 ] ],
                  piece : pieces[i],
                  pieces : pieces,
                  index : j,
                  props : common.areaProps(env, pieces[i].section, pieces[i].serie)
                });
              }

          // paths
          } else if (pieces[i].paths) {
            // Set of paths (bar graph?), generating overlapped areas
            for (j = 0; j < pieces[i].paths.length; j++)
              if (pieces[i].paths[j].path)
                env.mouseAreas.push({
                  path : pieces[i].paths[j].path,
                  piece : pieces[i],
                  pieces : pieces,
                  index : j,
                  props : common.areaProps(env, pieces[i].section, pieces[i].serie)
                });
          }
        }
      }
    } else {
      // INDEX: Each index (in every serie) is an area
      var indexCenter = env.opt.features.mousearea.indexCenter;
      if (indexCenter == 'auto')
        indexCenter = env.indexCenter;
      var start, delta;
      if (indexCenter == 'bar') {
        delta = (env.opt.width - env.opt.margins[3] - env.opt.margins[1]) / (env.opt.labels.length > 0 ? env.opt.labels.length : 1);
        start = env.opt.margins[3];
      } else {
        delta = (env.opt.width - env.opt.margins[3] - env.opt.margins[1]) / (env.opt.labels.length > 1 ? env.opt.labels.length - 1 : 1);
        start = env.opt.margins[3] - delta / 2;
      }

      for (var index in env.opt.labels) {
        env.mouseAreas.push({
          path : [ [ 'RECT', start + index * delta, env.opt.margins[0], start + (index + 1) * delta, env.opt.height - env.opt.margins[2] ] ],
          piece : false,
          pieces : pieces,
          index : parseInt(index),
          props : env.opt.defaultSeries // TODO common.areaProps(env, 'Plot')
        });
      }
    }

    var syncenv = false;
    if (!env.opt.features.mousearea.syncTag) {
      env.mouseareaenv = { chartEnv : false, mouseObj : false, caller : false, inArea : -1, timer : false };
      syncenv = env.mouseareaenv;
    } else {
      if (!$.elycharts.mouseareaenv)
        $.elycharts.mouseareaenv = {};
      if (!$.elycharts.mouseareaenv[env.opt.features.mousearea.syncTag])
        $.elycharts.mouseareaenv[env.opt.features.mousearea.syncTag] = { chartEnv : false, mouseObj : false, caller : false, inArea : -1, timer : false };
      syncenv = $.elycharts.mouseareaenv[env.opt.features.mousearea.syncTag];
    }
    for (i = 0; i < env.mouseAreas.length; i++) {
      env.mouseAreas[i].area = common.showPath(env, env.mouseAreas[i].path, paper).attr({stroke: "none", fill: "#fff", opacity: 0});

      (function(env, obj, objidx, caller, syncenv) {
        var piece = obj.piece;
        var index = obj.index;

        obj.mouseover = function(e) {
          //BIND: if (obj.listenerDisabled) return;
          obj.event = e;
          clearTimeout(syncenv.timer);
          caller.onMouseOverArea(env, piece, index, obj);

          if (syncenv.chartEnv && syncenv.chartEnv.id != env.id) {
            // Chart changed, removing old one
            syncenv.caller.onMouseExitArea(syncenv.chartEnv, syncenv.mouseObj.piece, syncenv.mouseObj.index, syncenv.mouseObj);
            caller.onMouseEnterArea(env, piece, index, obj);
          }
          else if (syncenv.inArea != objidx) {
            if (syncenv.inArea < 0)
              caller.onMouseEnterArea(env, piece, index, obj);
            else
              caller.onMouseChangedArea(env, piece, index, obj);
          }
          syncenv.chartEnv = env;
          syncenv.mouseObj = obj;
          syncenv.caller = caller;
          syncenv.inArea = objidx;
        };
        obj.mouseout = function(e) {
          //BIND: if (obj.listenerDisabled) return;
          obj.event = e;
          clearTimeout(syncenv.timer);
          caller.onMouseOutArea(env, piece, index, obj);
          syncenv.timer = setTimeout(function() {
            syncenv.timer = false;
            caller.onMouseExitArea(env, piece, index, obj);
            syncenv.chartEnv = false;
            syncenv.inArea = -1;
          }, env.opt.features.mousearea.areaMoveDelay);
        };

        $(obj.area.node).mouseover(obj.mouseover);
        $(obj.area.node).mouseout(obj.mouseout);
      })(env, env.mouseAreas[i], i, this, syncenv);
    }
  },

  // Called when mouse enter an area
  onMouseOverArea : function(env, piece, index, mouseAreaData) {
    //console.warn('over', piece.serie, index);
    if (env.opt.features.mousearea.onMouseOver)
      env.opt.features.mousearea.onMouseOver(env, mouseAreaData.piece ? mouseAreaData.piece.serie : false, mouseAreaData.index, mouseAreaData);
    featuresmanager.onMouseOver(env, mouseAreaData.piece ? mouseAreaData.piece.serie : false, mouseAreaData.index, mouseAreaData);
  },

  // Called when mouse exit from an area
  onMouseOutArea : function(env, piece, index, mouseAreaData) {
    //console.warn('out', piece.serie, index);
    if (env.opt.features.mousearea.onMouseOut)
      env.opt.features.mousearea.onMouseOut(env, mouseAreaData.piece ? mouseAreaData.piece.serie : false, mouseAreaData.index, mouseAreaData);
    featuresmanager.onMouseOut(env, mouseAreaData.piece ? mouseAreaData.piece.serie : false, mouseAreaData.index, mouseAreaData);
  },

  // Called when mouse enter an area from empty space (= it was in no area before)
  onMouseEnterArea : function(env, piece, index, mouseAreaData) {
    //console.warn('enter', piece.serie, index);
    if (env.opt.features.mousearea.onMouseEnter)
      env.opt.features.mousearea.onMouseEnter(env, mouseAreaData.piece ? mouseAreaData.piece.serie : false, mouseAreaData.index, mouseAreaData);
    featuresmanager.onMouseEnter(env, mouseAreaData.piece ? mouseAreaData.piece.serie : false, mouseAreaData.index, mouseAreaData);
  },

  // Called when mouse enter an area and it was on another area
  onMouseChangedArea : function(env, piece, index, mouseAreaData) {
    //console.warn('changed', piece.serie, index);
    if (env.opt.features.mousearea.onMouseChanged)
      env.opt.features.mousearea.onMouseChanged(env, mouseAreaData.piece ? mouseAreaData.piece.serie : false, mouseAreaData.index, mouseAreaData);
    featuresmanager.onMouseChanged(env, mouseAreaData.piece ? mouseAreaData.piece.serie : false, mouseAreaData.index, mouseAreaData);
  },

  // Called when mouse leaves an area and does not enter in another one (timeout check)
  onMouseExitArea : function(env, piece, index, mouseAreaData) {
    //console.warn('exit', piece.serie, index);
    if (env.opt.features.mousearea.onMouseExit)
      env.opt.features.mousearea.onMouseExit(env, mouseAreaData.piece ? mouseAreaData.piece.serie : false, mouseAreaData.index, mouseAreaData);
    featuresmanager.onMouseExit(env, mouseAreaData.piece ? mouseAreaData.piece.serie : false, mouseAreaData.index, mouseAreaData);
  }

}

$.elycharts.featuresmanager.register($.elycharts.mousemanager, 0);

})(jQuery);
/********* Source File: src/elycharts_manager_tooltip.js*********/
/**********************************************************************
 * ELYCHARTS
 * A Javascript library to generate interactive charts with vectorial graphics.
 *
 * Copyright (c) 2010 Void Labs s.n.c. (http://void.it)
 * Licensed under the MIT (http://creativecommons.org/licenses/MIT/) license.
 **********************************************************************/

(function($) {

//var featuresmanager = $.elycharts.featuresmanager;
var common = $.elycharts.common;

/***********************************************************************
 * FEATURE: TOOLTIP
 **********************************************************************/

$.elycharts.tooltipmanager = {

  afterShow : function(env, pieces) {
    if (env.tooltipContainer) {
      env.tooltipFrame.remove();
      env.tooltipFrame = null;
      env.tooltipFrameElement = null;
      env.tooltipContent.remove();
      env.tooltipContent = null;
      env.tooltipContainer.remove();
      env.tooltipContainer = null;
    }

    if (!$.elycharts.tooltipid)
      $.elycharts.tooltipid = 0;
    $.elycharts.tooltipid ++;

    // Preparo il tooltip
    env.tooltipContainer = $('<div id="elycharts_tooltip_' + $.elycharts.tooltipid + '" style="position: absolute; top: 100; left: 100; z-index: 10; overflow: hidden; white-space: nowrap; display: none"><div id="elycharts_tooltip_' + $.elycharts.tooltipid + '_frame" style="position: absolute; top: 0; left: 0; z-index: -1"></div><div id="elycharts_tooltip_' + $.elycharts.tooltipid + '_content" style="cursor: default"></div></div>').appendTo(document.body);
    env.tooltipFrame = common._RaphaelInstance('elycharts_tooltip_' + $.elycharts.tooltipid + '_frame', 500, 500);
    env.tooltipContent = $('#elycharts_tooltip_' + $.elycharts.tooltipid + '_content');
  },

  _prepareShow : function(env, props, mouseAreaData, tip) {
    if (env.tooltipFrameElement)
      env.tooltipFrameElement.attr(props.frameProps);
    if (props.padding)
      env.tooltipContent.css({ padding : props.padding[0] + 'px ' + props.padding[1] + 'px' });
    env.tooltipContent.css(props.contentStyle);
    env.tooltipContent.html(tip);

    //BIND: env.tooltipContainer.unbind().mouseover(mouseAreaData.mouseover).mouseout(mouseAreaData.mouseout);

    // WARN: Prendendo env.paper.canvas non va bene...
    //var offset = $(env.paper.canvas).offset();
    var offset = $(env.container).offset();

    if (env.opt.features.tooltip.fixedPos) {
      offset.top += env.opt.features.tooltip.fixedPos[1];
      offset.left += env.opt.features.tooltip.fixedPos[0];

    } else {
      var coord = this.getXY(env, props, mouseAreaData);
      if (!coord[2]) {
        offset.left += coord[0];
        while (offset.top + coord[1] < 0)
          coord[1] += 20;
        offset.top += coord[1];
      } else {
        offset.left = coord[0];
        offset.top = coord[1];
      }
    }

    return { top : offset.top, left : offset.left };
  },

  /**
   * Ritorna [x, y] oppure [x, y, true] se le coordinate sono relative alla pagina (e non al grafico)
   */
  getXY : function(env, props, mouseAreaData) {
    // NOTA Posizione mouse: mouseAreaData.event.pageX/pageY
    var x = 0, y = 0;
    if (mouseAreaData.path[0][0] == 'RECT') {
      // L'area e' su un rettangolo (un bar o un indice completo), il tooltip lo faccio subito sopra
      // Nota: per capire se e' sull'indice completo basta guardare mouseAreaData.piece == null
      x = common.getX(mouseAreaData.path[0]) - props.offset[1];
      y = common.getY(mouseAreaData.path[0]) - props.height - props.offset[0];
    }
    else if (mouseAreaData.path[0][0] == 'CIRCLE') {
      // L'area e' su un cerchio (punto di un line)
      x = common.getX(mouseAreaData.path[0]) - props.offset[1];
      y = common.getY(mouseAreaData.path[0]) - props.height - props.offset[0];
    }
    else if (mouseAreaData.path[0][0] == 'SLICE') {
      // L'area Ã¨ su una fetta di torta (pie)
      var path = mouseAreaData.path[0];

      // Genera la posizione del tip considerando che deve stare all'interno di un cerchio che Ã¨ sempre dalla parte opposta dell'area
      // e deve essere il piu' vicino possibile all'area
      var w = props.width && props.width != 'auto' ? props.width : 100;
      var h = props.height && props.height != 'auto' ? props.height : 100;
      // Raggio del cerchio che contiene il tip
      var cr = Math.sqrt(Math.pow(w,2) + Math.pow(h,2)) / 2;
      if (cr > env.opt.r)
              cr = env.opt.r;

      var tipangle = path[5] + (path[6] - path[5]) / 2 + 180;
      var rad = Math.PI / 180;
      x = path[1] + cr * Math.cos(- tipangle * rad) - w / 2;
      y = path[2] + cr * Math.sin(- tipangle * rad) - h / 2;
    }
    else if (mouseAreaData.piece && mouseAreaData.piece.paths && mouseAreaData.index >= 0 && mouseAreaData.piece.paths[mouseAreaData.index] && mouseAreaData.piece.paths[mouseAreaData.index].rect) {
      // L'area ha una forma complessa, ma abbiamo il rettangolo di contenimento (funnel)
      var rect = mouseAreaData.piece.paths[mouseAreaData.index].rect;
      x = rect[0] - props.offset[1];
      y = rect[1] - props.height - props.offset[0];
    }

    if (env.opt.features.tooltip.positionHandler)
      return env.opt.features.tooltip.positionHandler(env, props, mouseAreaData, x, y);
    else
      return [x, y];
  },

  getTip : function(env, serie, index) {
    var tip = false;
    if (env.opt.tooltips) {
      if (typeof env.opt.tooltips == 'function')
        tip = env.opt.tooltips(env, serie, index, serie && env.opt.values[serie] && env.opt.values[serie][index] ? env.opt.values[serie][index] : false, env.opt.labels && env.opt.labels[index] ? env.opt.labels[index] : false);
      else {
        if (serie && env.opt.tooltips[serie] && env.opt.tooltips[serie][index])
          tip = env.opt.tooltips[serie][index];
        else if (!serie && env.opt.tooltips[index])
          tip = env.opt.tooltips[index];
      }
    }
    return tip;
  },

  onMouseEnter : function(env, serie, index, mouseAreaData) {
    var props = mouseAreaData.props.tooltip;
    if (env.emptySeries && env.opt.series.empty)
      props = $.extend(true, props, env.opt.series.empty.tooltip);
    if (!props || !props.active)
      return false;

    var tip = this.getTip(env, serie, index);
    if (!tip)
      return this.onMouseExit(env, serie, index, mouseAreaData);

    //if (!env.opt.tooltips || (serie && (!env.opt.tooltips[serie] || !env.opt.tooltips[serie][index])) || (!serie && !env.opt.tooltips[index]))
    //  return this.onMouseExit(env, serie, index, mouseAreaData);
    //var tip = serie ? env.opt.tooltips[serie][index] : env.opt.tooltips[index];

    // Il dimensionamento del tooltip e la view del frame SVG, lo fa solo se width ed height sono specificati
    if (props.width && props.width != 'auto' && props.height && props.height != 'auto') {
      var delta = props.frameProps && props.frameProps['stroke-width'] ? props.frameProps['stroke-width'] : 0;
      env.tooltipContainer.width(props.width + delta + 1).height(props.height + delta + 1);
      if (!env.tooltipFrameElement && props.frameProps)
        env.tooltipFrameElement = env.tooltipFrame.rect(delta / 2, delta / 2, props.width, props.height, props.roundedCorners);
    }

    env.tooltipContainer.css(this._prepareShow(env, props, mouseAreaData, tip)).fadeIn(env.opt.features.tooltip.fadeDelay);

    return true;
  },

  onMouseChanged : function(env, serie, index, mouseAreaData) {
    var props = mouseAreaData.props.tooltip;
    if (env.emptySeries && env.opt.series.empty)
      props = $.extend(true, props, env.opt.series.empty.tooltip);
    if (!props || !props.active)
      return false;

    var tip = this.getTip(env, serie, index);
    if (!tip)
      return this.onMouseExit(env, serie, index, mouseAreaData);

    /*if (!env.opt.tooltips || (serie && (!env.opt.tooltips[serie] || !env.opt.tooltips[serie][index])) || (!serie && !env.opt.tooltips[index]))
      return this.onMouseExit(env, serie, index, mouseAreaData);
    var tip = serie ? env.opt.tooltips[serie][index] : env.opt.tooltips[index];*/

    env.tooltipContainer.clearQueue();
    // Nota: Non passo da animationStackPush, i tooltip non sono legati a piece
    env.tooltipContainer.animate(this._prepareShow(env, props, mouseAreaData, tip), env.opt.features.tooltip.moveDelay, 'linear' /*swing*/);

    return true;
  },

  onMouseExit : function(env, serie, index, mouseAreaData) {
    var props = mouseAreaData.props.tooltip;
    if (env.emptySeries && env.opt.series.empty)
      props = $.extend(true, props, env.opt.series.empty.tooltip);
    if (!props || !props.active)
      return false;

    //env.tooltipContainer.unbind();
    env.tooltipContainer.fadeOut(env.opt.features.tooltip.fadeDelay);

    return true;
  }
}

$.elycharts.featuresmanager.register($.elycharts.tooltipmanager, 20);

})(jQuery);
/********* Source File: src/elycharts_chart_line.js*********/
/**********************************************************************
 * ELYCHARTS
 * A Javascript library to generate interactive charts with vectorial graphics.
 *
 * Copyright (c) 2010 Void Labs s.n.c. (http://void.it)
 * Licensed under the MIT (http://creativecommons.org/licenses/MIT/) license.
 **********************************************************************/

(function($) {

var featuresmanager = $.elycharts.featuresmanager;
var common = $.elycharts.common;

/***********************************************************************
 * CHART: LINE/BAR
 **********************************************************************/

$.elycharts.line = {
  init : function($env) {
  },

  draw : function(env) {
    if (common.executeIfChanged(env, ['values', 'series'])) {
      env.plots = {};
      env.axis = { x : {} };
      env.barno = 0;
      env.indexCenter = 'line';
    }

    var opt = env.opt;
    var plots = env.plots;
    var axis = env.axis;
    var paper = env.paper;

    var values = env.opt.values;
    var labels = env.opt.labels;
    var i, cum, props, serie, plot, labelsCount;

    // Valorizzazione di tutte le opzioni utili e le impostazioni interne di ogni grafico e dell'ambiente di lavoro
    if (common.executeIfChanged(env, ['values', 'series'])) {
      var idx = 0;
      var prevVisibleSerie = false;
      for (serie in values) {
        plot = {
          index : idx,
          type : false,
          visible : false
        };
        plots[serie] = plot;
        if (values[serie]) {
          props = common.areaProps(env, 'Series', serie);
          plot.type = props.type;
          if (props.type == 'bar')
            env.indexCenter = 'bar';

          if (props.visible) {
            plot.visible = true;
            if (!labelsCount || labelsCount < values[serie].length)
              labelsCount = values[serie].length;

            // Values
            // showValues: manage NULL elements (doing an avg of near points) for line serie
            var showValues = []
            for (i = 0; i < values[serie].length; i++) {
              var val = values[serie][i];
              if (val == null && !props.hideNulls) {
                if (props.type == 'bar')
                  val = 0;
                else {
                  for (var j = i + 1; j < values[serie].length && values[serie][j] == null; j++) {}
                  var next = j < values[serie].length ? values[serie][j] : null;
                  for (var k = i -1; k >= 0 && values[serie][k] == null; k--) {}
                  var prev = k >= 0 ? values[serie][k] : null;
                  val = next != null ? (prev != null ? (next * (i - k) + prev * (j - i)) / (j - k) : next) : prev;
                }
              }
              showValues.push(val);
            }

            if (props.stacked && !(typeof props.stacked == 'string'))
              props.stacked = prevVisibleSerie;

            if (typeof props.stacked == 'undefined' || props.stacked == serie || props.stacked < 0 || !plots[props.stacked] || !plots[props.stacked].visible || plots[props.stacked].type != plot.type) {
              // NOT Stacked
              plot.ref = serie;
              if (props.type == 'bar')
                plot.barno = env.barno ++;
              plot.from = [];
              if (!props.cumulative) {
                plot.to = [];
                for (i = 0; i < showValues.length; i++)
                  if (showValues[i] != null)
                     plot.to.push(showValues[i]);
                  else
                     plot.to.push(null);

                //plot.to = showValues;
              } else {
                plot.to = [];
                cum = 0;
                for (i = 0; i < showValues.length; i++)
                  plot.to.push(cum += showValues[i]);
              }
              for (i = 0; i < showValues.length; i++)
                plot.from.push(0);


            } else {
              // Stacked
              plot.ref = props.stacked;
              if (props.type == 'bar')
                plot.barno = plots[props.stacked].barno;
              plot.from = plots[props.stacked].stack;
              plot.to = [];
              cum = 0;
              if (!props.cumulative)
                for (i = 0; i < showValues.length; i++)
                  plot.to.push(plot.from[i] + showValues[i]);
              else
                for (i = 0; i < showValues.length; i++)
                  plot.to.push(plot.from[i] + (cum += showValues[i]));
              plots[props.stacked].stack = plot.to;
            }

            plot.stack = plot.to;
            plot.max = Math.max.apply(Math, plot.from.concat(plot.to));
            plot.min = Math.min.apply(Math, plot.from.concat(plot.to));

            // Assi (DEP: values, series)
            if (props.axis) {
              if (!axis[props.axis])
                axis[props.axis] = { plots : [] };
              axis[props.axis].plots.push(serie);
              if (typeof axis[props.axis].max == 'undefined')
                axis[props.axis].max = plot.max;
              else
                axis[props.axis].max = Math.max(axis[props.axis].max, plot.max);
              if (typeof axis[props.axis].min == 'undefined')
                axis[props.axis].min = plot.min;
              else
                axis[props.axis].min = Math.min(axis[props.axis].min, plot.min);
            }

            prevVisibleSerie = serie;
          }
        }
      }
    }

    // Labels normalization (if not set or less  than values)
    if (!labels)
      labels = [];
    while (labelsCount > labels.length)
      labels.push(null);
    labelsCount = labels.length;
    env.opt.labels = labels;

    // Prepare axis scale (values, series, axis)
    if (common.executeIfChanged(env, ['values', 'series', 'axis'])) {
      for (var lidx in axis) {
        props = common.areaProps(env, 'Axis', lidx);
        axis[lidx].props = props;

        if (typeof props.max != 'undefined')
          axis[lidx].max = props.max;
        if (typeof props.min != 'undefined')
          axis[lidx].min = props.min;

        if (axis[lidx].min == axis[lidx].max)
          axis[lidx].max = axis[lidx].min + 1;

        if (props.normalize && props.normalize > 0) {
          var v = Math.abs(axis[lidx].max);
          if (axis[lidx].min && Math.abs(axis[lidx].min) > v)
            v = Math.abs(axis[lidx].min);
          if (v) {
            var basev = Math.floor(Math.log(v)/Math.LN10) - (props.normalize - 1);
            // NOTE: On firefox Math.pow(10, -X) sometimes results in number noise (0.89999...), it's better to do 1/Math.pow(10,X)
            basev = basev >= 0 ? Math.pow(10, basev) : 1 / Math.pow(10, -basev);
            v = Math.ceil(v / basev / (opt.features.grid.ny ? opt.features.grid.ny : 1)) * basev * (opt.features.grid.ny ? opt.features.grid.ny : 1);
            // Calculation above, with decimal number sometimes insert some noise in numbers (eg: 8.899999... instead of 0.9), so i need to round result with proper precision
            v = Math.round(v / basev) * basev;
            // I need to store the normalization base for further roundin (eg: in axis label, sometimes calculation results in "number noise", so i need to round them with proper precision)
            axis[lidx].normalizationBase = basev;
            if (axis[lidx].max)
              axis[lidx].max = Math.ceil(axis[lidx].max / v) * v;
            if (axis[lidx].min)
              axis[lidx].min = Math.floor(axis[lidx].min / v) * v;
          }
        }
        if (axis[lidx].plots)
          for (var ii = 0; ii < axis[lidx].plots.length; ii++) {
            plots[axis[lidx].plots[ii]].max = axis[lidx].max;
            plots[axis[lidx].plots[ii]].min = axis[lidx].min;
          }
      }
    }

    var pieces = [];

    this.grid(env, pieces);

    // DEP: *
    var deltaX = (opt.width - opt.margins[3] - opt.margins[1]) / (labels.length > 1 ? labels.length - 1 : 1);
    var deltaBarX = (opt.width - opt.margins[3] - opt.margins[1]) / (labels.length > 0 ? labels.length : 1);

    for (serie in values) {
      props = common.areaProps(env, 'Series', serie);
      plot = plots[serie];

      // TODO Settare una props in questo modo potrebbe incasinare la gestione degli update parziali (se iso "lineCenter: auto" e passo da un grafico con indexCenter = bar a uno con indexCenter = line)
      if (props.lineCenter && props.lineCenter == 'auto')
        props.lineCenter = (env.indexCenter == 'bar');
      else if (props.lineCenter && env.indexCenter == 'line')
        env.indexCenter = 'bar';

      if (values[serie] && props.visible) {
        var deltaY = (opt.height - opt.margins[2] - opt.margins[0]) / (plot.max - plot.min);

        if (props.type == 'line') {
          // LINE CHART
          var linePath = [ 'LINE', [], props.rounded ];
          var fillPath = [ 'LINEAREA', [], [], props.rounded ];
          var dotPieces = [];

          for (i = 0, ii = labels.length; i < ii; i++)
            if (plot.to.length > i) {
              var indexProps = common.areaProps(env, 'Series', serie, i);

              var d = plot.to[i] > plot.max ? plot.max : (plot.to[i] < plot.min ? plot.min : plot.to[i]);
              var x = Math.round((props.lineCenter ? deltaBarX / 2 : 0) + opt.margins[3] + i * (props.lineCenter ? deltaBarX : deltaX));
              var y = Math.round(opt.height - opt.margins[2] - deltaY * (d - plot.min));
              var dd = plot.from[i] > plot.max ? plot.max : (plot.from[i] < plot.min ? plot.min : plot.from[i]);
              var yy = Math.round(opt.height - opt.margins[2] - deltaY * (dd - plot.min)) + (/msie/.test(navigator.userAgent.toLowerCase()) ? 1 : 0);

              if (d != null || !props.hideNulls) {
                linePath[1].push([x, y]);

                if (props.fill) {
                  fillPath[1].push([x, y]);
                  fillPath[2].push([x, yy]);
                }
                if (indexProps.dot) {
                  if (values[serie][i] == null && !indexProps.dotShowOnNull)
                     dotPieces.push({path : false, attr : false});
                  else
                     dotPieces.push({path : [ [ 'CIRCLE', x, y, indexProps.dotProps.size ] ], attr : indexProps.dotProps}); // TODO Size should not be in dotProps (not an svg props)
                }
              }
            }

          if (props.fill)
            pieces.push({ section : 'Series', serie : serie, subSection : 'Fill', path : [ fillPath ], attr : props.fillProps });
          else
            pieces.push({ section : 'Series', serie : serie, subSection : 'Fill', path : false, attr : false });
          pieces.push({ section : 'Series', serie : serie, subSection : 'Plot', path : [ linePath ], attr : props.plotProps , mousearea : 'pathsteps'});

          if (dotPieces.length)
            pieces.push({ section : 'Series', serie : serie, subSection : 'Dot', paths : dotPieces });
          else
            pieces.push({ section : 'Series', serie : serie, subSection : 'Dot', path : false, attr : false });

        } else {
          pieceBar = [];

          // BAR CHART
          for (i = 0, ii = labels.length; i < ii; i++)
            if (plot.to.length > i) {
              if (plot.from[i] != plot.to[i]) {
                var bwid = Math.floor((deltaBarX - opt.barMargins) / env.barno);
                var bpad = bwid * (100 - props.barWidthPerc) / 200;
                var boff = opt.barMargins / 2 + plot.barno * bwid;

                var x1 = Math.floor(opt.margins[3] + i * deltaBarX + boff + bpad);
                var y1 = Math.round(opt.height - opt.margins[2] - deltaY * (plot.to[i] - plot.min));
                var y2 = Math.round(opt.height - opt.margins[2] - deltaY * (plot.from[i] - plot.min));

                pieceBar.push({path : [ [ 'RECT', x1, y1, x1 + bwid - bpad * 2, y2 ] ], attr : props.plotProps });
              } else
                pieceBar.push({path : false, attr : false });
            }

          if (pieceBar.length)
            pieces.push({ section : 'Series', serie : serie, subSection : 'Plot', paths: pieceBar, mousearea : 'paths' });
          else
            pieces.push({ section : 'Series', serie : serie, subSection : 'Plot', path: false, attr: false, mousearea : 'paths' });
        }

      } else {
        // Grafico non visibile / senza dati, deve comunque inserire i piece vuoti (NELLO STESSO ORDINE SOPRA!)
        if (props.type == 'line')
          pieces.push({ section : 'Series', serie : serie, subSection : 'Fill', path : false, attr : false });
        pieces.push({ section : 'Series', serie : serie, subSection : 'Plot', path: false, attr: false, mousearea : 'paths' });
        if (props.type == 'line')
          pieces.push({ section : 'Series', serie : serie, subSection : 'Dot', path : false, attr : false });
      }
    }
    featuresmanager.beforeShow(env, pieces);
    common.show(env, pieces);
    featuresmanager.afterShow(env, pieces);
    return pieces;
  },

  grid : function(env, pieces) {

    // DEP: axis, [=> series, values], labels, margins, width, height, grid*
    if (common.executeIfChanged(env, ['values', 'series', 'axis', 'labels', 'margins', 'width', 'height', 'features.grid'])) {
      var opt = env.opt;
      var props = env.opt.features.grid;
      var paper = env.paper;
      var axis = env.axis;
      var labels = env.opt.labels;
      var deltaX = (opt.width - opt.margins[3] - opt.margins[1]) / (labels.length > 1 ? labels.length - 1 : 1);
      var deltaBarX = (opt.width - opt.margins[3] - opt.margins[1]) / (labels.length > 0 ? labels.length : 1);
      var i, j, x, y, lw, labx, laby, labe, val, txt;
      // Label X axis
      var paths = [];
      var labelsCenter = props.labelsCenter;
      if (labelsCenter == 'auto')
        labelsCenter = (env.indexCenter == 'bar');

      if (axis.x && axis.x.props.labels) {
        // used in case of labelsHideCovered, contains a "rotated" representation of the rect coordinates occupied by the last shown label
        var lastShownLabelRect = false;
        // labelsAnchor is "auto" by default. Can be "start","middle" or "end". If "auto" then it is automatically set depending on labelsRotate.
        var labelsAnchor = axis.x.props.labelsAnchor || 'auto';
        // Automatic labelsAnchor is "middle" on no rotation, otherwise the anchor is the higher side of the label.
        if (labelsAnchor == 'auto')
          labelsAnchor = axis.x.props.labelsRotate > 0 ? "start" : (axis.x.props.labelsRotate == 0 ? "middle" : "end");
        // labelsPos is "auto" by default. Can be "start", "middle" or "end". If "auto" then it is automatically set depending on labelsCenter and labelsRotate and labelsAnchor.
        var labelsPos = axis.x.props.labelsPos || 'auto';
        // in labelsCenter (bar) it is middle when there is no rotation, equals to labelsAnchor on rotation.
        // in !labelsCenter (line) is is always 'start';
        if (labelsPos == 'auto')
          labelsPos = labelsCenter ? (axis.x.props.labelsRotate == 0 ? labelsAnchor : 'middle') : 'start';

        for (i = 0; i < labels.length; i++)
          if ((typeof labels[i] != 'boolean' && labels[i] != null) || labels[i]) {

            if (!axis.x.props.labelsSkip || i >= axis.x.props.labelsSkip) {
              val = labels[i];

              if (axis.x.props.labelsFormatHandler)
                val = axis.x.props.labelsFormatHandler(val, i);
              txt = (axis.x.props.prefix ? axis.x.props.prefix : "") + val + (axis.x.props.suffix ? axis.x.props.suffix : "");

              labx = opt.margins[3] + i * (labelsCenter ? deltaBarX : deltaX) + (axis.x.props.labelsMargin ? axis.x.props.labelsMargin : 0);
              if (labelsPos == 'middle') labx += (labelsCenter ? deltaBarX : deltaX) / 2;
              if (labelsPos == 'end') labx += (labelsCenter ? deltaBarX : deltaX);

              laby = opt.height - opt.margins[2] + axis.x.props.labelsDistance;
              labe = paper.text(labx, laby, txt).attr(axis.x.props.labelsProps).toBack();

              labe.attr({"text-anchor" : labelsAnchor});

              // will contain the boundingbox size, or false if it is hidden.
              var boundingbox = false;
              var bbox = labe.getBBox();
              var p1 = {x: bbox.x, y: bbox.y};
              var p2 = {x: bbox.x+bbox.width, y: bbox.y+bbox.height};
              var o1 = {x: labx, y: laby};

              rotate = function (p, rad) {
                var X = p.x * Math.cos(rad) - p.y * Math.sin(rad),
                    Y = p.x * Math.sin(rad) + p.y * Math.cos(rad);
                return {x: X, y: Y};
              };
              // calculate collision between non rotated rects with vertext p1-p2 and t1-t2
              // this algorythm works only for horizontal rects (alpha = 0)
              // "dist" is the length added as a margin to the rects before collision detection
              collide = function(r1,r2,dist) {
                xor = function(a,b) {
                  return ( a || b ) && !( a && b );
                }
                if (r1.alpha != r2.alpha) throw "collide doens't support rects with different rotations";
                var r1p1r = rotate({x: r1.p1.x-dist, y:r1.p1.y-dist}, -r1.alpha);
                var r1p2r = rotate({x: r1.p2.x+dist, y:r1.p2.y+dist}, -r1.alpha);
                var r2p1r = rotate({x: r2.p1.x-dist, y:r2.p1.y-dist}, -r2.alpha);
                var r2p2r = rotate({x: r2.p2.x+dist, y:r2.p2.y+dist}, -r2.alpha);
                return !xor(Math.min(r1p1r.x,r1p2r.x) > Math.max(r2p1r.x,r2p2r.x), Math.max(r1p1r.x,r1p2r.x) < Math.min(r2p1r.x,r2p2r.x)) &&
                        !xor(Math.min(r1p1r.y,r1p2r.y) > Math.max(r2p1r.y,r2p2r.y), Math.max(r1p1r.y,r1p2r.y) < Math.min(r2p1r.y,r2p2r.y));
              }
              // compute equivalent orizontal rotated rect
              rotated = function(rect, origin, alpha) {
                translate = function (p1, p2) {
                  return {x: p1.x+p2.x, y: p1.y+p2.y};
                };
                negate = function(p1) {
                  return {x: -p1.x, y: -p1.y};
                };
                var p1trt = translate(rotate(translate(rect.p1,negate(origin)), alpha),origin);
                var p2trt = translate(rotate(translate(rect.p2,negate(origin)), alpha),origin);
                return { p1: p1trt, p2: p2trt, alpha: rect.alpha+alpha };
              }
              bbox = function(rect) {
                if (rect.alpha == 0) {
                  return { x: rect.p1.x, y: rect.p1.y, width: rect.p2.x-rect.p1.x, height: rect.p2.y-rect.p1.y };
                } else {
                  var points = [];
                  points.push({ x: 0, y: 0 });
                  points.push({ x: rect.p2.x-rect.p1.x, y: 0 });
                  points.push({ x: 0, y: rect.p2.y-rect.p1.y });
                  points.push({ x: rect.p2.x-rect.p1.x, y: rect.p2.y-rect.p1.y });
                  var bb = [];
                  bb['left'] = 0; bb['right'] = 0; bb['top'] = 0; bb['bottom'] = 0;
                  for (_px = 0; _px < points.length; _px++) {
                    var p = points[_px];
                    var newX = parseInt((p.x * Math.cos(rect.alpha)) + (p.y * Math.sin(rect.alpha)));
                    var newY = parseInt((p.x * Math.sin(rect.alpha)) + (p.y * Math.cos(rect.alpha)));
                    bb['left'] = Math.min(bb['left'], newX);
                    bb['right'] = Math.max(bb['right'], newX);
                    bb['top'] = Math.min(bb['top'], newY);
                    bb['bottom'] = Math.max(bb['bottom'], newY);
                  }
                  var newWidth = parseInt(Math.abs(bb['right'] - bb['left']));
                  var newHeight = parseInt(Math.abs(bb['bottom'] - bb['top']));
                  var newX = ((rect.p1.x + rect.p2.x) / 2) - newWidth / 2;
                  var newY = ((rect.p1.y + rect.p2.y) / 2) - newHeight / 2;
                  return { x: newX, y: newY, width: newWidth, height: newHeight };
                }
              }

              var alpha = Raphael.rad(axis.x.props.labelsRotate);
              // compute used "rect" so to be able to check if there is overlapping with previous ones.
              var rect = rotated({p1: p1, p2: p2, alpha: 0}, o1, alpha);

              //console.log('bbox ',p1, p2, rect, props.nx, val, rect.p1, rect.p2, rect.alpha, boundingbox, opt.width);
              // se collide con l'ultimo mostrato non lo mostro.
              var dist = axis.x.props.labelsMarginRight ? axis.x.props.labelsMarginRight / 2 : 0;
              if (axis.x.props.labelsHideCovered && lastShownLabelRect && collide(rect, lastShownLabelRect, dist)) {
              	labe.hide();
              	labels[i] = false;
              } else {
                boundingbox = bbox(rect);
                // Manage label overflow
                if (props.nx == 'auto' && (boundingbox.x < 0 || boundingbox.x+boundingbox.width > opt.width)) {
                  labe.hide();
                  labels[i] = false;
                } else {
                  lastShownLabelRect = rect;
                }
              }

              // Apply rotation to the element.
              if (axis.x.props.labelsRotate) {
                labe.rotate(axis.x.props.labelsRotate, labx, laby).toBack();
              }

              paths.push({ path : [ [ 'RELEMENT', labe ] ], attr : false });
            }
          }
      }
      pieces.push({ section : 'Axis', serie : 'x', subSection : 'Label', paths : paths });

      // Title X Axis
      if (axis.x && axis.x.props.title) {
        x = opt.margins[3] + Math.floor((opt.width - opt.margins[1] - opt.margins[3]) / 2);
        y = opt.height - opt.margins[2] + axis.x.props.titleDistance * (/msie/.test(navigator.userAgent.toLowerCase()) ? axis.x.props.titleDistanceIE : 1);
        //paper.text(x, y, axis.x.props.title).attr(axis.x.props.titleProps);
        pieces.push({ section : 'Axis', serie : 'x', subSection : 'Title', path : [ [ 'TEXT', axis.x.props.title, x, y ] ], attr : axis.x.props.titleProps });
      } else
        pieces.push({ section : 'Axis', serie : 'x', subSection : 'Title', path : false, attr : false });

      // Label + Title L/R Axis
      for (var jj in ['l', 'r']) {
        j = ['l', 'r'][jj];
        if (axis[j] && axis[j].props.labels && props.ny) {
          paths = [];
          for (i = axis[j].props.labelsSkip ? axis[j].props.labelsSkip : 0; i <= props.ny; i++) {
            var deltaY = (opt.height - opt.margins[2] - opt.margins[0]) / props.ny;
            if (j == 'r') {
              labx = opt.width - opt.margins[1] + axis[j].props.labelsDistance;
              if (!axis[j].props.labelsProps["text-anchor"])
                axis[j].props.labelsProps["text-anchor"] = "start";
            } else {
              labx = opt.margins[3] - axis[j].props.labelsDistance;
              if (!axis[j].props.labelsProps["text-anchor"])
                axis[j].props.labelsProps["text-anchor"] = "end";
            }
            if (axis[j].props.labelsAnchor && axis[j].props.labelsAnchor != 'auto')
              axis[j].props.labelsProps["text-anchor"] = axis[j].props.labelsAnchor;
            // NOTE: Parenthesis () around division are useful to keep right number precision
            val = (axis[j].min + (i * ((axis[j].max - axis[j].min) / props.ny)));
            // Rounding with proper precision for "number sharpening"
            if (axis[j].normalizationBase)
              // I use (1 / ( 1 / norm ) ) to avoid some noise
              val = Math.round(val / axis[j].normalizationBase) / ( 1 / axis[j].normalizationBase );

            if (axis[j].props.labelsFormatHandler)
              val = axis[j].props.labelsFormatHandler(val, i);
            if (axis[j].props.labelsCompactUnits)
              val = common.compactUnits(val, axis[j].props.labelsCompactUnits);
            txt = (axis[j].props.prefix ? axis[j].props.prefix : "") + val + (axis[j].props.suffix ? axis[j].props.suffix : "");
            laby = opt.height - opt.margins[2] - i * deltaY;
            //var labe = paper.text(labx, laby + (axis[j].props.labelsMargin ? axis[j].props.labelsMargin : 0), txt).attr(axis[j].props.labelsProps).toBack();
            paths.push( { path : [ [ 'TEXT', txt, labx, laby + (axis[j].props.labelsMargin ? axis[j].props.labelsMargin : 0) ] ], attr : axis[j].props.labelsProps });
          }
          pieces.push({ section : 'Axis', serie : j, subSection : 'Label', paths : paths });
        } else
          pieces.push({ section : 'Axis', serie : j, subSection : 'Label', paths : [] });

        if (axis[j] && axis[j].props.title) {
          if (j == 'r')
            x = opt.width - opt.margins[1] + axis[j].props.titleDistance * (/msie/.test(navigator.userAgent.toLowerCase()) ? axis[j].props.titleDistanceIE : 1);
          else
            x = opt.margins[3] - axis[j].props.titleDistance * (/msie/.test(navigator.userAgent.toLowerCase()) ? axis[j].props.titleDistanceIE : 1);
          //paper.text(x, opt.margins[0] + Math.floor((opt.height - opt.margins[0] - opt.margins[2]) / 2), axis[j].props.title).attr(axis[j].props.titleProps).attr({rotation : j == 'l' ? 270 : 90});
          var attr = common._clone(axis[j].props.titleProps);
          attr.rotation = j == 'l' ? 270 : 90
          pieces.push({ section : 'Axis', serie : j, subSection : 'Title', path : [ [ 'TEXT', axis[j].props.title, x, opt.margins[0] + Math.floor((opt.height - opt.margins[0] - opt.margins[2]) / 2) ] ], attr : attr });
        } else
          pieces.push({ section : 'Axis', serie : j, subSection : 'Title', path : false, attr : false });
      }

      // Grid
      if (props.nx || props.ny) {
        var path = [], bandsH = [], bandsV = [],
          nx = props.nx == 'auto' ? (labelsCenter ? labels.length : labels.length - 1) : props.nx,
          ny = props.ny,
          rowHeight = (opt.height - opt.margins[2] - opt.margins[0]) / (ny ? ny : 1),
          columnWidth = (opt.width - opt.margins[1] - opt.margins[3]) / (nx ? nx : 1),
          forceBorderX1 = typeof props.forceBorder == 'object' ? props.forceBorder[3] : props.forceBorder,
          forceBorderX2 = typeof props.forceBorder == 'object' ? props.forceBorder[1] : props.forceBorder,
          forceBorderY1 = typeof props.forceBorder == 'object' ? props.forceBorder[0] : props.forceBorder,
          forceBorderY2 = typeof props.forceBorder == 'object' ? props.forceBorder[2] : props.forceBorder,
          drawH = ny > 0 ? (typeof props.draw == 'object' ? props.draw[0] : props.draw) : false,
          drawV = nx > 0 ? typeof props.draw == 'object' ? props.draw[1] : props.draw : false;

        if (ny > 0)
          for (i = 0; i < ny + 1; i++) {
            if (
              forceBorderY1 && i == 0 || // Show top line only if forced
              forceBorderY2 && i == ny ||  // Show bottom line only if forced
              drawH && i > 0 && i < ny // Show  other lines if draw = true
            ) {
              path.push(["M", opt.margins[3] - props.extra[3], opt.margins[0] + Math.round(i * rowHeight) ]);
              path.push(["L", opt.width - opt.margins[1] + props.extra[1], opt.margins[0] + Math.round(i * rowHeight)]);
            }
            if (i < ny) {
              if (i % 2 == 0 && props.evenHProps || i % 2 == 1 && props.oddHProps)
                bandsH.push({path : [ [ 'RECT',
                      opt.margins[3] - props.extra[3], opt.margins[0] + Math.round(i * rowHeight), // x1, y1
                      opt.width - opt.margins[1] + props.extra[1], opt.margins[0] + Math.round((i + 1) * rowHeight) // x2, y2
                  ] ], attr : i % 2 == 0 ? props.evenHProps : props.oddHProps });
              else
                bandsH.push({ path : false, attr: false})
            }
          }

        for (i = 0; i < nx + 1; i++) {
          if (
            forceBorderX1 && i == 0 || // Always show first line if forced
            forceBorderX2 && i == nx || // Always show last line if forced
            drawV && ( // To show other lines draw must be true
              (props.nx != 'auto' && i > 0 && i < nx) || // If nx = [number] show other lines (first and last are managed above with forceBorder)
              (props.nx == 'auto' && (typeof labels[i] != 'boolean' || labels[i])) // if nx = 'auto' show all lines if a label is associated
            )
            // Show all lines if props.nx is a number, or if label != false, AND draw must be true
          ) {
            path.push(["M", opt.margins[3] + Math.round(i * columnWidth), opt.margins[0] - props.extra[0] ]); //(t ? props.extra[0] : 0)]);
            path.push(["L", opt.margins[3] + Math.round(i * columnWidth), opt.height - opt.margins[2] + props.extra[2] ]); //(t ? props.extra[2] : 0)]);
          }
          if (i < nx) {
            if (i % 2 == 0 && props.evenVProps || i % 2 == 1 && props.oddVProps)
              bandsV.push({path : [ [ 'RECT',
                    opt.margins[3] + Math.round(i * columnWidth), opt.margins[0] - props.extra[0], // x1, y1
                    opt.margins[3] + Math.round((i + 1) * columnWidth), opt.height - opt.margins[2] + props.extra[2], // x2, y2
                ] ], attr : i % 2 == 0 ? props.evenVProps : props.oddVProps });
            else
              bandsV.push({ path : false, attr: false})
          }
        }

        pieces.push({ section : 'Grid', path : path.length ? path : false, attr : path.length ? props.props : false });
        pieces.push({ section : 'GridBandH', paths : bandsH });
        pieces.push({ section : 'GridBandV', paths : bandsV });

        var tpath = [];

        // Ticks asse X
        if (props.ticks.active && (typeof props.ticks.active != 'object' || props.ticks.active[0])) {
          for (i = 0; i < nx + 1; i++) {
            if (props.nx != 'auto' || typeof labels[i] != 'boolean' || labels[i]) {
              tpath.push(["M", opt.margins[3] + Math.round(i * columnWidth), opt.height - opt.margins[2] - props.ticks.size[1] ]);
              tpath.push(["L", opt.margins[3] + Math.round(i * columnWidth), opt.height - opt.margins[2] + props.ticks.size[0] ]);
            }
          }
        }
        // Ticks asse L
        if (props.ticks.active && (typeof props.ticks.active != 'object' || props.ticks.active[1]))
          for (i = 0; i < ny + 1; i++) {
            tpath.push(["M", opt.margins[3] - props.ticks.size[0], opt.margins[0] + Math.round(i * rowHeight) ]);
            tpath.push(["L", opt.margins[3] + props.ticks.size[1], opt.margins[0] + Math.round(i * rowHeight)]);
          }
        // Ticks asse R
        if (props.ticks.active && (typeof props.ticks.active != 'object' || props.ticks.active[2]))
          for (i = 0; i < ny + 1; i++) {
            tpath.push(["M", opt.width - opt.margins[1] - props.ticks.size[1], opt.margins[0] + Math.round(i * rowHeight) ]);
            tpath.push(["L", opt.width - opt.margins[1] + props.ticks.size[0], opt.margins[0] + Math.round(i * rowHeight)]);
          }

        pieces.push({ section : 'Ticks', path : tpath.length ? tpath : false, attr : tpath.length ? props.ticks.props : false });
      }
    }
  }
}

})(jQuery);
/********* Source File: src/elycharts_chart_pie.js*********/
/**********************************************************************
 * ELYCHARTS
 * A Javascript library to generate interactive charts with vectorial graphics.
 *
 * Copyright (c) 2010 Void Labs s.n.c. (http://void.it)
 * Licensed under the MIT (http://creativecommons.org/licenses/MIT/) license.
 **********************************************************************/

(function($) {

var featuresmanager = $.elycharts.featuresmanager;
var common = $.elycharts.common;

/***********************************************************************
 * CHART: PIE
 **********************************************************************/

$.elycharts.pie = {
  init : function($env) {
  },

  draw : function(env) {
    //var paper = env.paper;
    var opt = env.opt;

    var w = env.opt.width;
    var h = env.opt.height;
    var r = env.opt.r ? env.opt.r : Math.floor((w < h ? w : h) / 2.5);
    var cx = env.opt.cx ? env.opt.cx : Math.floor(w / 2);
    var cy = env.opt.cy ? env.opt.cx : Math.floor(h / 2);

    var cnt = 0, i, ii, serie, plot, props;
    for (serie in opt.values) {
      plot = {
        visible : false,
        total : 0,
        values : []
      };
      env.plots[serie] = plot;
      var serieProps = common.areaProps(env, 'Series', serie);
      if (serieProps.visible) {
        plot.visible = true;
        cnt ++;
        plot.values = opt.values[serie];
        for (i = 0, ii = plot.values.length; i < ii; i++)
          if (plot.values[i] > 0) {
            props = common.areaProps(env, 'Series', serie, i);
            if (typeof props.inside == 'undefined' || props.inside < 0)
              plot.total += plot.values[i];
          }
        for (i = 0; i < ii; i++)
          if (plot.values[i] < plot.total * opt.valueThresold) {
            plot.total = plot.total - plot.values[i];
            plot.values[i] = 0;
          }
      }
    }

    var rstep = r / cnt;
    var rstart = -rstep, rend = 0;

    var pieces = [];
    for (serie in opt.values) {
      plot = env.plots[serie];
      var paths = [];
      if (plot.visible) {
        rstart += rstep;
        rend += rstep;
        var angle = env.opt.startAngle, angleplus = 0, anglelimit = 0;

        if (plot.total == 0) {
          env.emptySeries = true;
          props = common.areaProps(env, 'Series', 'empty');
          paths.push({ path : [ [ 'CIRCLE', cx, cy, r ] ], attr : props.plotProps });

        } else {
          env.emptySeries = false;
          for (i = 0, ii = plot.values.length; i < ii; i++) {
            var value = plot.values[i];
            if (value > 0) {
              props = common.areaProps(env, 'Series', serie, i);
              if (typeof props.inside == 'undefined' || props.inside < 0) {
                angle += anglelimit;
                angleplus = 360 * value / plot.total;
                anglelimit = angleplus;
              } else {
                angleplus = 360 * values[props.inside] / plot.total * value / values[props.inside];
              }
              var rrstart = rstart, rrend = rend;
              if (props.r) {
                if (props.r > 0) {
                  if (props.r <= 1)
                    rrend = rstart + rstep * props.r;
                  else
                    rrend = rstart + props.r;
                } else {
                  if (props.r >= -1)
                    rrstart = rstart + rstep * (-props.r);
                  else
                    rrstart = rstart - props.r;
                }
              }

              if (!env.opt.clockwise)
                paths.push({ path : [ [ 'SLICE', cx, cy, rrend, rrstart, angle, angle + angleplus ] ], attr : props.plotProps });
              else
                paths.push({ path : [ [ 'SLICE', cx, cy, rrend, rrstart, - angle - angleplus, - angle ] ], attr : props.plotProps });
            } else
              paths.push({ path : false, attr : false });
          }
        }
      } else {
        // Even if serie is not visible it's better to put some empty path (for better transitions). It's not mandatory, just better
        if (opt.values[serie] && opt.values[serie].length)
          for (i = 0, ii = opt.values[serie].length; i < ii; i++)
            paths.push({ path : false, attr : false });
      }

      pieces.push({ section : 'Series', serie : serie, subSection : 'Plot', paths : paths , mousearea : 'paths'});
    }

    featuresmanager.beforeShow(env, pieces);
    common.show(env, pieces);
    featuresmanager.afterShow(env, pieces);
    return pieces;
  }
}

})(jQuery);