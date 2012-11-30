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
            
      if ($('div.DashboardSummaries div.Loading').length == 0)
         $('div.DashboardSummaries').html('<div class="Loading"></div>');

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
      
      $.ajax({
         url: gdn.url('/index.php?p=/dashboard/settings/dashboardsummaries&DeliveryType=VIEW&Range='+range+'&DateRange='+dateRange),
         success: function(data) {
            $('div.DashboardSummaries').html(data);
         },
         error: function(xhr, status, error) {
            $('div.DashboardSummaries').html('<div class="NoStats">Remote Analytics Server request failed.</div>');
            $('div.DashboardSummaries div.NoStats').css('padding','10px');
         },
         timeout: 15000 // 15 seconds in ms
      });
      
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
   $('input.DateRange').live('change', function() {
      getData();
   });
   
});