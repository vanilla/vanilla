jQuery(document).ready(function($) {
   // Hide/show the appropriate repeat options.
   var revealRepeatOptions = function() {
      // Get the current value of the repeat options.
      var selected = $("input[name$=RepeatType]:checked").val();
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

   var toggleRepeat = function() {
      var selected = $("select[name$=Location] option:selected").text();
      switch (selected) {
         case 'Custom':
            $('.js-repeat').hide();
            break;
         default:
            $('.js-repeat').show();
      }
   }

   $("select[name$=Location]").change(toggleRepeat);
   $(document).ready(toggleRepeat);

   $("input[name$=RepeatType]").click(revealRepeatOptions);

   revealRepeatOptions();

   // Hide/show the appropriate location elements.
   var revealLocationElements = function() {
      var selected = $("select[name$=Location]").val();
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

   $("select[name$=Location]").change(revealLocationElements);
   revealLocationElements();
});
