<?php
if(!defined('APPLICATION')) die();

/*
Plugin adds CLEditor (http://premiumsoftware.net/cleditor/) jQuery WYSIWYG to Vanilla 2

Included files:
1. jquery.cleditor.min.js (as v.1.3.0 - unchanged)
2. jquery.cleditor.css (as v.1.3.0 - unchanged)
3. images/toolbar.gif (as v.1.3.0 - unchanged)
4. images/buttons.gif (as v.1.3.0 - unchanged)

Changelog:
v0.1: 25AUG2010 - Initial release. 
- Known bugs: 
-- 1. Both HTML and WYSIWYG view are visible in 'Write comment' view. Quick fix: click HTML view button twice to toggle on/off.

Optional: Edit line 19 of jquery.cleditor.min.js to remove extra toolbar buttons.

v0.2: 29OCT2010 - by Mark @ Vanilla.
- Fixed:
-- 1. Removed autogrow from textbox. Caused previous bug of showing both html and wysiwyg.
-- 2. Disabled safestyles. Caused inline css to be ignored when rendering comments.
-- 3. Added livequery so textareas loaded on the fly (ie. during an inline edit) get wysiwyg.
-- 4. Upgraded to CLEditor 1.3.0

v0.3: 30OCT2010 - by Mark @ Vanilla
- Fixed:
-- 1. Adding a comment caused the textarea to be revealed and the wysiwyg to
retain the content just posted. Hooked into core js triggers to clear the
wysiwyg and re-hide the textbox.

v0.4: 30OCT2010 - by Mark @ Vanilla
- Fixed:
-- 1. Removed "preview" button since the wysiwyg *is* a preview, and it caused
some glitches.

v0.5: 02NOV2010 - by Tim @ Vanilla
- Fixed:
-- 1. Added backreference to the cleditor JS object and attached it to the textarea, for external interaction
 
v1.0.1 31AUG2011 - by Todd @ Vanilla
- Fixed:
-- 1. Fixed js error with new versions of jQuery.

v1.1 14SEPT2011 - by Linc @ Vanilla
-- Disabled CLEditor for IE6 or less if using Vanilla 2.0.18b5+.

v1.1.1 28SEPT2011 - Linc
-- Fixed infinite height loop confict with embed plugin.
 */

$PluginInfo['cleditor'] = array(
   'Name' => 'WYSIWYG (CLEditor)',
   'Description' => 'Adds a <a href="http://en.wikipedia.org/wiki/WYSIWYG">WYSIWYG</a> editor to your forum so that your users can enter rich text comments.',
   'Version' => '1.3.1.1',
   'Author' => "Mirabilia Media",
   'AuthorEmail' => 'info@mirabiliamedia.com',
   'AuthorUrl' => 'http://mirabiliamedia.com',
   'RequiredApplications' => array('Vanilla' => '>=2'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'RegisterPermissions' => FALSE,
   'SettingsUrl' => FALSE,
   'SettingsPermission' => FALSE
);

class cleditorPlugin extends Gdn_Plugin {

//	public function PostController_Render_Before($Sender) {
//		$this->_AddCLEditor($Sender);
//	}
//	
//	public function DiscussionController_Render_Before($Sender) {
//		$this->_AddCLEditor($Sender);
//	}

   public function Gdn_Dispatcher_AppStartup_Handler($Sender, $Args) {
      // Save in memory only so it does not persist after plugin is gone.
      SaveToConfig('Garden.Html.SafeStyles', FALSE, FALSE);
   }

   /**
    * @param AssetModel $Sender
    */
   public function AssetModel_StyleCss_Handler($Sender, $Args) {
      $Sender->AddCssFile('jquery.cleditor.css', 'plugins/cleditor');
   }
   
   /**
    *
    * @param Gdn_Form $Sender 
    */
   public function Gdn_Form_BeforeBodyBox_Handler($Sender, $Args) {
      $Column = GetValue('Column', $Args, 'Body');
      $this->_AddCLEditor(Gdn::Controller(), $Column);
      
      $Format = $Sender->GetValue('Format');
      
      if ($Format) {
         $Formatter = Gdn::Factory($Format.'Formatter');
         
         if ($Formatter && method_exists($Formatter, 'FormatForWysiwyg')) {
            $Body = $Formatter->FormatForWysiwyg($Sender->GetValue($Column));
            $Sender->SetValue($Column, $Body);
         } elseif (!in_array($Format, array('Html', 'Wysiwyg'))) {
            $Sender->SetValue($Column, Gdn_Format::To($Sender->GetValue($Column), $Format));
         }
      }
      $Sender->SetValue('Format', 'Wysiwyg');
   }
   
   public function AddClEditor() {
      $this->_AddCLEditor(Gdn::Controller());
   }
	
	private function _AddCLEditor($Sender, $Column = 'Body') {
      static $Added = FALSE;
      if ($Added)
         return;
      
		// Add the CLEditor to the form
		$Options = array('ie' => 'gt IE 6', 'notie' => TRUE); // Exclude IE6
		$Sender->RemoveJsFile('jquery.autogrow.js');
		$Sender->AddJsFile('jquery.cleditor'.(Debug() ? '' : '.min').'.js', 'plugins/cleditor', $Options);
      
      $CssInfo = AssetModel::CssPath('cleditor.css', 'plugins/cleditor');
      
      if ($CssInfo) {
         $CssPath = Asset($CssInfo[1]);
      }
      
		$Sender->Head->AddString(<<<EOT
<style type="text/css">
a.PreviewButton {
	display: none !important;
}
</style>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		// Make sure the removal of autogrow does not break anything
		$.fn.autogrow = function(o) { return; }
		// Attach the editor to comment boxes.
		$("textarea.BodyBox").livequery(function() {
			var frm = $(this).closest("form");
			ed = jQuery(this).cleditor({
            width:"100%", height:"100%",
            controls: "bold italic strikethrough | font size " +
                    "style | color highlight removeformat | bullets numbering | outdent indent | " +
                    "alignleft center alignright | undo redo | " +
                    "image link unlink | pastetext source",
            docType: '<!DOCTYPE html>',
            docCSSFile: "$CssPath"
         })[0];
			this.editor = ed; // Support other plugins!
			jQuery(frm).bind("clearCommentForm", {editor:ed}, function(e) {
				frm.find("textarea").hide();
				e.data.editor.clear();
			});
		});
	});
</script>
EOT
);
      $Added = TRUE;
   }
   
   public function PostController_Quote_Before($Sender, $Args) {
      // Make sure quotes know that we are hijacking the format to wysiwyg.
      if (!C('Garden.ForceInputFormatter'))
         SaveToConfig('Garden.InputFormatter', 'Wysiwyg', FALSE);
   }

	public function Setup() {
      $this->Structure();
   }
   
   public function Structure() {

   }
}
