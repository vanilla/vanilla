<?php if (!defined('APPLICATION')) exit();

$PluginInfo['MeAction'] = array(
   'Description' => 'Allows IRC-style /me actions in the middle of comments as long as they appear at start of a new line.',
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'MobileFriendly' => TRUE,
   'Author' => "Lincoln Russell",
   'AuthorEmail' => 'lincoln@vanillaforums.com',
   'AuthorUrl' => 'http://lincolnwebs.com'
);

class MeActionPlugin extends Gdn_Plugin {
   public function DiscussionController_Render_Before($Sender) {
		$this->AddMeAction($Sender);
	}
	
	public function MessagesController_Render_Before($Sender) {
		$this->AddMeAction($Sender);
	}
	
	private function AddMeAction($Sender) {
		$Sender->AddJsFile('meaction.js', 'plugins/MeAction');
		$Sender->AddCssFile('meaction.css', 'plugins/MeAction');
	}

	/**
	 * Enable the formatter in Gdn_Format::Mentions.
	 */
   public function Setup() {
      SaveToConfig('Garden.Format.MeActions', TRUE);
   }
   
   /**
	 * Disable the formatter in Gdn_Format::Mentions.
	 */
   public function OnDisable() {
      SaveToConfig('Garden.Format.MeActions', FALSE);
   }
}
