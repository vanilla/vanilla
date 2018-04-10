<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Flagging
 */

class FlaggingPlugin extends Gdn_Plugin {

    /**
     * Add Flagging to Dashboard menu.
     */
    public function dashboardNavModule_init_handler($sender) {
        $flaggedItemsResult = Gdn::sql()->select('count(distinct(ForeignURL)) as NumFlaggedItems')
            ->from('Flag fl')
            ->get()->firstRow(DATASET_TYPE_ARRAY);

        $linkText = t('Flagged Content');
        if ($flaggedItemsResult['NumFlaggedItems']) {
            $linkText .= ' <span class="badge">'.$flaggedItemsResult['NumFlaggedItems'].'</span>';
        }
        /** @var DashboardNavModule $sender */
        $sender->addLinkToSectionIf('Garden.Moderation.Manage', 'Moderation', $linkText, 'plugin/flagging', 'moderation.flagging');
    }

    /**
     * Let users with permission choose to receive Flagging emails.
     */
    public function profileController_afterPreferencesDefined_handler($sender) {
        if (Gdn::session()->checkPermission('Plugins.Flagging.Notify')) {
            $sender->Preferences['Notifications']['Email.Flag'] = t('Notify me when a comment is flagged.');
            $sender->Preferences['Notifications']['Popup.Flag'] = t('Notify me when a comment is flagged.');
        }
    }

    /**
     * Save Email.Flag preference list in config for easier access.
     */
    public function userModel_beforeSaveSerialized_handler($sender) {
        if (Gdn::session()->checkPermission('Plugins.Flagging.Notify')) {
            if ($sender->EventArguments['Column'] == 'Preferences' && is_array($sender->EventArguments['Name'])) {
                // Shorten our arguments
                $userID = $sender->EventArguments['UserID'];
                $prefs = $sender->EventArguments['Name'];
                $flagPref = val('Email.Flag', $prefs, null);

                if ($flagPref !== null) {
                    // Add or remove user from config array
                    $notifyUsers = c('Plugins.Flagging.NotifyUsers', []);
                    $isNotified = array_search($userID, $notifyUsers); // beware '0' key
                    if ($isNotified !== false && !$flagPref) {
                        // Remove from NotifyUsers
                        unset($notifyUsers[$isNotified]);
                    } elseif ($isNotified === false && $flagPref) {
                        // Add to NotifyUsers
                        $notifyUsers[] = $userID;
                    }

                    // Save new list of users to notify
                    saveToConfig('Plugins.Flagging.NotifyUsers', array_values($notifyUsers));
                }
            }
        }
    }

    /**
     * Create virtual Flagging controller.
     */
    public function pluginController_flagging_create($sender) {
        $sender->permission('Garden.Moderation.Manage');
        $sender->title('Content Flagging');
        $sender->setHighlightRoute('plugin/flagging');
        $sender->Form = new Gdn_Form();
        $this->dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Get flagged content & show settings.
     *
     * Default method of virtual Flagging controller.
     */
    public function controller_index($sender) {
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'Plugins.Flagging.UseDiscussions',
            'Plugins.Flagging.CategoryID'
        ]);

