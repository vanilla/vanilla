<?php
/**
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Flagging
 */

// Define the plugin:
$PluginInfo['Flagging'] = array(
    'Name' => 'Flagging',
    'Description' => 'Allows users to report content that violates forum rules.',
    'Version' => '1.1.1',
    'RequiredApplications' => false,
    'RequiredTheme' => false,
    'RequiredPlugins' => false,
    'SettingsUrl' => '/dashboard/plugin/flagging',
    'SettingsPermission' => 'Garden.Moderation.Manage',
    'HasLocale' => true,
    'MobileFriendly' => true,
    'RegisterPermissions' => array('Plugins.Flagging.Notify'),
    'Author' => "Tim Gunter",
    'AuthorEmail' => 'tim@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
);

class FlaggingPlugin extends Gdn_Plugin {
    /**
     * Add Flagging to Dashboard menu.
     */
    public function Base_GetAppSettingsMenuItems_Handler($Sender) {
        $NumFlaggedItems = Gdn::sql()->select('fl.ForeignID', 'DISTINCT', 'NumFlaggedItems')
            ->from('Flag fl')
            ->groupBy('ForeignURL')
            ->get()->numRows();

        $LinkText = T('Flagged Content');
        if ($NumFlaggedItems) {
            $LinkText .= ' <span class="Alert">'.$NumFlaggedItems.'</span>';
        }
        $Menu = $Sender->EventArguments['SideMenu'];
        $Menu->AddItem('Forum', T('Forum'));
        $Menu->addLink('Forum', $LinkText, 'plugin/flagging', 'Garden.Moderation.Manage');
    }

    /**
     * Let users with permission choose to receive Flagging emails.
     */
    public function ProfileController_AfterPreferencesDefined_Handler($Sender) {
        if (Gdn::session()->CheckPermission('Plugins.Flagging.Notify')) {
            $Sender->Preferences['Notifications']['Email.Flag'] = T('Notify me when a comment is flagged.');
            $Sender->Preferences['Notifications']['Popup.Flag'] = T('Notify me when a comment is flagged.');
        }
    }

    /**
     * Save Email.Flag preference list in config for easier access.
     */
    public function UserModel_BeforeSaveSerialized_Handler($Sender) {
        if (Gdn::session()->CheckPermission('Plugins.Flagging.Notify')) {
            if ($Sender->EventArguments['Column'] == 'Preferences' && is_array($Sender->EventArguments['Name'])) {
                // Shorten our arguments
                $UserID = $Sender->EventArguments['UserID'];
                $Prefs = $Sender->EventArguments['Name'];
                $FlagPref = val('Email.Flag', $Prefs, null);

                if ($FlagPref !== null) {
                    // Add or remove user from config array
                    $NotifyUsers = c('Plugins.Flagging.NotifyUsers', array());
                    $IsNotified = array_search($UserID, $NotifyUsers); // beware '0' key
                    if ($IsNotified !== false && !$FlagPref) {
                        // Remove from NotifyUsers
                        unset($NotifyUsers[$IsNotified]);
                    } elseif ($IsNotified === false && $FlagPref) {
                        // Add to NotifyUsers
                        $NotifyUsers[] = $UserID;
                    }

                    // Save new list of users to notify
                    saveToConfig('Plugins.Flagging.NotifyUsers', array_values($NotifyUsers));
                }
            }
        }
    }

    /**
     * Create virtual Flagging controller.
     */
    public function PluginController_Flagging_Create($Sender) {
        $Sender->Permission('Garden.Moderation.Manage');
        $Sender->Title('Content Flagging');
        $Sender->AddSideMenu('plugin/flagging');
        $Sender->Form = new Gdn_Form();
        $this->dispatch($Sender, $Sender->RequestArgs);
    }

    /**
     * Get flagged content & show settings.
     *
     * Default method of virtual Flagging controller.
     */
    public function Controller_Index($Sender) {
        $Sender->AddCssFile('admin.css');
        $Sender->AddCssFile($this->GetResource('design/flagging.css', false, false));

        $Validation = new Gdn_Validation();
        $ConfigurationModel = new Gdn_ConfigurationModel($Validation);
        $ConfigurationModel->setField(array(
            'Plugins.Flagging.UseDiscussions',
            'Plugins.Flagging.CategoryID'
        ));

        // Set the model on the form.
        $Sender->Form->setModel($ConfigurationModel);

        // If seeing the form for the first time...
        if ($Sender->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $Sender->Form->SetData($ConfigurationModel->Data);
        } else {
            $Saved = $Sender->Form->Save();
            if ($Saved) {
                $Sender->informMessage(T("Your changes have been saved."));
            }
        }

        $FlaggedItems = Gdn::sql()->select('*')
            ->from('Flag fl')
            ->orderBy('DateInserted', 'DESC')
            ->get();

        $Sender->FlaggedItems = array();
        while ($Flagged = $FlaggedItems->NextRow(DATASET_TYPE_ARRAY)) {
            $URL = $Flagged['ForeignURL'];
            $Index = $Flagged['DateInserted'].'-'.$Flagged['InsertUserID'];
            $Flagged['EncodedURL'] = str_replace('=', '-', base64_encode($Flagged['ForeignURL']));
            $Sender->FlaggedItems[$URL][$Index] = $Flagged;
        }
        unset($FlaggedItems);

        $Sender->Render($this->GetView('flagging.php'));
    }

