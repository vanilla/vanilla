<?php if (!defined('APPLICATION')) exit();

$PluginInfo['Emotify'] = array(
	'Name' => 'Emotify :)',
	'Description' => 'Replaces emoticons in forum comments with images.',
	'Version' 	=>	 '1.0.1',
	'MobileFriendly' => TRUE,
	'Author' 	=>	 "Mark O'Sullivan",
	'AuthorEmail' => 'mark@vanillaforums.com',
	'AuthorUrl' =>	 'http://vanillaforums.org',
	'License' => 'GPL v2',
	'RequiredApplications' => array('Vanilla' => '>=2.0.14'),
);

/**
 * Note: Added jquery events required for proper display/hiding of emoticons
 * as write & preview buttons are clicked on forms in Vanilla 2.0.14. These
 * are necessary in order for this plugin to work properly.
 */

class EmotifyPlugin implements Gdn_IPlugin {
	
	public function PostController_Render_Before($Sender) {
		$this->_Emotify($Sender);
	}
	
	public function DiscussionController_Render_Before($Sender) {
		$this->_Emotify($Sender);
	}

   /**
    *
    * @param Gdn_Controller $Sender
    */
	private function _Emotify($Sender) {
		$Sender->AddJsFile('emotify.js', 'plugins/Emotify');   
      $Sender->AddCssFile('emotify.css', 'plugins/Emotify');
      $Sender->AddDefinition('FormatEmoticons', C('Plugins.Emotify.FormatEmoticons', TRUE));
	}
	
	public function Setup() { }
	
}