jQuery(document).ready(function($) {
   // Test url rewrites.
   var $rewriteurls = $('input[name=RewriteUrls]');
   
   if (!$rewriteurls.val()) {
      $.ajax('setup/testurlrewrites', {
         success: function(data) {
            if (data == 'ok')
               $rewriteurls.val(1);
         }
      });
   }
});

