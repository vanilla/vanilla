jQuery(document).ready(function($) {
   // Hide/show the appropriate repeat options.
   var revealRepeatOptions = function() {
      // Get the current value of the repeat options.
      var selected = $("input[name=Pocket/RepeatType]:checked").val();
      switch (selected) {
         case 'every':
            $('.RepeatEveryOptions').show();
            $('.RepeatIndexesOptions').hide();
            break;
         case 'index':
            $('.RepeatEveryOptions').hide();
            $('.RepeatIndexesOptions').show();
            break;
         default:
            $('.RepeatEveryOptions').hide();
            $('.RepeatIndexesOptions').hide();
            break;
      }
   };

   $("input[name=Pocket/RepeatType]").click(revealRepeatOptions);

   revealRepeatOptions();

   // Hide/show the appropriate location elements.
   var revealLocationElements = function() {
      var selected = $("select[name=Pocket/Location]").val();
      // Select everything.
      var $hide;
      if (selected != "") {
         $hide = $(".LocationInfo:not(." + selected + "Info)");
         var $show = $("." + selected + "Info");
         $show.show();
      } else {
         $hide = $(".LocationInfo");
      }

      $hide.hide();
   };

   $("select[name=Pocket/Location]").change(revealLocationElements);
   revealLocationElements();
});
