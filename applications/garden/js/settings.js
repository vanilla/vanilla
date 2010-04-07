jQuery(document).ready(function($) {
   
   // Phone-home to the VanillaForums server to check for updates
   var updateChecks = definition('UpdateChecks', '');
   if (updateChecks != '') {
      var webroot = definition('WebRoot', '');
      var data = 'source='+webroot
         +'&users='+definition('CountUsers', 0)
         +'&conversations='+definition('CountConversations', 0)
         +'&messages='+definition('CountConversationMessages', 0)
         +'&discussions='+definition('CountDiscussions', 0)
         +'&comments='+definition('CountComments', 0)
         +'&updateChecks='+updateChecks

      $.ajax({
         type: "POST",
         url: combinePaths(webroot, 'index.php/garden/utility/updateproxy'),
         data: data,
         dataType: 'json',
         success: function(json) {
            if (json.messages != '' || json.response != '') {
               // Save the message
               $.ajax({
                  type: "POST",
                  url: combinePaths(webroot, 'index.php/garden/utility/updateresponse'),
                  data: 'Messages='+json.messages+'&Response='+json.response+'&TransientKey='+definition('TransientKey'),
                  success: function() {
                     // After the responses have been saved, re-fill the
                     // #Content with this page's view (in case there are any
                     // messages to be displayed)
                     if (json.messages != '')
                        $('#Content').load(
                           combinePaths(webroot, 'index.php/garden/settings/index'),
                           'DeliveryType=ASSET&DeliveryMethod=XHTML'
                        );
                  }
               });
            }
         }
      });
   }
});