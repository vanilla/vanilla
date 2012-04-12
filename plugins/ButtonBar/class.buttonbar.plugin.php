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
   'Version' => '1.2.4',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class ButtonBarPlugin extends Gdn_Plugin {

   /**
    * Hook the page-level event and insert buttonbar resource files
    * 
    * @param Gdn_Controller $Sender 
    */
   public function DiscussionController_Render_Before($Sender) {
      $Formatter = C('Garden.InputFormatter','Html');
      $this->AttachButtonBarResources($Sender, $Formatter);
   }
   public function PostController_Render_Before($Sender) {
      $Formatter = C('Garden.InputFormatter','Html');
      $this->AttachButtonBarResources($Sender, $Formatter);
   }
      
   /**
    * Insert buttonbar resources
    * 
    * This method is abstracted because it is invoked by two different 
    * controllers.
    * 
    * @param Gdn_Controller $Sender 
    */
   protected function AttachButtonBarResources($Sender, $Formatter) {
      $Sender->AddCssFile('buttonbar.css', 'plugins/ButtonBar');
      $Sender->AddJsFile('buttonbar.js', 'plugins/ButtonBar');
      $Sender->AddJsFile('jquery.hotkeys.js', 'plugins/ButtonBar');
      
      $Sender->AddDefinition('InputFormat', $Formatter);
   }
   
   /**
    * Hook 'BeforeBodyField' event
    * 
    * This event fires just before the comment textbox is drawn.
    * We bind to two different events because the box is drawn by two different
    * controllers.
    * 
    * @param Gdn_Controller $Sender 
    */
   public function DiscussionController_BeforeBodyField_Handler($Sender) {
      $this->AttachButtonBar($Sender);
   }
   public function PostController_BeforeBodyField_Handler($Sender) {
      $this->AttachButtonBar($Sender);
   }
   
   /**
    * Hook 'BeforeBodyInput' event
    * 
    * This event fires just before the new discussion textbox is drawn.
    * 
    * @param Gdn_Controller $Sender 
    */
   public function PostController_BeforeBodyInput_Handler($Sender) {
      $this->AttachButtonBar($Sender, TRUE);
   }
   
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
      $View = $Sender->FetchView('buttonbar','','plugins/ButtonBar');
      
      if ($Wrap)
         echo Wrap($View, 'div', array('class' => 'P'));
      else
         echo $View;
   }
   
}