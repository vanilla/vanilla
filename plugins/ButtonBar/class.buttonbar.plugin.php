<?php if (!defined('APPLICATION')) exit();

/**
 * ButtonBar Plugin
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 */

$PluginInfo['ButtonBar'] = array(
   'Name' => 'Button Bar',
   'Description' => 'Adds several simple buttons above comment boxes, allowing additional formatting.',
   'Version' => '1.6',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => array('Vanilla' => '2.1b'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class ButtonBarPlugin extends Gdn_Plugin {
   
   protected $Formats = array('Html', 'BBCode', 'Markdown');

   /**
    * Insert ButtonBar resource files on every page so they are available
    * to any new uses of BodyBox in plugins and applications.
    * 
    * @param Gdn_Controller $Sender 
    */
   public function Base_Render_Before($Sender) {
      $Formatter = C('Garden.InputFormatter','Html');
      $this->AttachButtonBarResources($Sender, $Formatter);
   }
   public function AssetModel_StyleCss_Handler($Sender) {
      $Sender->AddCssFile('buttonbar.css', 'plugins/ButtonBar');
   }
      
   /**
    * Insert buttonbar resources
    * 
    * This method is abstracted because it is invoked by multiple controllers.
    * 
    * @param Gdn_Controller $Sender 
    */
   protected function AttachButtonBarResources($Sender, $Formatter) {
      if (!in_array($Formatter, $this->Formats)) return;
      $Sender->AddJsFile('buttonbar.js', 'plugins/ButtonBar');
      $Sender->AddJsFile('jquery.hotkeys.js', 'plugins/ButtonBar');
      
      $Sender->AddDefinition('ButtonBarLinkUrl', T('ButtonBar.LinkUrlText', 'Enter your URL:'));
      $Sender->AddDefinition('ButtonBarImageUrl', T('ButtonBar.ImageUrlText', 'Enter image URL:'));
      $Sender->AddDefinition('ButtonBarBBCodeHelpText', T('ButtonBar.BBCodeHelp', 'You can use <b><a href="http://en.wikipedia.org/wiki/BBCode" target="_new">BBCode</a></b> in your post.'));
      $Sender->AddDefinition('ButtonBarHtmlHelpText', T('ButtonBar.HtmlHelp', 'You can use <b><a href="http://htmlguide.drgrog.com/cheatsheet.php" target="_new">Simple Html</a></b> in your post.'));
      $Sender->AddDefinition('ButtonBarMarkdownHelpText', T('ButtonBar.MarkdownHelp', 'You can use <b><a href="http://en.wikipedia.org/wiki/Markdown" target="_new">Markdown</a></b> in your post.'));
      
      $Sender->AddDefinition('InputFormat', $Formatter);
   }
   
   /**
    * Attach ButtonBar anywhere 'BodyBox' is used.
    * 
    * @param Gdn_Controller $Sender 
    */
   public function Gdn_Form_BeforeBodyBox_Handler($Sender) {
      $Wrap = false;
      if (Gdn::Controller() instanceof PostController)
         $Wrap = true;
      $this->AttachButtonBar($Sender, $Wrap);
   }
//   public function DiscussionController_BeforeBodyField_Handler($Sender) {
//      $this->AttachButtonBar($Sender);
//   }
//   public function PostController_BeforeBodyField_Handler($Sender) {
//      $this->AttachButtonBar($Sender);
//   }
   
   /**
    * Attach button bar in place
    * 
    * This method is abstracted because it is called from multiple places, due
    * to the way that the comment.php view is invoked both by the DiscussionController
    * and the PostController.
    * 
    * @param Gdn_Controller $Sender 
    */
   protected function AttachButtonBar($Sender, $Wrap = FALSE) {
      $Formatter = C('Garden.InputFormatter','Html');
      if (!in_array($Formatter, $this->Formats)) return;
      
      $View = Gdn::Controller()->FetchView('buttonbar','','plugins/ButtonBar');
      
      if ($Wrap)
         echo Wrap($View, 'div', array('class' => 'P'));
      else
         echo $View;
   }
   
}