    /**
     * Dismiss a flag, then view index.
     */
    public function Controller_Dismiss($Sender) {
        $Arguments = $Sender->RequestArgs;
        if (sizeof($Arguments) != 2) {
            return;
        }
        list($Controller, $EncodedURL) = $Arguments;

        $URL = base64_decode(str_replace('-', '=', $EncodedURL));

        Gdn::sql()->delete('Flag', array(
            'ForeignURL' => $URL
        ));

        $this->Controller_Index($Sender);
    }

    /**
     * Add Flagging styling to Discussion.
     */
    public function DiscussionController_BeforeCommentsRender_Handler($Sender) {
        $Sender->AddCssFile($this->GetResource('design/flagging.css', false, false));
    }

    /**
     * Add 'Flag' link for discussions.
     */
    public function DiscussionController_AfterDiscussionMeta_Handler($Sender, $Args) {
        // Signed in users only. No guest reporting!
        if (Gdn::session()->UserID) {
            $this->AddFlagButton($Sender, $Args, 'discussion');
        }
    }

    /**
     * Add 'Flag' link for comments.
     */
    public function DiscussionController_InsideCommentMeta_Handler($Sender, $Args) {
        // Signed in users only. No guest reporting!
        if (Gdn::session()->UserID) {
            $this->AddFlagButton($Sender, $Args);
        }
    }

    /**
     * Output Flag link.
     */
    protected function AddFlagButton($Sender, $Args, $Context = 'comment') {
        $ElementID = ($Context == 'comment') ? $Args['Comment']->CommentID : $Args['Discussion']->DiscussionID;

        if (!is_object($Args['Author']) || !isset($Args['Author']->UserID)) {
            $ElementAuthorID = 0;
            $ElementAuthor = 'Unknown';
        } else {
            $ElementAuthorID = $Args['Author']->UserID;
            $ElementAuthor = $Args['Author']->Name;
        }
        switch ($Context) {
            case 'comment':
                $URL = "/discussion/comment/{$ElementID}/#Comment_{$ElementID}";
                break;

            case 'discussion':
                $URL = "/discussion/{$ElementID}/".Gdn_Format::url($Args['Discussion']->Name);
                break;

            default:
                return;
        }
        $EncodedURL = str_replace('=', '-', base64_encode($URL));
        $FlagLink = anchor(T('Flag'), "discussion/flag/{$Context}/{$ElementID}/{$ElementAuthorID}/".Gdn_Format::url($ElementAuthor)."/{$EncodedURL}", 'FlagContent Popup');
        echo wrap($FlagLink, 'span', array('class' => 'MItem CommentFlag'));
    }

