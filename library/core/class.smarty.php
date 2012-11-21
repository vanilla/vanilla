<?php if (!defined('APPLICATION')) exit();

/**
 * Smart abstraction layer
 * 
 * Vanilla implementation of Smarty templating engine.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Smarty {
   /// Constructor ///

   /// Properties ///

   /**
    * @var Smarty The smarty object used for the template.
    */
   protected $_Smarty = NULL;

   /// Methods ///

   
   public function Init($Path, $Controller) {
      $Smarty = $this->Smarty();

      // Get a friendly name for the controller.
      $ControllerName = get_class($Controller);
      if (StringEndsWith($ControllerName, 'Controller', TRUE)) {
         $ControllerName = substr($ControllerName, 0, -10);
      }

      // Get an ID for the body.
      $BodyIdentifier = strtolower($Controller->ApplicationFolder.'_'.$ControllerName.'_'.Gdn_Format::AlphaNumeric(strtolower($Controller->RequestMethod)));
      $Smarty->assign('BodyID', $BodyIdentifier);
      //$Smarty->assign('Config', Gdn::Config());

      // Assign some information about the user.
      $Session = Gdn::Session();
      if($Session->IsValid()) {
         $User = array(
            'Name' => $Session->User->Name,
            'Photo' => '',
            'CountNotifications' => (int)GetValue('CountNotifications', $Session->User, 0),
            'CountUnreadConversations' => (int)GetValue('CountUnreadConversations', $Session->User, 0),
            'SignedIn' => TRUE);
         
         $Photo = $Session->User->Photo;
         if ($Photo) {
            if (!preg_match('`^https?://`i', $Photo)) {
               $Photo = Gdn_Upload::Url(ChangeBasename($Photo, 'n%s'));
            }
         } else {
            if (function_exists('UserPhotoDefaultUrl'))
               $Photo = UserPhotoDefaultUrl($Session->User, 'ProfilePhoto');
            elseif ($ConfigPhoto = C('Garden.DefaultAvatar'))
               $Photo = Gdn_Upload::Url($ConfigPhoto);
            else
               $Photo = Asset('/applications/dashboard/design/images/defaulticon.png', TRUE);
         }
         $User['Photo'] = $Photo;
      } else {
         $User = FALSE; /*array(
            'Name' => '',
            'CountNotifications' => 0,
            'SignedIn' => FALSE);*/
      }
      $Smarty->assign('User', $User);

      // Make sure that any datasets use arrays instead of objects.
      foreach($Controller->Data as $Key => $Value) {
         if($Value instanceof Gdn_DataSet) {
            $Controller->Data[$Key] = $Value->ResultArray();
         } elseif($Value instanceof stdClass) {
            $Controller->Data[$Key] = (array)$Value;
         }
      }
      
      $BodyClass = GetValue('CssClass', $Controller->Data, '', TRUE);
      $Sections = Gdn_Theme::Section(NULL, 'get');
      if (is_array($Sections)) {
         foreach ($Sections as $Section) {
            $BodyClass .= ' Section-'.$Section;
         }
      }
     
      $Controller->Data['BodyClass'] = $BodyClass;

      $Smarty->assign('Assets', (array)$Controller->Assets);
      $Smarty->assign('Path', Gdn::Request()->Path());

      // Assigign the controller data last so the controllers override any default data.
      $Smarty->assign($Controller->Data);

      $Smarty->Controller = $Controller; // for smarty plugins
      $Smarty->security = TRUE;
      
      $Smarty->security_settings['IF_FUNCS'] = array_merge($Smarty->security_settings['IF_FUNCS'],
         array('Category', 'CheckPermission', 'InSection', 'InCategory', 'MultiCheckPermission', 'GetValue', 'SetValue', 'Url'));
      
      $Smarty->security_settings['MODIFIER_FUNCS'] = array_merge($Smarty->security_settings['MODIFIER_FUNCS'],
         array('sprintf'));
      
      $Smarty->secure_dir = array($Path);
   }   
   
   /**
    * Render the given view.
    *
    * @param string $Path The path to the view's file.
    * @param Controller $Controller The controller that is rendering the view.
    */
   public function Render($Path, $Controller) {
      $Smarty = $this->Smarty();
      $this->Init($Path, $Controller);
      $CompileID = $Smarty->compile_id;
      if (defined('CLIENT_NAME'))
         $CompileID = CLIENT_NAME;
      
      $Smarty->display($Path, NULL, $CompileID);
   }

   /**
    * @return Smarty The smarty object used for rendering.
    */
   public function Smarty() {
      if(is_null($this->_Smarty)) {
         $Smarty = Gdn::Factory('Smarty');

         $Smarty->cache_dir = PATH_CACHE . DS . 'Smarty' . DS . 'cache';
         $Smarty->compile_dir = PATH_CACHE . DS . 'Smarty' . DS . 'compile';
         $Smarty->plugins_dir[] = PATH_LIBRARY . DS . 'vendors' . DS . 'SmartyPlugins';
         
//         Gdn::PluginManager()->Trace = TRUE;
         Gdn::PluginManager()->CallEventHandlers($Smarty, 'Gdn_Smarty', 'Init');
         
         $this->_Smarty = $Smarty;
      }
      return $this->_Smarty;
   }
   
   /** 
    * See if the provided template causes any errors. 
    * @param type $Path Path of template file to test.
    * @return boolean TRUE if template loads successfully.
    */
   public function TestTemplate($Path) {
      $Smarty = $this->Smarty();
      $this->Init($Path, Gdn::Controller());
      $CompileID = $Smarty->compile_id;
      if (defined('CLIENT_NAME'))
         $CompileID = CLIENT_NAME;

      $Return = TRUE;
      try {
         $Result = $Smarty->fetch($Path, NULL, $CompileID);
         // echo Wrap($Result, 'textarea', array('style' => 'width: 900px; height: 400px;'));
         $Return = ($Result == '' || strpos($Result, '<title>Fatal Error</title>') > 0 || strpos($Result, '<h1>Something has gone wrong.</h1>') > 0) ? FALSE : TRUE;
      } catch(Exception $ex) {
         $Return = FALSE;
      }
      return $Return;
   }
}