// Enable multicomplete on selected inputs.
jQuery(document).ready(function($) {
   var toggleWhisperForm = function(first) {
      var whisper = $('#Form_Whisper:checked').val();
      if (whisper)
         $('#WhisperForm').slideDown('fast', 'swing');
      else if (first == true)
         $('#WhisperForm').hide();
      else
         $('#WhisperForm').slideUp('fast', 'swing');
   };
   toggleWhisperForm(true);

   $(document).on('click', '#Form_Whisper', toggleWhisperForm);



   $('.MultiComplete').livequery(function() {
      $(this).autocomplete(
         gdn.url('/dashboard/user/autocomplete/'),
         {
            minChars: 1,
            multiple: true,
            scrollHeight: 220,
            selectFirst: true
         }
      ); //.autogrow();
   });
});