    /**
     * Handle flagging process in a discussion.
     */
    public function DiscussionController_Flag_Create($Sender) {
        // Signed in users only.
        if (!($UserID = Gdn::session()->UserID)) {
            return;
        }
        $UserName = Gdn::session()->User->Name;

        $Arguments = $Sender->RequestArgs;
        if (sizeof($Arguments) != 5) {
            return;
        }
        list($Context, $ElementID, $ElementAuthorID, $ElementAuthor, $EncodedURL) = $Arguments;
        $URL = htmlspecialchars(base64_decode(str_replace('-', '=', $EncodedURL)));

        $Sender->SetData('Plugin.Flagging.Data', array(
            'Context' => $Context,
            'ElementID' => $ElementID,
            'ElementAuthorID' => $ElementAuthorID,
            'ElementAuthor' => $ElementAuthor,
            'URL' => $URL,
            'UserID' => $UserID,
            'UserName' => $UserName
        ));

        if ($Sender->Form->authenticatedPostBack()) {
            $SQL = Gdn::sql();
            $Comment = $Sender->Form->GetValue('Plugin.Flagging.Reason');
            $Sender->SetData('Plugin.Flagging.Reason', $Comment);
            $CreateDiscussion = c('Plugins.Flagging.UseDiscussions');

            if ($CreateDiscussion) {
                // Category
                $CategoryID = c('Plugins.Flagging.CategoryID');

                // New discussion name
                if ($Context == 'comment') {
                    $Result = $SQL
                        ->select('d.Name')
                        ->select('c.Body')
                        ->from('Comment c')
                        ->join('Discussion d', 'd.DiscussionID = c.DiscussionID', 'left')
                        ->where('c.CommentID', $ElementID)
                        ->get()
                        ->firstRow();
                } elseif ($Context == 'discussion') {
                    $DiscussionModel = new DiscussionModel();
                    $Result = $DiscussionModel->getID($ElementID);
                }

                $DiscussionName = val('Name', $Result);
                $PrefixedDiscussionName = T('FlagPrefix', 'FLAG: ').$DiscussionName;

                // Prep data for the template
                $Sender->SetData('Plugin.Flagging.Report', array(
                    'DiscussionName' => $DiscussionName,
                    'FlaggedContent' => val('Body', $Result)
                ));

                // Assume no discussion exists
                $this->DiscussionID = null;

                // Get discussion ID if already flagged
                $FlagResult = Gdn::sql()
                    ->select('DiscussionID')
                    ->from('Flag fl')
                    ->where('ForeignType', $Context)
                    ->where('ForeignID', $ElementID)
                    ->get()
                    ->firstRow();

                if ($FlagResult) {
                    // New comment in existing discussion
                    $DiscussionID = $FlagResult->DiscussionID;
                    $ReportBody = $Sender->fetchView($this->GetView('reportcomment.php'));
                    $SQL->insert('Comment', array(
                        'DiscussionID' => $DiscussionID,
                        'InsertUserID' => $UserID,
                        'Body' => $ReportBody,
                        'Format' => 'Html',
                        'DateInserted' => date('Y-m-d H:i:s')
                    ));
                    $CommentModel = new CommentModel();
                    $CommentModel->UpdateCommentCount($DiscussionID);
                } else {
                    // New discussion body
                    $ReportBody = $Sender->fetchView($this->GetView('report.php'));
                    $DiscussionID = $SQL->insert('Discussion', array(
                        'InsertUserID' => $UserID,
                        'UpdateUserID' => $UserID,
                        'CategoryID' => $CategoryID,
                        'Name' => $PrefixedDiscussionName,
                        'Body' => $ReportBody,
                        'Format' => 'Html',
                        'CountComments' => 1,
                        'DateInserted' => date('Y-m-d H:i:s'),
                        'DateUpdated' => date('Y-m-d H:i:s'),
                        'DateLastComment' => date('Y-m-d H:i:s')
                    ));

                    // Update discussion count
                    $DiscussionModel = new DiscussionModel();
                    $DiscussionModel->UpdateDiscussionCount($CategoryID);
                }
            }

            try {
                // Insert the flag
                $SQL->insert('Flag', array(
                    'DiscussionID' => $DiscussionID,
                    'InsertUserID' => $UserID,
                    'InsertName' => $UserName,
                    'AuthorID' => $ElementAuthorID,
                    'AuthorName' => $ElementAuthor,
                    'ForeignURL' => $URL,
                    'ForeignID' => $ElementID,
                    'ForeignType' => $Context,
                    'Comment' => $Comment,
                    'DateInserted' => date('Y-m-d H:i:s')
                ));
            } catch (Exception $e) {
            }

            // Notify users with permission who've chosen to be notified
            if (!$FlagResult) { // Only send if this is first time it's being flagged.
                $Sender->SetData('Plugin.Flagging.DiscussionID', $DiscussionID);
                $Subject = (isset($PrefixedDiscussionName)) ? $PrefixedDiscussionName : T('FlagDiscussion', 'A discussion was flagged');
                $EmailBody = $Sender->fetchView($this->GetView('reportemail.php'));
                $NotifyUsers = c('Plugins.Flagging.NotifyUsers', array());

                // Send emails
                $UserModel = new UserModel();
                foreach ($NotifyUsers as $UserID) {
                    $User = $UserModel->getID($UserID);
                    $Email = new Gdn_Email();
                    $Email->to($User->Email)
                        ->subject(sprintf(T('[%1$s] %2$s'), Gdn::config('Garden.Title'), $Subject))
                        ->message($EmailBody)
                        ->send();
                }
            }

            $Sender->informMessage(T('FlagSent', "Your complaint has been registered."));
        }
        $Sender->Render($this->GetView('flag.php'));
    }

    /**
     * Database changes needed for this plugin.
     */
    public function Structure() {
        $Structure = Gdn::structure();
        $Structure
            ->table('Flag')
            ->column('DiscussionID', 'int(11)', true)
            ->column('InsertUserID', 'int(11)', false, 'key')
            ->column('InsertName', 'varchar(64)')
            ->column('AuthorID', 'int(11)')
            ->column('AuthorName', 'varchar(64)')
            ->column('ForeignURL', 'varchar(255)', false, 'key')
            ->column('ForeignID', 'int(11)')
            ->column('ForeignType', 'varchar(32)')
            ->column('Comment', 'text')
            ->column('DateInserted', 'datetime')
            ->set(false, false);

        // Turn off disabled Flagging plugin (deprecated)
        if (C('Plugins.Flagging.Enabled', null) === false) {
            RemoveFromConfig('EnabledPlugins.Flagging');
        }
    }

    public function Setup() {
        $this->Structure();
    }
}
