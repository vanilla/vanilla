jQuery(document).ready(function($){
   statsUrl = gdn.definition('VanillaStatsUrl', '//analytics.vanillaforums.com');

   frame = function() {
      var frame = document.getElementById('VanillaStatsGraph');
      return !frame ? null : frame;
   }

   function getData() {
      // Add spinners
      if ($('#Content h1 span').length == 0)
         $('<span class="TinyProgress"></span>').appendTo('#Content h1:last');

      // Grab the installation id, version, and token
      var vanillaId = $('input.VanillaID').val();
      var vanillaVersion = $('input.VanillaVersion').val();
      var securityToken = $('input.SecurityToken').val();

      // Grab the ranges and
      var range = $('input.Range').val();
      var dateRange = $('input.DateRange').val();

      // Load the graph data
      // REMOTE QUERY
      frame().src = statsUrl
         +'/graph/'
         +'?VanillaID=' + vanillaId
         +'&VanillaVersion=' + vanillaVersion
         +'&SecurityToken=' + securityToken
         +'&Range=' + range
         +'&DateRange=' + dateRange

      // Load the summary data
      // LOCAL QUERY
      var range = $('input.Range').val();
      var dateRange = $('input.DateRange').val();

      // Remove Spinners
      $('#Content h1 span.TinyProgress').remove();
   }

   // Draw the graph when the window is loaded.
   window.onload = function() {
      getData();
   }

   // Redraw the graph when the window is resized
   $(window).resize(function() {
      getData();
   });

   // Redraw the graph if the date range changes
   $(document).on('change', 'input.DateRange', function() {
      getData();
   });

});