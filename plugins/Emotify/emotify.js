/**
 * This is a modified version of Ben Alman's dual GPL/MIT licensed "Javascript
 * Emotify" jQuery plugin.
 */

// About: License
// Copyright (c) 2009 "Cowboy" Ben Alman,
// Dual licensed under the MIT and GPL licenses.
// http://benalman.com/about/license/

window.emotify = (function(){
  var emotify,
    EMOTICON_RE,
    emoticons = {},
    lookup = [];
    
  emotify = function(txt, callback) {
    callback = callback || function(cssSuffix, smiley) {
      return '<span class="Emoticon Emoticon' + cssSuffix + '"><span>' + smiley + '</span></span>';
    };
    
    txt = txt.toString();
    txt = txt.replace("\n", "EXPLICIT_EMOTIFY_NEWLINE");
    txt = txt.replace(/<br>/img, "\nEXPLICIT_EMOTIFY_BR");
    txt = txt.replace(/<br \/>/img, "\nEXPLICIT_EMOTIFY_BR");
    txt = txt.replace(EMOTICON_RE, function(a, b, text) {
      var i = 0,
        smiley = text,
        e = emoticons[text];
      
      // If smiley matches on manual regexp, reverse-lookup the smiley.
      if (!e) {
        while (i < lookup.length && !lookup[i].regexp.test(text)) { i++ };
        smiley = lookup[i].name;
        e = emoticons[smiley];
      }
      
      // If the smiley was found, return HTML, otherwise the original search string
      return e ? (b + callback(e[0], smiley)) : a;
    });
    txt = txt.replace(/EXPLICIT_EMOTIFY_BR/img, "<br />");
    txt = txt.replace(/EXPLICIT_EMOTIFY_NEWLINE/img, "\n\n");
    return txt;
  };
  
  emotify.emoticons = function() {
    var args = Array.prototype.slice.call( arguments ),
      replace_all = typeof args[0] === 'boolean' ? args.shift() : false,
      smilies = args[0],
      e,
      arr = [],
      alts,
      i,
      regexp_str;
    
    if (smilies) {
      if (replace_all) {
        emoticons = {};
        lookup = [];
      }

      for (e in smilies) {
        emoticons[e] = smilies[e];
        emoticons[e][0] = emoticons[e][0];
      }
      
      // Generate the smiley-match regexp.
      for (e in emoticons) {
        if (emoticons[e].length > 1) {
          // Generate regexp from smiley and alternates.
          alts = emoticons[e].slice(1).concat(e);
          i = alts.length
          while (i--) {
            alts[i] = alts[i].replace(/(\W)/g, '\\$1');
          }
          
          regexp_str = alts.join('|');
          
          // Manual regexp, map regexp back to smiley so we can reverse-match.
          lookup.push({ name: e, regexp: new RegExp( '^' + regexp_str + '$' ) });
        } else {
          // Generate regexp from smiley.
          regexp_str = e.replace(/(\W)/g, '\\$1');
        }
        
        arr.push(regexp_str);
      }
      
      EMOTICON_RE = new RegExp('(\\s?)(' + arr.join('|') + ')(?=(?:$|\\s))', 'g');
    }
    
    return emoticons;
  };
  
  return emotify;
  
})();

