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
*/

$PluginInfo['cleditor'] = array(
   'Name' => 'CLEditor jQuery WYSIWYG',
   'Description' => '<a href="http://premiumsoftware.net/cleditor/" target="_blank">CLEditor</a> jQuery WYSIWYG plugin for Vanilla 2.',
   'Version' => '0.5',
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

	public function PostController_Render_Before(&$Sender) {
		$this->_AddCLEditor($Sender);
	}
	
	public function DiscussionController_Render_Before(&$Sender) {
		$this->_AddCLEditor($Sender);
	}
	
	private function _AddCLEditor($Sender) {
		// Turn off safestyles so the inline styles get applied to comments
		$Config = Gdn::Factory(Gdn::AliasConfig);
		$Config->Set('Garden.Html.SafeStyles', FALSE);
		
		// Add the CLEditor to the form
		$Sender->RemoveJsFile('jquery.autogrow.js');
		$Sender->AddJsFile($this->GetResource('jquery.cleditor.min.js', FALSE, FALSE));
		$Sender->AddCssFile($this->GetResource('jquery.cleditor.css', FALSE, FALSE));
		$Sender->Head->AddString('
<style type="text/css">
a.PreviewButton {
	display: none !important;
}
</style>
<script type="text/javascript">
	(function(jQuery) {
		// Make sure the removal of autogrow does not break anything
		jQuery.fn.autogrow = function(o) { return; }
		// Attach the editor to comment boxes
		jQuery("#Form_Body").livequery(function() {
			var frm = $(this).parents("div.CommentForm");
			ed = jQuery(this).cleditor({width:"100%", height:"100%"})[0];
			this.editor = ed; // Support other plugins!
			jQuery(frm).bind("clearCommentForm", {editor:ed}, function(e) {
				frm.find("textarea").hide();
				e.data.editor.clear();
			});
		});
	})(jQuery);
</script>');
   }

	public function Setup(){}

}