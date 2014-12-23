<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['ReactionsStub'] = array(
   'Description' => 'Provides an example Raction bar to allow local-install theming.',
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class ReactionsStubPlugin extends Gdn_Plugin {
   
   public function __construct() {}
   
   public function RootController_React_Create($Sender, $RecordType, $Reaction, $ID) {
      $Sender->Render('blank', 'utility', 'dashboard');
   }
   
   private function AddJs($Sender) {
      $Sender->AddJsFile('jquery-ui.js');
      $Sender->AddJsFile('reactions.js', 'plugins/ReactionsStub');
   }
   
   public function DiscussionController_Render_Before($Sender) {
      $Sender->AddCssFile('reactions.css', 'plugins/ReactionsStub');
      $this->AddJs($Sender);
   }
   
   public function Setup() {}
   
}

if (!function_exists('WriteReactions')) {
   
   function WriteReactions($Row) {
      $Reactions = <<<REACTIONS
<div class="Reactions"><span class="Flag ToggleFlyout"><a class="Hijack ReactButton ReactButton-Flag" href="" title="Flag" rel="nofollow"><span class="ReactSprite ReactFlag"></span> <span class="ReactLabel">Flag</span></a>
   <ul class="Flyout MenuItems Flags" style="display: none;"><li><a class="Hijack ReactButton ReactButton-Spam" href="/react/comment/spam?id=25011203" title="Spam" rel="nofollow"><span class="ReactSprite ReactSpam"></span> <span class="ReactLabel">Spam</span></a>
   </li><li><a class="Hijack ReactButton ReactButton-Abuse" href="/react/comment/abuse?id=25011203" title="Abuse" rel="nofollow"><span class="ReactSprite ReactAbuse"></span> <span class="ReactLabel">Abuse</span></a>
   </li></ul></span>&nbsp;<span class="React"><span class="ReactButtons"><a class="Hijack ReactButton ReactButton-Promote" href="/react/comment/promote?id=25011203" title="Promote" rel="nofollow"><span class="ReactSprite ReactPromote"></span> <span class="ReactLabel">Promote</span></a>
   <a class="Hijack ReactButton ReactButton-Insightful" href="/react/comment/insightful?id=25011203" title="Insightful" rel="nofollow"><span class="ReactSprite ReactInsightful"></span> <span class="ReactLabel">Insightful</span></a>
   <a class="Hijack ReactButton ReactButton-Awesome" href="/react/comment/awesome?id=25011203" title="Awesome" rel="nofollow"><span class="ReactSprite ReactAwesome"></span> <span class="ReactLabel">Awesome</span></a>
   <a class="Hijack ReactButton ReactButton-LOL" href="/react/comment/lol?id=25011203" title="LOL" rel="nofollow"><span class="ReactSprite ReactLOL"></span> <span class="ReactLabel">LOL</span></a>
   </span></span></div>
REACTIONS;
   
      echo $Reactions;
   }
   
}

