jQuery(document).ready(function($) {
  
  function AutoAuthenticate(Url){
    $.ajax({
      url: Url,
      dataType: 'json',
      success: function(data, textStatus) {
        $.ajax({
          url: data.JsAuthenticateUrl,
          dataType: 'json',
          success: function(data) {
            
            var action = '';
            if (data['error']) {
              action = gdn.url('/entry/jsconnect/error');
            } else if (!data['name']) {
              //data = {'error': 'unauthorized', 'message': 'You are not signed in.' };
              //action = gdn.url('/entry/jsconnect/guest');
              return;
              
            } else {
              for(var key in data) {
                if (data[key] == null)
                  data[key] = '';
              }
              target = location.search.match(/Target=([^&]+)/);
              action = gdn.url('/entry/connect/jsconnect?client_id='+data['client_id']+(target ? '&'+target[0] :''));
            }
            
            
            var smokescreen = $(
              '<div id="smokescreen-panel" class="Popup">'+
                '<div class="Border">'+
                  '<div id="smokescreen-panel-box" class="Body">'+
                  '</div>'+
                '</div>'+
              '</div>'+
              '<div id="smokescreen"> </div>'
            );
            
            $(document.body).append(smokescreen);
            
            $('#smokescreen-panel-box').append('<h1 style="text-align: center;">'+gdn.definition('Connecting')+'</h1>');
            $('#smokescreen-panel-box').append(('<p class="Message">'+gdn.definition('ConnectingUser')+'</p>').replace(/%/,$(this).children('.Username').text()));
            $('#smokescreen-panel-box').append('<div class="Progress"></div><br />');
            
            $("#smokescreen, #smokescreen-panel").show();  
            setTimeout(function(){$("#smokescreen, #smokescreen-panel").hide();},1000*60);
                      
            var jsConnectForm = $('<form>').attr({
                        'id':'jsConnectAuto',
                        'method':'post',
                        //'style':'display:none;',
                        'action':action
                      });
                      
            jsConnectForm.append($('<input type="hidden" name="Form/JsConnect" />').val($.param(data)));
            jsConnectForm.append($('<input type="hidden" name="Form/Target" />').val(document.location.toString()));
            jsConnectForm.append($('<input type="hidden" name="Form/TransientKey" />').val(gdn.definition('TransientKey')));
            jsConnectForm.find('input').each(function(){
              if($(this).attr('name').match(/^Form\//)!=-1){
                jsConnectForm.append($('<input type="hidden" name="'+$(this).attr('name').replace(/^Form\//,'')+'" />').val($(this).val()));
              }
            });
              
          
            $(document.body).append(jsConnectForm);
            $('#jsConnectAuto').submit();
          },
          error: function(data, x, y) {
            //$('form').attr('action', gdn.url('/entry/jsconnect/error'));
          }
          });
      }
    });
  }
   
  function jsconnectAuto(provider) {
    var connectUrl = gdn.url('/entry.json/jsconnectauto?client_id='+provider['AuthenticationKey']+'&Target='+provider['Target']);
    AutoAuthenticate(connectUrl);
  };

  var providers = $.parseJSON(gdn.definition('JsConnectProviders'));

  if(providers.length){
    provider = providers[0];
    jsconnectAuto(provider);
  }

});
