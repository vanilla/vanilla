// This file contains javascript that is specific to the dashboard/entry controller.
jQuery(document).ready(function($) {
   // Check to see if the selected email is valid
   $('#Register input[name=User/Email], body.register input[name=User/Email]').blur(function() {
      var email = $(this).val();
      if (email != '') {
         var checkUrl = gdn.combinePaths(
            gdn.definition('WebRoot', ''),
            'index.php?p=/dashboard/user/emailavailable/'+encodeURIComponent(email)+'/x'
         );
         $.ajax({
            type: "GET",
            url: checkUrl,
            dataType: 'text',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               $.popup({}, XMLHttpRequest.responseText);
            },
            success: function(text) {
               if (text == 'TRUE')
                  $('#EmailUnavailable').hide();
               else
                  $('#EmailUnavailable').show();
            }
         });
      }
   });
	
   // Check to see if the selected username is valid
   $('#Register input[name=User/Name], body.register input[name=User/Name]').blur(function() {
      var name = $(this).val();
      if (name != '') {
         var checkUrl = gdn.combinePaths(
            gdn.definition('WebRoot', ''),
            'index.php?p=/dashboard/user/usernameavailable/'+encodeURIComponent(name)
         );
         $.ajax({
            type: "GET",
            url: checkUrl,
            dataType: 'text',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
               $.popup({}, XMLHttpRequest.responseText);
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