        // Set the model on the form.
        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            // Apply the config settings to the form.
            $sender->Form->setData($configurationModel->Data);
        } else {
            $saved = $sender->Form->save();
            if ($saved) {
                $sender->informMessage(t("Your changes have been saved."));
            }
        }

        $flaggedItems = Gdn::sql()->select('*')
            ->from('Flag fl')
            ->orderBy('DateInserted', 'DESC')
            ->get();

        $sender->FlaggedItems = [];
        while ($flagged = $flaggedItems->nextRow(DATASET_TYPE_ARRAY)) {
            $uRL = $flagged['ForeignURL'];
            $index = $flagged['DateInserted'].'-'.$flagged['InsertUserID'];
            $flagged['EncodedURL'] = str_replace('=', '-', base64_encode($flagged['ForeignURL']));
            $sender->FlaggedItems[$uRL][$index] = $flagged;
        }
        unset($flaggedItems);

        Gdn_Theme::section('Moderation');
        $sender->render($sender->fetchViewLocation('flagging', '', 'plugins/Flagging'));
    }

    /**
     * Dismiss a flag, then view index.
     * @param Gdn_Controller $sender
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function controller_dismiss($sender) {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception('Requires POST', 405);
        }
        if (Gdn::session()->checkPermission('Garden.Moderation.Manage')) {
            $arguments = $sender->RequestArgs;
            if (sizeof($arguments) != 2) {
                return;
            }
            list($controller, $encodedURL) = $arguments;

            $uRL = base64_decode(str_replace('-', '=', $encodedURL));

            Gdn::sql()->delete('Flag', [
                'ForeignURL' => $uRL
            ]);

            $sender->informMessage(sprintf(t('%s dismissed.'), t('Flag')));
        }
        $sender->render('blank', 'utility', 'dashboard');
    }

    /**
     * Add 'Flag' link for discussions.
     */
    public function discussionController_discussionInfo_handler($sender, $args) {
        // Signed in users only. No guest reporting!
        if (Gdn::session()->UserID) {
            $this->addFlagButton($sender, $args, 'discussion');
        }
    }

    /**
     * Add 'Flag' link for comments.
     */
    public function discussionController_commentInfo_handler($sender, $args) {
        // Signed in users only. No guest reporting!
        if (Gdn::session()->UserID) {
            $this->addFlagButton($sender, $args);
        }
    }

    /**
     * Output Flag link.
     */
    protected function addFlagButton($sender, $args, $context = 'comment') {
        $elementID = ($context == 'comment') ? $args['Comment']->CommentID : $args['Discussion']->DiscussionID;

        if (!is_object($args['Author']) || !isset($args['Author']->UserID)) {
            $elementAuthorID = 0;
            $elementAuthor = 'Unknown';
        } else {
            $elementAuthorID = $args['Author']->UserID;
            $elementAuthor = $args['Author']->Name;
        }
        switch ($context) {
            case 'comment':
                $uRL = "/discussion/comment/{$elementID}/#Comment_{$elementID}";
                break;

            case 'discussion':
                $uRL = "/discussion/{$elementID}/".Gdn_Format::url($args['Discussion']->Name);
                break;

            default:
                return;
        }
        $encodedURL = str_replace('=', '-', base64_encode($uRL));
        $flagLink = anchor(t('Flag'), "discussion/flag/{$context}/{$elementID}/{$elementAuthorID}/".Gdn_Format::url($elementAuthor)."/{$encodedURL}", 'FlagContent Popup');
        echo wrap($flagLink, 'span', ['class' => 'MItem CommentFlag']);
    }

    /**
     * Handle flagging process in a discussion.
     */
    public function discussionController_flag_create($sender) {
        // Signed in users only.
        if (!($userID = Gdn::session()->UserID)) {
            return;
        }
        $userName = Gdn::session()->User->Name;

        $arguments = $sender->RequestArgs;
        if (sizeof($arguments) != 5) {
            return;
        }
        list($context, $elementID, $elementAuthorID, $elementAuthor, $encodedURL) = $arguments;
        $uRL = htmlspecialchars(base64_decode(str_replace('-', '=', $encodedURL)));

        $sender->setData('Plugin.Flagging.Data', [
            'Context' => $context,
            'ElementID' => $elementID,
            'ElementAuthorID' => $elementAuthorID,
            'ElementAuthor' => $elementAuthor,
            'URL' => $uRL,
            'UserID' => $userID,
            'UserName' => $userName
        ]);

        if ($sender->Form->authenticatedPostBack()) {
            $sQL = Gdn::sql();
            $comment = $sender->Form->getValue('Plugin.Flagging.Reason');
            $sender->setData('Plugin.Flagging.Reason', $comment);
            $createDiscussion = c('Plugins.Flagging.UseDiscussions');

            if ($createDiscussion) {
                // Category
                $categoryID = c('Plugins.Flagging.CategoryID');

                // New discussion name
                if ($context == 'comment') {
                    $result = $sQL
                        ->select('d.Name')
                        ->select('c.Body')
                        ->from('Comment c')
                        ->join('Discussion d', 'd.DiscussionID = c.DiscussionID', 'left')
                        ->where('c.CommentID', $elementID)
                        ->get()
                        ->firstRow();
                } elseif ($context == 'discussion') {
                    $discussionModel = new DiscussionModel();
                    $result = $discussionModel->getID($elementID);
                }

                $discussionName = val('Name', $result);
                $prefixedDiscussionName = t('FlagPrefix', 'FLAG: ').$discussionName;

                // Prep data for the template
                $sender->setData('Plugin.Flagging.Report', [
                    'DiscussionName' => $discussionName,
                    'FlaggedContent' => val('Body', $result)
                ]);

                // Assume no discussion exists
                $this->DiscussionID = null;

                // Get discussion ID if already flagged
                $flagResult = Gdn::sql()
                    ->select('DiscussionID')
                    ->from('Flag fl')
                    ->where('ForeignType', $context)
                    ->where('ForeignID', $elementID)
                    ->get()
                    ->firstRow();

                if ($flagResult) {
                    // New comment in existing discussion
                    $discussionID = $flagResult->DiscussionID;
                    $reportBody = $sender->fetchView('reportcomment', '', 'plugins/Flagging');
                    $sQL->insert('Comment', [
                        'DiscussionID' => $discussionID,
                        'InsertUserID' => $userID,
                        'Body' => $reportBody,
                        'Format' => 'Html',
                        'DateInserted' => date('Y-m-d H:i:s')
                    ]);
                    $commentModel = new CommentModel();
                    $commentModel->updateCommentCount($discussionID);
                } else {
                    // New discussion body
                    $reportBody = $sender->fetchView('report', '', 'plugins/Flagging');
                    $discussionID = $sQL->insert('Discussion', [
                        'InsertUserID' => $userID,
                        'UpdateUserID' => $userID,
                        'CategoryID' => $categoryID,
                        'Name' => $prefixedDiscussionName,
                        'Body' => $reportBody,
                        'Format' => 'Html',
                        'CountComments' => 1,
                        'DateInserted' => date('Y-m-d H:i:s'),
                        'DateUpdated' => date('Y-m-d H:i:s'),
                        'DateLastComment' => date('Y-m-d H:i:s')
                    ]);

                    // Update discussion count
                    $discussionModel = new DiscussionModel();
                    $discussionModel->updateDiscussionCount($categoryID);
                }
            }

            try {
                // Insert the flag
                $sQL->insert('Flag', [
                    'DiscussionID' => $discussionID,
                    'InsertUserID' => $userID,
                    'InsertName' => $userName,
                    'AuthorID' => $elementAuthorID,
                    'AuthorName' => $elementAuthor,
                    'ForeignURL' => $uRL,
                    'ForeignID' => $elementID,
                    'ForeignType' => $context,
                    'Comment' => $comment,
                    'DateInserted' => date('Y-m-d H:i:s')
                ]);
            } catch (Exception $e) {
                // Suppress exceptions from bubbling up.
            }

            // Notify users with permission who've chosen to be notified
            if (!$flagResult) { // Only send if this is first time it's being flagged.
                $sender->setData('Plugin.Flagging.DiscussionID', $discussionID);
                $subject = (isset($prefixedDiscussionName)) ? $prefixedDiscussionName : t('FlagDiscussion', 'A discussion was flagged');
                $emailBody = $sender->fetchView('reportemail', '', 'plugins/Flagging');
                $notifyUsers = c('Plugins.Flagging.NotifyUsers', []);

                // Send emails
                $userModel = new UserModel();
                foreach ($notifyUsers as $userID) {
                    $user = $userModel->getID($userID);
                    $email = new Gdn_Email();
                    $email->to($user->Email)
                        ->subject(sprintf(t('[%1$s] %2$s'), Gdn::config('Garden.Title'), $subject))
                        ->message($emailBody);

                    try {
                        $email->send();
                    } catch (Exception $e) {
                        if (debug()) {
                            throw $e;
                        }
                    }
                }
            }

            $sender->informMessage(t('FlagSent', "Your complaint has been registered."));
        }
        $sender->render($sender->fetchViewLocation('flag', '', 'plugins/Flagging'));
    }

    /**
     * Database changes needed for this plugin.
     */
    public function structure() {
        $structure = Gdn::structure();
        $structure
            ->table('Flag')
            ->column('DiscussionID', 'int(11)', true)
            ->column('InsertUserID', 'int(11)', false, 'key')
            ->column('InsertName', 'varchar(64)')
            ->column('AuthorID', 'int(11)')
            ->column('AuthorName', 'varchar(64)')
            ->column('ForeignURL', 'varchar(191)', false, 'key')
            ->column('ForeignID', 'int(11)')
            ->column('ForeignType', 'varchar(32)')
            ->column('Comment', 'text')
            ->column('DateInserted', 'datetime')
            ->set(false, false);

        // Turn off disabled Flagging plugin (deprecated)
        if (c('Plugins.Flagging.Enabled', null) === false) {
            removeFromConfig('EnabledPlugins.Flagging');
        }
    }

    public function setup() {
        $this->structure();
    }
}
