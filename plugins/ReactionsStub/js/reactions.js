jQuery(document).ready(function($) {
   
   if ($.fn.expander)
      $('.Expander').expander({slicePoint: 200, expandText: gdn.definition('ExpandText'), userCollapseText: gdn.definition('CollapseText')});
   
   $(document).delegate('.Buried', 'click', function(e) {
      e.preventDefault();
      $(this).removeClass('Buried').addClass('Un-Buried');
      return false;
   });
   
   $(document).delegate('.Un-Buried', 'click', function() {
      $(this).removeClass('Un-Buried').addClass('Buried');
   });
});