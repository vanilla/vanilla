$(function() {
   statsUrl = gdn.definition('VanillaStatsUrl', 'http://analytics.vanillaforums.com');
   
   frame = function() {
      var frame = document.getElementById('VanillaStatsGraph');
      return !frame ? null : frame;
   }
   
   function getData() {
      // Add spinners
      if ($('#Content h1 span').length == 0)
         $('<span class="TinyProgress"></span>').appendTo('#Content h1:last');
            
      if ($('div.DashboardSummaries div.Loading').length == 0)
         $('div.DashboardSummaries').html('<div class="Loading"></div>');

      // Grab the ranges and installation id
      var range = $('input.Range').val(),
         dateRange = $('input.DateRange').val(),
         vanillaId = $('input.VanillaID').val();
         
      // Load the graph data
      frame().src = statsUrl
         +'/graph/'
         +'?VanillaID=' + vanillaId
         +'&Range=' + range
         +'&DateRange=' + dateRange;
      
      // Load summary data
      $.get(gdn.url('/dashboard/settings/dashboardsummaries&DeliveryType=VIEW&Range='+range+'&DateRange='+dateRange), function(data) {
         $('div.DashboardSummaries').html(data);
      });
      
      // Remove Spinners
      $('#Content h1 span.TinyProgress').remove();
   }

   // Draw the graph when the window is loaded.
   window.onload = function() {
      getData();
   }

   // Redraw the grpah when the window is resized
   $(window).resize(function() {
      getData();
   });

   // Redraw the graph if the date range changes
   $('input.DateRange').live('change', function() {
      getData();
   });
   
});