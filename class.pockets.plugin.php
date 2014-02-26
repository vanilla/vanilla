<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['Pockets'] = array(
   'Name' => 'Pockets',
   'Description' => 'Administrators may add raw HTML to various places on the site. This plugin is very powerful, but can easily break your site if you make a mistake.',
   'Version' => '1.1.2',
   'Author' => "Todd Burry",
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.org/profile/todd',
   'RequiredApplications' => array('Vanilla' => '2.1a20'),
   'RegisterPermissions' => array('Plugins.Pockets.Manage'),
   'SettingsUrl' => '/settings/pockets',
   'SettingsPermission' => 'Plugins.Pockets.Manage',
   'MobileFriendly' => TRUE,
   'HasLocale' => TRUE
);

class PocketsPlugin extends Gdn_Plugin {
   /** An array of counters for the various locations.
    *
    * @var array
    */
   protected $_Counters = array();

   public $Locations = array(
      'Content' => array('Name' => 'Content'),
      'Panel' => array('Name' => 'Panel'),
      'BetweenDiscussions' => array('Name' => 'Between Discussions', 'Wrap' => array('<li>', '</li>')),
      'BetweenComments' => array('Name' => 'Between Comments', 'Wrap' => array('<li>', '</li>')),
      'Head' => array('Name' => 'Head'),
      'Foot' => array('Name' => 'Foot'));

   /** An array of all of the pockets indexed by location.
    *
    * @var array
    */
   protected $_Pockets = array();
   protected $_PocketNames = array();

   protected $StateLoaded = FALSE;


   /** Whether or not to display test items for all pockets. */
   public $TestMode = NULL;

   public function Base_Render_Before($Sender) {
      if ($this->TestMode === NULL)
         $this->TestMode = C('Plugins.Pockets.ShowLocations');

      if ($this->TestMode && Gdn::Session()->CheckPermission('Plugins.Pockets.Manage')) {
         // Add the css for the test pockets to the page.
         $Sender->AddCSSFile('pockets.css', 'plugins/Pockets');
      }
   }

