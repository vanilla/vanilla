/**
 * Duplicate allows you to duplicate a "template row". Always keep your inputs to
 * be duplicated in their own container so the js knows what to add the new items
 * to.
 *
 * EXAMPLE 1: Duplicate a single input.
 * Html:
 * <div class="Options">
 *    <input class="OptionInput" type="text" name="input[]" placeholder="Add an option..." />
 * </div>
 * <a href="#" class="AddOption">Add another option...</a>
 *
 * Javascript:
 * $('.OptionInput').duplicate({addButton: '.AddOption'});
 *
 * EXAMPLE 2: Duplicate a group of inputs.
 * Html:
 * <div class="Options">
 *    <div class="Option">
 *       <input class="OptionInput1 NoIE" type="text" name="input1[]" placeholder="Add option 1..." />
 *       <input class="OptionInput2 NoIE" type="text" name="input2[]" placeholder="Add option 2..." />
 *    </div>
 * </div>
 * <a href="#" class="AddOption">Add another option...</a>
 *
 * Javascript:
 * $('.Option').duplicate({addButton: '.AddOption'});
 *
 */
jQuery(function($) {

   $.fn.duplicate = function (options) {
      var self = this;

      self.options = {
         addButton:           '',   // The button that adds another item when clicked.
         hideTemplate:        true, // Hide the template
         minItems:            2,    // The minimum number of template copies to display
         maxItems:            10,   // The maximum number of template copies to display (0 is infinite)
         hideButtonAfterMax:  true, // Hide the add button after you hit the max # of items
         autoAdd:             true, // Automatically add another when tabbing away from the last input in the last row
         curLength:           0
      };

      if (options) {
         $.extend(self.options, options);
      }

      var settings = self.options;

      var btn = settings.addButton,
         $btn = $(btn),
         tpl = this;

      // If more than one match was found, use the first one.
      if (tpl.length > 1) {
         // Make sure to add a new row on blur of the last row.
         if (tpl.length <= settings.maxItems) {
            var last = $(tpl[tpl.length-1]);
            var textbox = $(last).is('input,textarea') ? last : last.find('input,textarea').last();
            $(textbox).addClass('DuplicateAutoAddOnBlur');
         }

         tpl = $(tpl[0]); // Use the first one as the template.
      }

      // Don't do anything unless the required elements are present.
      if ($btn.length != 1 || !tpl || settings.minItems > settings.maxItems)
         return;

      // Get the container
      var container = $(tpl).parent();

      // Hide the template?
      if (settings.hideTemplate)
         tpl.hide();

      // Add an item on button click
      var noFocus = false;
      settings.curLength = container.children().length;
      if (settings.hideTemplate)
         settings.curLength = settings.curLength - 1;

      jQuery(document).on('click', btn, function(e) {
         e.stopPropagation();
         e.preventDefault();

         // Don't add more than we're allowed
         if (settings.curLength >= settings.maxItems)
            return false;

         container.append(tpl.clone().show());
         settings.curLength++;

         // Hide the button when we've reached our limit.
         if (settings.curLength >= settings.maxItems && settings.hideButtonAfterMax)
            $btn.hide();

         var lastItem = container.children().last(),
            textbox = null;

         // Focus on the first input in the newly added clone
         if (!noFocus) {
            textbox = $(lastItem).is('input,textarea') ? lastItem : lastItem.find('input,textarea').first();
            $(textbox).focus();
         }

         // Identify the last element in the row so it can be duplicated on blur.
         if (settings.autoAdd && settings.curLength < settings.maxItems) {
            // Remove the class from previously added rows
            $('.DuplicateAutoAddOnBlur').removeClass('DuplicateAutoAddOnBlur');
            // Add the class to this row
            textbox = $(lastItem).is('input,textarea') ? lastItem : lastItem.find('input,textarea').last();
            $(textbox).addClass('DuplicateAutoAddOnBlur');
         }
      });

      // Add a new row when the last input in the last row is blurred.
      $(document).on('blur', '.DuplicateAutoAddOnBlur', function() {
         if ($(this).val() != '')
            $btn.click();
      });

      // Add the minimum number of items
      if (settings.minItems > 0) {
         var counter = 0;
         while (settings.curLength < settings.minItems && counter < settings.minItems) {
            noFocus = true;
            counter++;
            $btn.click();
         }
      }

      noFocus = false;
   };
});
