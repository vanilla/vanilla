jQuery(document).ready(function($) {
   
   // Ajax-test addons before enabling
   $('a.EnableAddon').click(function() {
      gdn.clearAddonErrors();
      
      var url = $(this).attr('href');
      var urlParts = url.split('/');
      var addonType = urlParts[urlParts.length - 3];
      if (addonType == 'plugins')
         addonType = 'Plugin';
      else if (addonType == 'applications')
         addonType = 'Application';
      else if (addonType == 'themes')
         addonType = 'Theme';
         
      if (addonType != 'Theme') {
         $('.TinyProgress').remove();
         $(this).after('<span class="TinyProgress">&nbsp;</span>');
      }
      var addonName = urlParts[urlParts.length - 2];
      var testUrl = gdn.combinePaths(
         gdn.definition('WebRoot'),
         'index.php?p=/dashboard/settings/testaddon/'+addonType+'/'+addonName+'/'+gdn.definition('TransientKey')+'&DeliveryType=JSON'
      );
      
      $.ajax({
         type: "GET",
         url: testUrl,
         dataType: 'html',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            // Remove any old errors from the form
            gdn.fillAddonErrors(XMLHttpRequest.responseText);
         },
         success: function(data) {
            if (data != 'Success') {
               gdn.fillAddonErrors(data);
            } else {
               document.location = url;
            }
         }
      });
      return false;
   });
   
   gdn.clearAddonErrors  = function() {
      $('div.TestAddonErrors:not(.Hidden)').remove();
      $('.TinyProgress').remove();
   }
   gdn.fillAddonErrors = function(errorMessage) {
      $('.TinyProgress').remove();
      err = $('div.TestAddonErrors');
      html = $(err).html();
      html = html.replace('%s', errorMessage);
      $(err).before('<div class="Messages Errors TestAddonErrors">' + html + '</div>');
      $('div.TestAddonErrors:first').removeClass('Hidden');
      // $(window).scrollTop($("div.TestAddonErrors").offset().top);
      $(window).scrollTop();
   }

   // Ajax-test addons before enabling
   $('a.PreviewAddon').click(function() {
      gdn.clearAddonErrors();
      
      var url = $(this).attr('href');
      var urlParts = url.split('/');
      var addonName = urlParts[urlParts.length - 1];
      var testUrl = gdn.combinePaths(
         gdn.definition('WebRoot'),
         'index.php?p=/dashboard/settings/testaddon/Theme/'+addonName+'/'+gdn.definition('TransientKey')+'&DeliveryType=JSON'
      );
      
      $.ajax({
         type: "GET",
         url: testUrl,
         dataType: 'html',
         error: function(XMLHttpRequest, textStatus, errorThrown) {
            // Remove any old errors from the form
            gdn.fillAddonErrors(XMLHttpRequest.responseText);
         },
         success: function(data) {
            if (data != 'Success') {
               gdn.fillAddonErrors(data);
            } else {
               document.location = url;
            }
         }
      });
      return false;
   });

   // Selection for theme styles.
   $('a.SelectThemeStyle').click(function(e) {
      e.preventDefault();

      var key = $(this).attr('key');

      // Deselect the current item.
      $('table.ThemeStyles td').removeClass('Active');

      // Select the new item.
      $(this).parents('td').addClass('Active');
      $('#Form_StyleKey').val(key);
      $(this).parents('form').submit();

      // $(this).blur();
      return false;
   });
});