$(function(){
  var emoticons = {
/*  smiley, css_suffix, alternate_smileys */
    ":-)":    ["1", ":)"],
    ":(":    ["2", ":-("],
    ";)":    ["3", ";-)"],
    ":D":    ["4", ":-D"],
    ";;)":   ["5"],
    ">:D<":  ["6", "&gt;:D&lt;"],
    ":-/":   ["7", ":/"],
    ":x":    ["8", ":X"],
    ":\">":  ["9", ":\"&gt;"],
    ":P":    ["10", ":p", ":-p", ":-P"],
    ":-*":   ["11", ":*"],
    "=((":   ["12"],
    ":-O":   ["13", ":O"],
    "X(":    ["14"],
    ":>":    ["15", ":&gt;"],
    "B-)":   ["16"],
    ":-S":   ["17"],
    "#:-S":  ["18", "#:-s"],
    ">:)":   ["19", ">:-)", "&gt;:)", "&gt;:-)"],
    ":((":   ["20", ":-((", ":'(", ":'-("],
    ":))":   ["21", ":-))"],
    ":|":    ["22", ":-|"],
    "/:)":   ["23", "/:-)"],
//    "=))":   ["24"],
    "O:-)":  ["25", "O:)"],
//    ":-B":   ["26"],
    "=;":    ["27"],
    "I-)":   ["28"],
    "8-|":   ["29"],
    "L-)":   ["30"],
    ":-&":   ["31", ":0&amp;"],
    ":-$":   ["32"],
    "[-(":   ["33"],
//    ":O)":   ["34"],
//    "8-}":   ["35"],
//    "<:-P":  ["36"],
    "(:|":   ["37"],
    "=P~":   ["38"],
    ":-?":   ["39"],
    "#-o":   ["40", "#-O"],
    "=D>":   ["41", "=D&gt;"],
    ":-SS":  ["42", ":-ss"],
    "@-)":   ["43"],
    ":^o":   ["44"],
    ":-w":   ["45", ":-W"],
    ":-<":   ["46", ":-&lt;"],
    ">:P":   ["47", ">:p", "&gt;:P", "&gt;:p"],
    "<):)":  ["48", "&lt;):)"],
//    ":@)":   ["49"],
//    "3:-O":  ["50", "3:-o"],
    ":(|)":  ["51"],
    "~:>":   ["52", "~:&gt;"],
//    "@};-":  ["53"],
    "%%-":   ["54"],
//    "**==":  ["55"],
//    "(~~)":  ["56"],
    "~O)":   ["57"],
    "*-:)":  ["58"],
    "8-X":   ["59"],
//    "=:)":   ["60"],
    ">-)":   ["61", "&gt;-)"],
    ":-L":   ["62", ":L"],
    "[-O<":  ["63", "[-O&lt;"],
    "$-)":   ["64"],
    ":-\"":  ["65"],
    "b-(":   ["66"],
    ":)>-":  ["67", ":)&gt;-"],
    "[-X":   ["68"],
    "\\:D/": ["69"],
    ">:/":   ["70", "&gt;:/"],
    ";))":   ["71"],
    "o->":   ["72", "o-&gt;"],
    "o=>":   ["73", "o=&gt;"],
    "o-+":   ["74"],
    "(%)":   ["75"],
    ":-@":   ["76"],
    "^:)^":  ["77"],
    ":-j":   ["78"],
    "(*)":   ["79"],
    ":)]":   ["100"],
    ":-c":   ["101"],
    "~X(":   ["102"],
    ":-h":   ["103"],
    ":-t":   ["104"],
    "8->":   ["105", "8-&gt;"],
    ":-??":  ["106"],
    "%-(":   ["107"],
//    ":o3":   ["108"],
    "X_X":   ["109"],
    ":!!":   ["110"],
    "\\m/":  ["111"],
    ":-q":   ["112"],
    ":-bd":  ["113"],
    "^#(^":  ["114"],
    ":bz":   ["115"],
    ":ar!":  ["pirate"],
    "[..]":  ["transformer"]
  }
  
  emotify.emoticons(emoticons);
  
  $('div.Comment div.Message, div.Preview div.Message').livequery(function() {
    $(this).html(emotify($(this).html()));
  });
  
  // Insert a clickable icon list after the textbox
  $('textarea#Form_Body').livequery(function() {
    var buts = '';
    var emo = '';
    for (e in emoticons) {
      emo = emoticons[e][1];
      if (typeof(emo) == 'undefined')
        emo = e;
        
      buts += '<a class="EmoticonBox Emoticon Emoticon'+emoticons[e][0]+'"><span>'+emo+'</span></a>';
    }
    $(this).before("<div class=\"EmotifyWrapper\">\
      <a class=\"EmotifyDropdown\"><span>Emoticons</span></a> \
      <div class=\"EmoticonContainer Hidden\">"+buts+"</div> \
    </div>");
    
    $('.EmotifyDropdown').live('click', function() {
      if ($(this).hasClass('EmotifyDropdownActive'))
        $(this).removeClass('EmotifyDropdownActive');
      else
        $(this).addClass('EmotifyDropdownActive');

      $(this).next().toggle();
      return false;
    });
    
    // Hide emotify options when previewing
    $('form#Form_Comment').bind("PreviewLoaded", function(e, frm) {
      frm.find('.EmotifyDropdown').removeClass('EmotifyDropdownActive');
      frm.find('.EmotifyDropdown').hide();
      frm.find('.EmoticonContainer').hide();
    });
    
    // Reveal emotify dropdowner when write button clicked
    $('form#Form_Comment').bind('WriteButtonClick', function(e, frm) {
      frm.find('.EmotifyDropdown').show();
    });
    
    // Hide emoticon box when textarea is focused
    $('textarea#Form_Body').live('focus', function() {
      var frm = $(this).parents('form');
      frm.find('.EmotifyDropdown').removeClass('EmotifyDropdownActive');
      frm.find('.EmoticonContainer').hide();
    });

    // Put the clicked emoticon into the textarea
    $('.EmoticonBox').live('click', function() {
      var emoticon = $(this).find('span').text();
      var textbox = $(this).parents('form').find('textarea#Form_Body');
      var txt = $(textbox).val();
      if (txt != '')
        txt += ' ';
      $(textbox).val(txt + emoticon + ' ');
      var container = $(this).parents('.EmoticonContainer');
      $(container).hide();
      $(container).prev().removeClass('EmotifyDropdownActive');
      
      // If cleditor is running, update it's contents
      var ed = $(textbox).get(0).editor;
      if (ed) {
        // Update the frame to match the contents of textarea
        ed.updateFrame();
        // Run emotify on the frame contents
        var Frame = $(ed.$frame).get(0);
        var FrameBody = null;
        var FrameDocument = null;
        
        // DOM
        if (Frame.contentDocument) {
           FrameDocument = Frame.contentDocument;
           FrameBody = FrameDocument.body;
        // IE
        } else if (Frame.contentWindow) {
           FrameDocument = Frame.contentWindow.document;
           FrameBody = FrameDocument.body;
        }
        $(FrameBody).html(emotify($(FrameBody).html()));
        var webRoot = gdn.definition('WebRoot', '');
        var ss = document.createElement("link");
        ss.type = "text/css";
        ss.rel = "stylesheet";
        ss.href = gdn.combinePaths(webRoot, 'plugins/Emotify/emotify.css');
        if (document.all)
           FrameDocument.createStyleSheet(ss.href);
        else
           FrameDocument.getElementsByTagName("head")[0].appendChild(ss);
      }

      return false;
    });
  });
  
});
