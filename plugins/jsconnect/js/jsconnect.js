jQuery(document).ready(function($) {

var jsUrl = gdn.definition('JsAuthenticateUrl', false);
if (jsUrl) {
   // Reveal the please wait text after a small wait so that if the request is faster we never have to see it.
   setTimeout(function() { $('.Connect-Wait').show(); }, 2000);

   $.ajax({
      url: jsUrl,
      dataType: 'json',
      timeout : 10000,
      success: function(data) {
         var connectData = $.param(data);

         if ($('#Form_JsConnect-Connect').length > 0) {
            if (data['error']) {
               $('#Form_JsConnect-Connect').attr('action', gdn.url('/entry/jsconnect/error'));
            } else if (!data['uniqueid']) {
                // Just redirect to the target.
                var target = $('#Form_Target').val();
                if (!target)
                    target = '/';

                window.location.replace(gdn.url(target));
                return;
   //            data = {'error': 'unauthorized', 'message': 'You are not signed in.' };
//               $('#Form_JsConnect-Connect').attr('action', gdn.url('/entry/jsconnect/guest'));
            } else {
               for(var key in data) {
                  if (data[key] == null)
                     data[key] = '';
               }
            }
            $('#Form_JsConnect').val(connectData);
            $('#Form_JsConnect-Connect').submit();
         } else {
            if (!data['error'] && data['name']) {
               var parts = $.jsconnectStrip(jsUrl);

               $.ajax({
                  url: gdn.url('/entry/connect/jsconnect'),
                  type: 'POST',
                  data: { JsConnect: connectData }
               });
            }
         }
      },
      error: function(xhr, errorText) {
         var error = $.param({ error: errorText });
         $('#Form_JsConnect').val(error);
         $('#Form_JsConnect-Connect').attr('action', gdn.url('/entry/jsconnect/error'));
         $('#Form_JsConnect-Connect').submit();
      }
   });
}

$.jsconnectStrip = function(url) {
   var re = new RegExp("client_?id=([^&]+)(&Target=([^&]+))?", "g");
   var matches = re.exec(url);
   var client_id = false, target = '/';

   if (matches) {
      if (matches[1])
         client_id = matches[1];
      if (matches[3])
         target = matches[3];
   }

   return { client_id: client_id, target: target };
};

$.fn.jsconnect = function(options) {
   if (this.length == 0)
      return;

   var $elems = this;

   // Collect the urls.
   var urls = {};
   $elems.each(function(i, elem) {
      var rel = $(elem).attr('rel');

      if (urls[rel] == undefined)
         urls[rel] = [];
      urls[rel].push(elem);
   });

   for (var url in urls) {
      var elems = urls[url];

      // Get the client id from the url.
      var re = new RegExp("client_?id=([^&]+)(&Target=([^&]+))?", "g");
      var matches = re.exec(url);
      var client_id = false, target = '/';
      if (matches) {
         if (matches[1])
            client_id = matches[1];
         if (matches[3])
            target = matches[3];
      }

      // Make a request to the host page.
      $.ajax({
         url: url,
         dataType: 'json',
         success: function(data, textStatus) {
            var connectUrl = gdn.url('/entry/jsconnect?client_id='+client_id+'&Target='+target);

            var signedIn = data['name'] || data['signedin'] ? true : false;

            if (signedIn) {
               $(elems).find('.ConnectLink').attr('href', connectUrl);
               $(elems).find('.Username').text(data['name']);

               if (data['photourl'])
                  $(elems).find('.UserPhoto').attr('src', data['photourl']);

               $(elems).find('.JsConnect-Connect').show();
               $(elems).find('.JsConnect-Guest').hide();
            } else {
               $(elems).find('.JsConnect-Connect').hide();
               $(elems).find('.JsConnect-Guest').show();
            }
            $(elems).show();
         },
         error: function(data, x, y) {
            $(elems).find('.JsConnect-Connect').hide();
            $(elems).find('.JsConnect-Guest').show();

            $(elems).show();
         }
      });
   }
};

$('.JsConnect-Container').livequery(function() { $(this).jsconnect(); });

});
