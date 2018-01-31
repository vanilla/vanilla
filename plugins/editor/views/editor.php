<?php

$format = strtolower($this->data('_EditorInputFormat'));
$html_toolbar = ''; // for regular text

$css_ismobile_class = (isMobile())
   ? 'editor-mobile'
   : 'editor-desktop';

$html_toolbar = '<div class="editor editor-format-'.$format.' '.$css_ismobile_class.'">';
$html_arrow_down = '<span class="icon icon-caret-down" aria-hidden="true"></span>';
$editor_file_input_name = $this->data('_editorFileInputName');

foreach ($this->data('_EditorToolbar') as $button) {
    $title =  valr('attr.title', $button);
    $screenReaderMarkup = $title ? '<span class="sr-only">' . $title . '</span>' : '';

   // If the type is not an array, it's a regular button (type==button)
   if (!is_array($button['type'])) {

        $buttonMarkup = '';


        if($button['type'] == 'separator') {
           $button['attr']['aria-hidden'] = "true";
           $button['attr']['role'] = "presentation";
        } else {
           $button['attr']['tabindex'] = "0";
           $button['attr']['role'] = "button";
           $buttonMarkup = $screenReaderMarkup;
        }

        $html_toolbar .= wrap($buttonMarkup, 'span', $button['attr'] );
   } else {
      // Else this button has dropdown options, so generate them
      $html_button_dropdown_options = '';

      foreach ($button['type'] as $type_key => $button_option) {

         // If any text, use it
         $action_text = (isset($button_option['text']))
            ? $button_option['text']
            : '';

         // If the dropdown child elements require a different tag,
         // specify it in the array, then grab it here, otherwise
         // use the default, being a span.
         $html_tag = (isset($button_option['html_tag']))
            ? $button_option['html_tag']
            : 'span';

         // Concatenate child elements
         if (isset($button_option['attr'])) {
            $html_button_dropdown_options .= wrap($action_text, $html_tag, $button_option['attr']);
         }
      }



      switch ($button['action']) {

         case 'link':
            $html_toolbar .= wrap(
               wrap($html_arrow_down.$screenReaderMarkup, 'span', $button['attr']).''.
               '<div class="editor-insert-dialog Flyout MenuItems" data-wysihtml5-dialog="createLink">
                     <input class="InputBox editor-input-url" data-wysihtml5-dialog-field="href" value="http://" />
                      <div class="MenuButtons">
                      <input type="button" data-wysihtml5-dialog-action="save" class="Button Flyout-Button editor-dialog-fire-close" value="'.t('OK').'"/>
                      <input type="button" data-wysihtml5-dialog-action="cancel" class="Button Flyout-Button Cancel editor-dialog-fire-close" value="'.t('Cancel').'"/>
                      </div>
                   </div>'
               , 'div', ['class' => 'editor-dropdown editor-dropdown-link']);
            break;

         case 'image':
            $html_toolbar .= wrap(
               wrap($html_arrow_down.$screenReaderMarkup, 'span', $button['attr']).''.
               '<div class="editor-insert-dialog Flyout MenuItems editor-file-image editor-insert-image" data-wysihtml5-dialog="insertImage">
                      <div class="drop-section image-input" title="'.t('Paste the URL of an image to quickly embed it.').'">
                        <input class="InputBox editor-input-image" placeholder="'.t('Image URL').'" />
                     </div>
                  </div>'
               , 'div', ['class' => 'editor-dropdown editor-dropdown-image']);
            break;

         case 'fileupload':
            $accept = $this->data('Accept', '');
            $html_toolbar .= wrap(
                wrap($html_arrow_down.$screenReaderMarkup, 'span', $button['attr']).''.
                '<div class="editor-insert-dialog Flyout MenuItems editor-file-image">
                     <div class="file-title">'.t('Attach a file').' 
                        <span class="js-can-drop info">'.t('you can also drag-and-drop').'</span>
                     </div>
                     <div class="dd-separator" role="presentation"></div>
                     <div class="file-input">
                        <input type="file" name="'.$editor_file_input_name.'[]" multiple data-upload-type="file" accept="'.$accept.'" />
                     </div>
                  </div>'
                , 'div', ['class' => 'editor-dropdown editor-dropdown-upload']);
            break;

         case 'imageupload':
            $accept = $this->data('AcceptImage', '');
            $html_toolbar .= wrap(
                wrap($html_arrow_down.$screenReaderMarkup, 'span', $button['attr']).''.
                '<div class="editor-insert-dialog Flyout MenuItems editor-file-image">
                     <div class="file-title">'.t('Insert an image').' 
                        <span class="js-can-drop info">'.t('you can also drag-and-drop').'</span>
                     </div>
                     <div class="dd-separator" role="presentation"></div>
                     <div class="file-input">
                        <input type="file" name="'.$editor_file_input_name.'[]" multiple data-upload-type="image" accept="'.$accept.'" />
                     </div>
                     <div class="dd-separator" role="presentation"></div>
                     <div class="image-input" title="'.t('Paste the URL of an image to quickly embed it.').'">
                        <input class="InputBox editor-input-image" placeholder="'.t('Image URL').'" />
                     </div>
                  </div>'
                , 'div', ['class' => 'editor-dropdown editor-dropdown-upload']);
            break;

         case 'color':

            $colorType = $button['type'];

            $textColorOptions = '';
            if (isset($colorType['text'])) {
               foreach ($colorType['text'] as $textColor) {
                  $textColorOptions .= wrap($screenReaderMarkup, $textColor['html_tag'], $textColor['attr']);
               }

               if ($textColorOptions) {
                  $textColorOptions = '<div class="color-group text-color ClearFix"><i class="icon icon-font" title="Text"></i>'.$textColorOptions.'</div>';
               }
            }

            $highlightColorOptions = '';
            if (isset($colorType['highlight'])) {
               foreach ($colorType['highlight'] as $highlightColor) {
                  $highlightColorOptions .= wrap($screenReaderMarkup, $highlightColor['html_tag'], $highlightColor['attr']);
               }

               if ($highlightColorOptions) {
                  $highlightColorOptions = '<div class="color-group highlight-color ClearFix"><i class="icon icon-sign-blank" title="Highlight"></i>'.$highlightColorOptions.'</div>';
               }
            }

            $cssHasHighlight = ($highlightColorOptions)
               ? 'color-has-highlight'
               : '';

            $colorOptions = $textColorOptions.$highlightColorOptions;

            $html_toolbar .= wrap(
               wrap($html_arrow_down.$screenReaderMarkup, 'span', $button['attr']).''.
               wrap($colorOptions, 'div', ['class' => 'editor-insert-dialog Flyout MenuItems', 'data-wysihtml5-dialog' => ''])
               , 'div', ['class' => "editor-dropdown editor-dropdown-color $cssHasHighlight"]
            );
            break;

         // All other dropdowns (color, format, emoji)
         default:
            $html_toolbar .= wrap(
               wrap($html_arrow_down.$screenReaderMarkup, 'span', $button['attr']).''.
               wrap($html_button_dropdown_options, 'div', ['class' => 'editor-insert-dialog Flyout MenuItems', 'data-wysihtml5-dialog' => ''])
               , 'div', ['class' => 'editor-dropdown editor-dropdown-default editor-action-'.$button['action']]);
            break;
      }
   }
}

$html_toolbar .= '</div>';

// Add progress meter for file uploads.
$html_toolbar .= '<div class="editor-upload-progress"></div>';

// Add drop message when dragging over dropzone. Only display when
// dragging over element.
$html_toolbar .= '<div class="editor-upload-attention">'.t('Drop image/file').'</div>';

// Generate output for view
echo $html_toolbar;
