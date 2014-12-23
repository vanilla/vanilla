/* 	Copyright 2013-2014 Zachary Doll
 * 	This program is free software: you can redistribute it and/or modify
 * 	it under the terms of the GNU General Public License as published by
 * 	the Free Software Foundation, either version 3 of the License, or
 * 	(at your option) any later version.
 *
 * 	This program is distributed in the hope that it will be useful,
 * 	but WITHOUT ANY WARRANTY; without even the implied warranty of
 * 	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * 	GNU General Public License for more details.
 *
 * 	You should have received a copy of the GNU General Public License
 * 	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
jQuery(document).ready(function($) {
  // Get strings from definitions
  var ResultString = gdn.definition('DP_ShowResults');
  var FormString = gdn.definition('DP_ShowForm');
  var ConfirmString = gdn.definition('DP_ConfirmDelete');

  // hijack the results click
  $('#DP_Results a').click(function(event) {
    event.preventDefault();

    if ($(this).html() === ResultString) {
      var btn = this;
      // Load from ajax if they don't exist
      if ($('.DP_ResultsForm').length === 0) {
        // Load Results from ajax
        $.ajax({
          url: $(btn).attr('href'),
          global: false,
          type: 'GET',
          data: 'DeliveryType=VIEW',
          dataType: 'json',
          beforeSend: function() {
            // add a spinner
            $(btn).after('<span class="DP_Spinner TinyProgress">&nbsp;</span>');
          },
          success: function(Data) {
            $('.DP_AnswerForm').after(Data.html);
            $('.DP_ResultsForm').hide();

            // Repeated here to account for slow hosts
            $('.DP_AnswerForm').fadeOut('slow', function() {
              $('.DP_ResultsForm').fadeIn('slow', function() {
                // Change tool mode
                $(btn).html(FormString);
              });
            });
          },
          complete: function() {
            $('.DP_Spinner').remove();
          }
        });
      }
      else {
        // Bring results to front
        $('.DP_AnswerForm').fadeOut('slow', function() {
          $('.DP_ResultsForm').fadeIn('slow', function() {
            // Change tool mode
            $(btn).html(FormString);
          });
        });
      }
    }
    else {
      // Bring poll form to front
      $('.DP_ResultsForm').fadeOut('slow', function() {
        $('.DP_AnswerForm').fadeIn('slow');
      });

      // Change tool mode
      $(this).html(ResultString);
    }
  });

  // hijack the submission click
  $('.DP_AnswerForm form').submit(function(event) {
    event.preventDefault();
    // Load the result from ajax
    $.ajax({
      url: $(this).attr('action'),
      global: false,
      type: $(this).attr('method'),
      data: $(this).serialize() + '&DeliveryType=VIEW',
      dataType: 'json',
      beforeSend: function() {
        // add a spinner
        $('.DP_AnswerForm .Buttons').append('<span class="DP_Spinner TinyProgress">&nbsp;</span>');
      },
      success: function(Data) {
        switch (Data.type) {
          case 'Full Poll':
            // Remove the old results form
            if ($('.DP_ResultsForm').length !== 0) {
              $('.DP_ResultsForm').remove();
            }
            // Insert the new results
            $('.DP_AnswerForm').after(Data.html);
            $('.DP_ResultsForm').hide();

            // Remove the answer form after some sweet sweet animation
            $('.DP_AnswerForm').fadeOut('slow', function() {
              $('.DP_ResultsForm').fadeIn('slow', function() {
                $('.DP_AnswerForm').remove();
              });
            });

            // update tools
            $('#DP_Results').slideUp();
            break;
          default:
          case 'Partial Poll':
            gdn.informMessage(Data.html);
            break;
        }
      },
      complete: function() {
        $('.DP_Spinner').remove();
      }
    });
  });

  // hijack the delete click
  $('#DP_Remove a').popup({
    confirm: true,
    confirmText: ConfirmString,
    followConfirm: false,
    afterConfirm: function(json, sender) {
      // Remove all poll tools and forms
      $('.DP_AnswerForm').slideUp('slow', function() {
        $(this).remove();
      });
      $('.DP_ResultsForm').slideUp('slow', function() {
        $(this).remove();
      });
      $('#DP_Tools').slideUp('slow', function() {
        $(this).remove();
      });
    }
  });
});