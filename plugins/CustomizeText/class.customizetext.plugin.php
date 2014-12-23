<?php if (!defined('APPLICATION')) exit();

/**
 * Allows forum adminis to modify text on their forum.
 * 
 * Requires infrastructure, and 2.1 locales (using Gdn_ConfigurationSource), as 
 * well as Garden.Locales.DeveloperMode
 * 
 * TODO:
 * Create a method to edit a translation. Drop it into the page (if not admin master). Ajax on submit.
 * Fill form with values on hover.
 * Auto-focus form when form values are filled.
 * Alternatively: could put the form into an inform with a custom target that always gets replaced.
 * 
 * Blacklist known "problem translations" that are in buttons or wreck page layout.
 */

$PluginInfo['CustomizeText'] = array(
   'Name' => 'Customize Text',
   'Description' => "Allows administrators to edit the text throughout their forum.",
   'Version' => '1.2',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'Author' => "Tim Gunter",
   'AuthorEmail' => 'tim@vanillaforums.com',
   'AuthorUrl' => 'http://vanillaforums.com',
	'SettingsUrl' => 'settings/customizetext'
);

class CustomizeTextPlugin extends Gdn_Plugin {
   
   public function __construct() {
      parent::__construct();
      if (!C('Garden.Locales.DeveloperMode', FALSE))
         SaveToConfig('Garden.Locales.DeveloperMode', TRUE);
   }

   /**
	 * Add the customize text menu option.
	 */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
		$Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Appearance', 'Customize Text', 'settings/customizetext', 'Garden.Settings.Manage');
	}

	/**
	 * Add the customize text page to the dashboard.
    * 
    * @param Gdn_Controller $Sender
	 */
   public function SettingsController_CustomizeText_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      
      $Sender->AddSideMenu('settings/customizetext');
		$Sender->AddJsFile('jquery.autogrow.js');
      
      $Sender->Title('Customize Text');

		$Directive = GetValue(0, $Sender->RequestArgs, '');
		$View = 'customizetext';
		if ($Directive == 'rebuild')
			$View = 'rebuild';
		elseif ($Directive == 'rebuildcomplete')
			$View = 'rebuildcomplete';
      
      $Method = 'none';
      
      if ($Sender->Form->IsPostback()) {
         $Method = 'search';
      
         if ($Sender->Form->GetValue('Save_All'))
            $Method = 'save';
      }
      
      $Matches = array();
      $Keywords = NULL;
      switch ($Method) {
         case 'none':
            break;
         
         case 'search':
         case 'save':
            
            $Keywords = strtolower($Sender->Form->GetValue('Keywords'));
            
            if ($Method == 'search') {
               $Sender->Form->ClearInputs();
               $Sender->Form->SetFormValue('Keywords', $Keywords);
            }
            
            $Definitions = Gdn::Locale()->GetDeveloperDefinitions();
            $CountDefinitions = sizeof($Definitions);
            $Sender->SetData('CountDefinitions', $CountDefinitions);
            
            $Changed = FALSE;
            foreach ($Definitions as $Key => $BaseDefinition) {
               $KeyHash = md5($Key);
               $ElementName = "def_{$KeyHash}";

               // Look for matches
               $k = strtolower($Key);
               $d = strtolower($BaseDefinition);
               
               // If this key doesn't match, skip it
               if ($Keywords != '*' && !(strlen($Keywords) > 0 && (strpos($k, $Keywords) !== FALSE || strpos($d, $Keywords) !== FALSE)))
                  continue;
               
               $Modified = FALSE;

               // Found a definition, look it up in the real locale first, to see if it has been overridden
               $CurrentDefinition = Gdn::Locale()->Translate($Key, FALSE);
               if ($CurrentDefinition !== FALSE && $CurrentDefinition != $BaseDefinition)
                  $Modified = TRUE;
               else
                  $CurrentDefinition = $BaseDefinition;

               $Matches[$Key] = array('def' => $CurrentDefinition, 'mod' => $Modified);
               if ($CurrentDefinition[0] == "\r\n")
                  $CurrentDefinition = "\r\n{$CurrentDefinition}";
               else if ($CurrentDefinition[0] == "\r")
                  $CurrentDefinition = "\r{$CurrentDefinition}";
               else if ($CurrentDefinition[0] == "\n")
                  $CurrentDefinition = "\n{$CurrentDefinition}";
               
               if ($Method == 'save') {
                  $SuppliedDefinition = $Sender->Form->GetValue($ElementName);

                  // Has this field been changed?
                  if ($SuppliedDefinition != FALSE && $SuppliedDefinition != $CurrentDefinition) {

                     // Changed from what it was, but is it a change from the *base* value?
                     $SaveDefinition = ($SuppliedDefinition != $BaseDefinition) ? $SuppliedDefinition : NULL;
                     if (!is_null($SaveDefinition)) {
                        $CurrentDefinition = $SaveDefinition;
                        $SaveDefinition = str_replace("\r\n", "\n", $SaveDefinition);
                     }
                     
                     Gdn::Locale()->SetTranslation($Key, $SaveDefinition, array(
                        'Save'         => TRUE,
                        'RemoveEmpty'  => TRUE
                     ));
                     $Matches[$Key] = array('def' => $SuppliedDefinition, 'mod' => !is_null($SaveDefinition));
                     $Changed = TRUE;
                  }
               }
               
               $Sender->Form->SetFormValue($ElementName, $CurrentDefinition);
            }

            if ($Changed) {
               $Sender->InformMessage("Locale changes have been saved!");
            }
            
            break;
      }
      
      $Sender->SetData('Matches', $Matches);
      $CountMatches = sizeof($Matches);
      $Sender->SetData('CountMatches', $CountMatches);
      
      $Sender->Render($View, '', 'plugins/CustomizeText');
   }
}
