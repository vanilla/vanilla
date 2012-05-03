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
 *       <input class="OptionInput1" type="text" name="input1[]" placeholder="Add option 1..." />
 *       <input class="OptionInput2" type="text" name="input2[]" placeholder="Add option 2..." />
 *    </div>
 * </div>
 * <a href="#" class="AddOption">Add another option...</a>
 * 
 * Javascript:
 * $('.Option').duplicate({addButton: '.AddOption'});
 * 
 */
jQuery(document).ready(function($) {
   
   $.fn.duplicate = function(options) {
      var settings = {
         addButton:           '', // The button that adds another item when clicked.
         hideTemplate:        true, // Hide the template
         minItems:            2, // The minimum number of template copies to display
         maxItems:            10, // The maximum number of template copies to display (0 is infinite)
         hideButtonAfterMax:  true, // Hide the add button after you hit the max # of items
         autoAdd:             true // Automatically add another when tabbing away from the last input in the last row
      }      
      settings = $.extend({}, settings, options);
      var btn = $(settings.addButton),
         tpl = this;

      // Don't do anything unless the required elements are present.
      if (btn.length != 1 || tpl.length != 1 || settings.minItems > settings.maxItems)
         return;

      // Get the container
      var container = $(tpl).parent();
         
      // Hide the template?
      if (settings.hideTemplate)
         tpl.hide();

      // Add an item on button click   
      var length = 0, noFocus = false;
      btn.live('click', function() {
         // Don't add more than we're allowed
         if (length >= settings.maxItems)
            return false;
         
         container.append(tpl.clone().show());
         length++;

         // Hide the button when we've reached our limit.
         if (length >= settings.maxItems && settings.hideButtonAfterMax)
            btn.hide();
         
         var lastItem = container.find($(tpl).selector).last(),
            textbox = null;
            
         // Focus on the first input in the newly added clone
         if (!noFocus) {
            textbox = $(lastItem).is('input,textarea') ? lastItem : lastItem.find('input,textarea').first();
            $(textbox).focus();
         }
         
         // Identify the last element in the row so it can be duplicated on blur.
         if (settings.autoAdd && length < settings.maxItems) {
            // Remove the class from previously added rows
            $('.DuplicateAutoAddOnBlur').removeClass('DuplicateAutoAddOnBlur'); 
            // Add the class to this row
            textbox = $(lastItem).is('input,textarea') ? lastItem : lastItem.find('input,textarea').last();
            $(textbox).addClass('DuplicateAutoAddOnBlur');
         }
         
         return false;
      });
      // Add a new row when the last input in the last row is blurred.
      $('.DuplicateAutoAddOnBlur').live('blur', function() {
         btn.click();
      });

      // Add the minimum number of items
      if (settings.minItems > 0)
         for (i = 0; i < settings.minItems; i++) {
            noFocus = true;
            btn.click();
         }
      
      noFocus = false;
   }
});