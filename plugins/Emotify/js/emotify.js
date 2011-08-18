$(function(){
  
  // Insert a clickable icon list after the textbox
  $('textarea#Form_Body').livequery(function() {
	 // Pick up the emoticons from the def list
	var emoticons = gdn.definition('Emoticons', false);

	if (emoticons)
		emoticons = eval("("+$.base64Decode(emoticons)+")");
	 
    var buts = '';
	 var last = '';
    for (e in emoticons) {
		// no duplicates
		if (last != emoticons[e]) {
		  last = emoticons[e];
		  buts += '<a class="EmoticonBox Emoticon Emoticon'+emoticons[e]+'"><span>'+e+'</span></a>';
		}
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