   /**
    * Adds "Media" menu option to the Forum menu on the dashboard.
    */
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = $Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Appearance', T('Appearance'));
      $Menu->AddLink('Appearance', T('Pockets'), 'settings/pockets', 'Plugins.Pockets.Manage');
   }

   public function Base_BeforeRenderAsset_Handler($Sender) {
      $AssetName = GetValueR('EventArguments.AssetName', $Sender);
      $this->ProcessPockets($Sender, $AssetName, Pocket::REPEAT_BEFORE);
   }

   public function Base_AfterRenderAsset_Handler($Sender) {
      $AssetName = GetValueR('EventArguments.AssetName', $Sender);
      $this->ProcessPockets($Sender, $AssetName, Pocket::REPEAT_AFTER);
   }

   public function Base_BetweenRenderAsset_Handler($Sender) {
      $AssetName = GetValueR('EventArguments.AssetName', $Sender);
      $this->ProcessPockets($Sender, $AssetName);

      //echo $this->TestHtml("RenderAsset: $AssetName");
   }

   public function Base_BetweenDiscussion_Handler($Sender) {
      $this->ProcessPockets($Sender, 'BetweenDiscussions');

      //echo '<li>'.$this->TestHtml("BetweenDiscussion").'</li>';
   }

   public function Base_BeforeCommentDisplay_Handler($Sender) {
      // We don't want pockets to display before the first comment because they are only between comments.
      $Processed = isset($this->_Counters['BeforeCommentDisplay']);
      if (!$Processed) {
         $this->_Counters['BeforeCommentDisplay'] = TRUE;
         return;
      }

      $this->ProcessPockets($Sender, 'BetweenComments');
      //echo '<li>'.$this->TestHtml("BetweenComments").'</li>';
   }

   /** Main list for a pocket management.
    *
    * @param Gdn_Controller $Sender.
    */
   public function SettingsController_Pockets_Create($Sender, $Args = array()) {
      $Sender->Permission('Plugins.Pockets.Manage');
      $Sender->AddSideMenu('settings/pockets');
      $Sender->AddJsFile('pockets.js', 'plugins/Pockets');

      $Page = GetValue(0, $Args);
      switch(strtolower($Page)) {
         case 'add':
            return $this->_Add($Sender);
            break;
         case 'edit':
            return $this->_Edit($Sender, GetValue(1, $Args));
            break;
         case 'delete':
            return $this->_Delete($Sender, GetValue(1, $Args));
            break;
         default:
            return $this->_Index($Sender, $Args);
      }
   }

   protected function _Index($Sender, $Args) {
      $Sender->SetData('Title', T('Pockets'));

      // Grab the pockets from the DB.
      $PocketData = Gdn::SQL()->Get('Pocket', 'Location, `Sort`')->ResultArray();
      // Add notes to the pockets data.
      foreach ($PocketData as $Index => &$PocketRow) {
         // Add notes for the display.
         $Notes = array();

         if ($PocketRow['Repeat'] && $PocketRow['Repeat'] != Pocket::REPEAT_ONCE)
            $PocketRow['Location'] .= " ({$PocketRow['Repeat']})";

         if ($PocketRow['Disabled'] == Pocket::DISABLED)
            $Notes[] = T('Disabled');
         elseif ($PocketRow['Disabled'] == Pocket::TESTING)
            $Notes[] = T('Testing');

         $PocketRow['Notes'] = implode(', ', $Notes);
      }

      $Sender->SetData('PocketData', $PocketData);

      $Form = new Gdn_Form();

      // Save global options.
      switch (GetValue(0, $Args)) {
         case 'showlocations':
            SaveToConfig('Plugins.Pockets.ShowLocations', TRUE);
            break;
         case 'hidelocations':
            SaveToConfig('Plugins.Pockets.ShowLocations', FALSE, array('RemoveEmpty' => TRUE));
            break;
      }

//      if ($Form->AuthenticatedPostBack()) {
//         $ShowLocations = $Form->GetFormValue('ShowLocations');
//         SaveToConfig('Plugins.Pockets.ShowLocations', $ShowLocations);
//         $Sender->StatusMessage = T('Your changes have been saved.');
//      } else {
//         $Form->SetFormValue('ShowLocations', C('Plugins.Pockets.ShowLocations', FALSE));
//      }

      $Sender->Form = $Form;
      $Sender->Render('Index', '', 'plugins/Pockets');
   }

   protected function _Add($Sender) {
      $Sender->SetData('Title', sprintf(T('Add %s'), T('Pocket')));

      return $this->_AddEdit($Sender);
   }

   protected function _AddEdit($Sender, $PocketID = FALSE) {
      $Form = new Gdn_Form();
      $PocketModel = new Gdn_Model('Pocket');
      $Form->SetModel($PocketModel);
      $Sender->ConditionModule = new ConditionModule($Sender);
      $Sender->Form = $Form;

      if ($Form->AuthenticatedPostBack()) {
         // Save the pocket.
         if ($PocketID !== FALSE)
            $Form->SetFormValue('PocketID', $PocketID);

         // Convert the form data into a format digestable by the database.
         $Repeat = $Form->GetFormValue('RepeatType');
         switch ($Repeat) {
            case Pocket::REPEAT_EVERY:
               $PocketModel->Validation->ApplyRule('EveryFrequency', 'Integer');
               $PocketModel->Validation->ApplyRule('EveryBegin', 'Integer');
               $Frequency = $Form->GetFormValue('EveryFrequency', 1);
               if (!$Frequency || !ValidateInteger($Frequency) || $Frequency < 1)
                  $Frequency = 1;
               $Repeat .= ' '.$Frequency;
               if ($Form->GetFormValue('EveryBegin', 1) > 1)
                  $Repeat .= ','.$Form->GetFormValue('EveryBegin');
               break;
            case Pocket::REPEAT_INDEX:
               $PocketModel->Validation->AddRule('IntegerArray', 'function:ValidateIntegerArray');
               $PocketModel->Validation->ApplyRule('Indexes', 'IntegerArray');
               $Indexes = explode(',', $Form->GetFormValue('Indexes', ''));
               $Indexes = array_map('trim', $Indexes);
               $Repeat .= ' '.implode(',', $Indexes);
               break;
            default:
               break;
         }
         $Form->SetFormValue('Repeat', $Repeat);
         $Form->SetFormValue('Sort', 0);
         $Form->SetFormValue('Format', 'Raw');
         $Condition = Gdn_Condition::ToString($Sender->ConditionModule->Conditions(TRUE));
         $Form->SetFormValue('Condition', $Condition);

         $Saved = $Form->Save();
         if ($Saved) {
            $Sender->StatusMessage = T('Your changes have been saved.');
            $Sender->RedirectUrl = Url('settings/pockets');
         }
      } else {
         if ($PocketID !== FALSE) {
            // Load the pocket.
            $Pocket = $PocketModel->GetWhere(array('PocketID' => $PocketID))->FirstRow(DATASET_TYPE_ARRAY);
            if (!$Pocket)
               return Gdn::Dispatcher()->Dispatch('Default404');

            // Convert some of the pocket data into a format digestable by the form.
            list($RepeatType, $RepeatFrequency) = Pocket::ParseRepeat($Pocket['Repeat']);
            $Pocket['RepeatType'] = $RepeatType;
            $Pocket['EveryFrequency'] = GetValue(0, $RepeatFrequency, 1);
            $Pocket['EveryBegin'] = GetValue(1, $RepeatFrequency, 1);
            $Pocket['Indexes'] = implode(',', $RepeatFrequency);
            $Sender->ConditionModule->Conditions(Gdn_Condition::FromString($Pocket['Condition']));
            $Form->SetData($Pocket);
         } else {
            // Default the repeat.
            $Form->SetFormValue('RepeatType', Pocket::REPEAT_ONCE);
         }
      }

      $Sender->Form = $Form;

      $Sender->SetData('Locations', $this->Locations);
      $Sender->SetData('LocationsArray', $this->GetLocationsArray());
      $Sender->SetData('Pages', array('' => '('.T('All').')', 'activity' => 'activity', 'comments' => 'comments', 'dashboard' => 'dashboard', 'discussions' => 'discussions', 'inbox' => 'inbox', 'profile' => 'profile'));

      return $Sender->Render('AddEdit', '', 'plugins/Pockets');
   }

   protected function _Edit($Sender, $PocketID) {
      $Sender->SetData('Title', sprintf(T('Edit %s'), T('Pocket')));

      return $this->_AddEdit($Sender, $PocketID);
   }

   protected function _Delete($Sender, $PocketID) {
      $Sender->SetData('Title', sprintf(T('Delete %s'), T('Pocket')));

      $Form = new Gdn_Form();

      if ($Form->AuthenticatedPostBack()) {
         Gdn::SQL()->Delete('Pocket', array('PocketID' => $PocketID));
         $Sender->StatusMessage = sprintf(T('The %s has been deleted.'), strtolower(T('Pocket')));
         $Sender->RedirectUrl = Url('settings/pockets');
      }

      $Sender->Form = $Form;
      $Sender->Render('Delete', '', 'plugins/Pockets');
      return TRUE;
   }

   /** Add a pocket to the plugin's array of pockets to process.
    *
    * @param Pocket $Pocket
    */
   public function AddPocket($Pocket) {
      if (!isset($this->_Pockets[$Pocket->Location]))
         $this->_Pockets[$Pocket->Location] = array();

      $this->_Pockets[$Pocket->Location][] = $Pocket;
      $this->_PocketNames[$Pocket->Name][] = $Pocket;
   }

   public function GetLocationsArray() {
      $Result = array();
      foreach ($this->Locations as $Key => $Value) {
         $Result[$Key] = GetValue('Name', $Value, $Key);
      }
      return $Result;
   }

   public function GetPockets($Name) {
      $this->_LoadState();
      return GetValue($Name, $this->_PocketNames, array());
   }

   protected function _LoadState($Force = FALSE) {
      if (!$Force && $this->StateLoaded)
         return;

      $Pockets = Gdn::SQL()->Get('Pocket', 'Location, Sort, Name')->ResultArray();
      foreach ($Pockets as $Row) {
         $Pocket = new Pocket();
         $Pocket->Load($Row);
         $this->AddPocket($Pocket);
      }

      $this->StateLoaded = TRUE;
   }

   public function ProcessPockets($Sender, $Location, $CountHint = NULL) {
      if (Gdn::Controller()->Data('_NoMessages'))
         return;

      // Since plugins can't currently maintain their state we have to stash it in the Gdn object.
      $this->_LoadState();

      // Build up the data for filtering.
      $Data = array();
      $Data['Request'] = Gdn::Request();


      // Increment the counter.
      if ($CountHint != NULL) {
         $Count = $CountHint;
      } elseif (array_key_exists($Location, $this->_Counters)) {
         $Count = $this->_Counters[$Location] + 1;
         $this->_Counters[$Location] = $Count;
      } else {
         $Count = $this->_Counters[$Location] = 1;
      }

      $Data['Count'] = $Count;
      $Data['PageName'] = Pocket::PageName($Sender);

      $LocationOptions = GetValue($Location, $this->Locations, array());

      if ($this->TestMode && array_key_exists($Location, $this->Locations) && Gdn::Session()->CheckPermission('Plugins.Pockets.Manage')) {
         $LocationName = GetValue("Name", $this->Locations, $Location);
         echo
            GetValueR('Wrap.0', $LocationOptions, ''),
            "<div class=\"TestPocket\"><h3>$LocationName ($Count)</h3></div>",
            GetValueR('Wrap.1', $LocationOptions, '');

         if ($Location == 'Foot' && strcasecmp($Count, 'after') == 0) {
            echo $this->TestData($Sender);
         }
      }

      // Process all of the pockets.
      if (array_key_exists($Location, $this->_Pockets)) {
         foreach ($this->_Pockets[$Location] as $Pocket) {
            /** @var Pocket $Pocket */

            if ($Pocket->CanRender($Data)) {
               $Wrap = GetValue('Wrap', $LocationOptions, array());

               echo GetValue(0, $Wrap, '');
               $Pocket->Render($Data);
               echo GetValue(1, $Wrap, '');
            }
         }
      }

      $this->_SaveState();
   }

   public static function PocketString($Name, $Data = NULL) {
      $Inst = Gdn::PluginManager()->GetPluginInstance('PocketsPlugin', Gdn_PluginManager::ACCESS_CLASSNAME);
      $Pockets = $Inst->GetPockets($Name);

      if (GetValue('random', $Data)) {
         $Pockets = array(array_rand($Pockets));
      }

      $Result = '';
      foreach ($Pockets as $Pocket) {
         $Result .= $Pocket->ToString();
      }

      if (is_array($Data)) {
         $Data = array_change_key_case($Data);

         self::PocketStringCb($Data, TRUE);
         $Result = preg_replace_callback('`{{(\w+)}}`', array('PocketsPlugin', 'PocketStringCb'), $Result);
      }

      return $Result;
   }

   public static function PocketStringCb($Match = NULL, $SetArgs = FALSE) {
      static $Data;
      if ($SetArgs) {
         $Data = $Match;
      }

      $Key = strtolower($Match[1]);
      if (isset($Data[$Key]))
         return $Data[$Key];
      else
         return '';
   }

   protected function _SaveState() {
//      $State = array(
//         'Counters' => $this->_Counters,
//         'Pockets' => $this->_Pockets
//      );
//      $PM = Gdn::PluginManager();
//      $PM->_PocketsPluginState = $State;
   }

   public function Setup() {
      $this->Structure();
   }

   public function Structure($Explicit = FALSE, $Drop = FALSE) {
   	  // It seems plugins need to be disabled and enabled for this to happen.
   	  // Might want to warn users that upgrade.
      $St = Gdn::Structure();
      $St->Table('Pocket')
         ->PrimaryKey('PocketID')
         ->Column('Name', 'varchar(255)')
         ->Column('Page', 'varchar(50)', NULL)
         ->Column('Location', 'varchar(50)')
         ->Column('Sort', 'smallint')
         ->Column('Repeat', 'varchar(25)')
         ->Column('Body', 'text')
         ->Column('Format', 'varchar(20)')
         ->Column('Condition', 'varchar(500)', NULL)
         ->Column('Disabled', 'smallint', '0') // set to a constant in class Pocket
         ->Column('Attributes', 'text', NULL)
         ->Column('MobileOnly', 'tinyint', '0')
         ->Column('MobileNever', 'tinyint', '0')
         ->Column('EmbeddedNever', 'tinyint', '0')
         ->Column('ShowInDashboard', 'tinyint', '0')
         ->Set($Explicit, $Drop);
   }

   public function TestData($Sender) {
      return;
      echo "<div class=\"TestPocket\"><h3>Test Data</h3>";

      echo '<ul class="Variables">';

      echo self::_Var('path', Gdn::Request()->Path());

      echo self::_Var('page', Pocket::PageName($Sender));


//      $RequestArgs = Gdn::Request()->GetRequestArguments();
//      foreach ($RequestArgs as $Type => $Args) {
//         if ($Type == Gdn_Request::INPUT_COOKIES)
//            continue;
//         foreach ($Args as $Name => $Value) {
//            echo self::_Var("$Type.$Name", $Value);
//         }
//      }

      echo '</ul>';

      echo "</div>";
   }

   protected static function _Var($Name, $Value) {
      return '<li class="Var"><b>'.htmlspecialchars($Name).'</b><span>'.htmlspecialchars($Value).'</span></li>';
   }
}

if (!function_exists('ValidateIntegerArray')) {
   function ValidateIntegerArray($Value, $Field) {
      $Values = explode(',', $Value);
      foreach ($Values as $Val) {
         if ($Val && !ValidateInteger(trim($Val)))
            return FALSE;
      }

      return TRUE;
   }
}