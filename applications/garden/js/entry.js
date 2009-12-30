// This file contains javascript that is specific to the garden/entry controller.
jQuery(document).ready(function($) {
   
   // Check to see if the selected username is valid
   $('#Register input[name=User/Name], body.register input[name=User/Name]').blur(function() {
      var name = $(this).val();
      if (name != '') {
         var checkUrl = combinePaths(
            definition('WebRoot', ''),
            'index.php/garden/utility/usernameavailable/'+encodeURIComponent(name)
         );
         $.ajax({
            type: "GET",
            url: checkUrl,
            dataType: 'text',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               $.popup({}, definition('TransportError').replace('%s', textStatus));
            },
            success: function(text) {
               if (text == 'TRUE')
                  $('#NameUnavailable').hide();
               else
                  $('#NameUnavailable').show();
            }
         });
      }
   });
   
   // Check to see if passwords match
   $('input[name=User/PasswordMatch]').blur(function() {
      if ($('#Register input[name=User/Password], body.register input[name=User/Password]').val() == $(this).val())
         $('#PasswordsDontMatch').hide();
      else
         $('#PasswordsDontMatch').show();
   });
});