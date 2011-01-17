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
         vanillaId = $('input.VanillaID').val(),
         securityHash = $('input.SecurityHash').val(),
         requestTime = $('input.RequestTime').val();
         
      // Load the graph data
      frame().src = statsUrl
         +'/graph/'
         +'?VanillaID=' + vanillaId
         +'&SecurityHash=' + securityHash
         +'&RequestTime=' + requestTime
         +'&Range=' + range
         +'&DateRange=' + dateRange;
      
      // Load summary data
/*
      $.get(gdn.url('/dashboard/settings/dashboardsummaries&DeliveryType=VIEW&Range='+range+'&DateRange='+dateRange), function(data) {
         $('div.DashboardSummaries').html(data);
      });
*/
      
      $.ajax({
         url: gdn.url('/dashboard/settings/dashboardsummaries&DeliveryType=VIEW&Range='+range+'&DateRange='+dateRange),
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
   
   function manageHash(skip) {
      if (!skip) {
         $.ajax({
            url: gdn.url('/dashboard/settings/dashboardrefreshhash.json'),
            success: function(data) {
               $('input.SecurityHash').val(data.SecurityHash);
               $('input.RequestTime').val(data.RequestTime);
            },
            dataType: 'json'
         });
      }
      
      // Get a new code every 120 seconds
      setTimeout(manageHash,(120*1000));
   }

   // Draw the graph when the window is loaded.
   window.onload = function() {
      getData();
      manageHash(true);
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