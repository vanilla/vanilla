<?php
/**
 * Quotes Plugin.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Quotes
 */

/**
 * This plugin allows users to quote comments for reference in their own comments
 * within a discussion.
 *
 * Changes:
 *  1.0     Initial release
 *  1.6.1   Overhaul
 *  1.6.4   Moved button to reactions area & changed js accordingly.
 *  1.6.8   Textarea target will now automatically resize to fit text body.
 *  1.6.9   Security fix.
 *  1.7     Eliminate livequery and js refactor.
 *  1.9     Use contentLoad to format quotes.
 */
class QuotesPlugin extends Gdn_Plugin {

    /** @var bool */
    public $HandleRenderQuotes = true;

    /**
     * Set some properties we always need.
     */
    public function __construct() {
        parent::__construct();

        if (function_exists('ValidateUsernameRegex')) {
            $this->ValidateUsernameRegex = validateUsernameRegex();
        } else {
            $this->ValidateUsernameRegex = "[\d\w_]{3,20}";
        }

        // Whether to handle drawing quotes or leave it up to some other plugin
        $this->HandleRenderQuotes = c('Plugins.Quotes.RenderQuotes', true);
    }

    /**
     * Add "Quote Settings" to edit profile menu.
     *
     * @param profileController $sender
     */
    public function profileController_afterAddSideMenu_handler($sender) {
        if (!Gdn::session()->checkPermission('Garden.SignIn.Allow')) {
            return;
        }

        $sideMenu = $sender->EventArguments['SideMenu'];
        $viewingUserID = Gdn::session()->UserID;

        if ($sender->User->UserID == $viewingUserID) {
            $sideMenu->addLink('Options', sprite('SpQuote').' '.t('Quote Settings'), '/profile/quotes', false, ['class' => 'Popup QuoteSettingsLink']);
        } else {
            $sideMenu->addLink('Options', sprite('SpQuote').' '.t('Quote Settings'), userUrl($sender->User, '', 'quotes'), 'Garden.Users.Edit', ['class' => 'Popup QuoteSettingsLink']);
        }
    }

    /**
     * Endpoint for managing personal quote settings from edit profile menu.
     *
     * @param profileController $sender
     */
    public function profileController_quotes_create($sender) {
        $sender->permission('Garden.SignIn.Allow');
        $sender->title(t("Quote Settings"));

        $args = $sender->RequestArgs;
        if (sizeof($args) < 2) {
            $args = array_merge($args, [0, 0]);
        } elseif (sizeof($args) > 2) {
            $args = array_slice($args, 0, 2);
        }

        list($userReference, $username) = $args;

        $sender->getUserInfo($userReference, $username);
        $userPrefs = dbdecode($sender->User->Preferences);
        if (!is_array($userPrefs)) {
            $userPrefs = [];
        }

        $userID = Gdn::session()->UserID;
        $viewingUserID = $userID;

        if ($sender->User->UserID != $viewingUserID) {
            $sender->permission('Garden.Users.Edit');
            $userID = $sender->User->UserID;
            $userName = $sender->User->Name;
            $userName = htmlspecialchars($userName);
            $sender->setData('ForceEditing', $userName);
        } else {
            $sender->setData('ForceEditing', false);
        }

        $quoteFolding = val('Quotes.Folding', $userPrefs, '1');
        $sender->Form->setValue('QuoteFolding', $quoteFolding);

        $sender->setData('QuoteFoldingOptions', [
            'None' => t("Don't fold quotes"),
            '1' => plural(1, '%s level deep', '%s levels deep'),
            '2' => plural(2, '%s level deep', '%s levels deep'),
            '3' => plural(3, '%s level deep', '%s levels deep'),
            '4' => plural(4, '%s level deep', '%s levels deep'),
            '5' => plural(5, '%s level deep', '%s levels deep')
        ]);

        // Form submission handling.
        if ($sender->Form->authenticatedPostBack()) {
            $newFoldingLevel = $sender->Form->getValue('QuoteFolding', '1');
            if ($newFoldingLevel != $quoteFolding) {
                Gdn::userModel()->savePreference($userID, 'Quotes.Folding', $newFoldingLevel);
                $sender->informMessage(t("Your changes have been saved."));
            }
        }

        $sender->render('quotes', '', 'plugins/Quotes');
    }

    /**
     * Set user's quote folding preference in the page for Javascript access.
     *
     * @param discussionController $sender
     */
    public function discussionController_beforeDiscussionRender_handler($sender) {
        if (!Gdn::session()->isValid()) {
            return;
        }

        $userPrefs = dbdecode(Gdn::session()->User->Preferences);
        if (!is_array($userPrefs)) {
            $userPrefs = [];
        }

        $quoteFolding = val('Quotes.Folding', $userPrefs, '1');
        $sender->addDefinition('QuotesFolding', $quoteFolding);
        $sender->addDefinition('hide previous quotes', t('hide previous quotes', '&laquo; hide previous quotes'));
        $sender->addDefinition('show previous quotes', t('show previous quotes', '&raquo; show previous quotes'));
    }

    /**
     * Re-dispatch for requests to our embedded controller.
     *
     * This is the old and busted way of doing controllers in addons. Use a native controller instead.
     *
     * @param $sender
     * @throws Exception
     */
    public function pluginController_quotes_create($sender) {
        $this->dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Add getquote endpoint to our embedded controller.
     *
     * Old and busted method.
     *
     * @param $sender
     */
    public function controller_getquote($sender) {
        $this->discussionController_getQuote_create($sender);
    }

    /**
     * Retrieve text of a quote.
     *
     * @param discussionController $sender
     * @param $selector
     * @param bool $format
     */
    public function discussionController_getQuote_create($sender, $selector, $format = false) {
        $sender->permission('Garden.SignIn.Allow');

        $sender->deliveryMethod(DELIVERY_METHOD_JSON);
        $sender->deliveryType(DELIVERY_TYPE_VIEW);

        if (!$format) {
            $format = c('Garden.InputFormatter');
        }

        $quoteData = [
            'status' => 'failed'
        ];

        $quoteData['selector'] = $selector;
        list($type, $id) = explode('_', $selector);
        $this->formatQuote($type, $id, $quoteData, $format);

        $sender->setJson('Quote', $quoteData);
        $sender->render('GetQuote', '', 'plugins/Quotes');
    }

    /**
     * Add Javascript to discussion pages.
     *
     * @param discussionController $sender
     */
    public function discussionController_render_before($sender) {
        $sender->addJsFile('quotes.js', 'plugins/Quotes');
    }

    /**
     * Add Javascript to post pages.
     *
     * @param postController $sender
     */
    public function postController_render_before($sender) {
        $sender->addJsFile('quotes.js', 'plugins/Quotes');
    }

    /**
     * Add 'Quote' option to discussion via the reactions row after each post.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_afterFlag_handler($sender, $args) {
        $this->addQuoteButton($sender, $args);
    }

    /**
     * Output Quote link.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    protected function addQuoteButton($sender, $args) {
        // There are some case were Discussion is not set as an event argument so we use the sender data instead.
        $discussion = $sender->data('Discussion');
        if (!$discussion) {
            return;
        }

        if (!Gdn::session()->UserID) {
            return;
        }

        if (!Gdn::session()->checkPermission('Vanilla.Comments.Add', false, 'Category', $discussion->PermissionCategoryID)) {
            return;
        }

        if (isset($args['Comment'])) {
            $object = $args['Comment'];
            $objectID = 'Comment_'.$object->CommentID;
        } elseif ($discussion) {
            $object = $discussion;
            $objectID = 'Discussion_'.$object->DiscussionID;
        } else {
            return;
        }

        echo Gdn_Theme::bulletItem('Flags');
        echo anchor(sprite('ReactQuote', 'ReactSprite').' '.t('Quote'), url("post/quote/{$object->DiscussionID}/{$objectID}", true), 'ReactButton Quote Visible').' ';
    }

    /**
     * Build quotes in a post.
     *
     * @param discussionController $sender
     */
    public function discussionController_beforeDiscussionDisplay_handler($sender) {
        $this->renderQuotes($sender);
    }

    public function postController_beforeDiscussionDisplay_handler($sender) {
        $this->renderQuotes($sender);
    }

    public function discussionController_beforeCommentDisplay_handler($sender) {
        $this->renderQuotes($sender);
    }

    public function postController_beforeCommentDisplay_handler($sender) {
        $this->renderQuotes($sender);
    }

    /**
     * Render quotes.
     *
     * @param $sender
     */
    protected function renderQuotes($sender) {
        if (!$this->HandleRenderQuotes) {
            return;
        }

        /** @var string|null $ValidateUsernameRegex */
        static $validateUsernameRegex = null;

        if (is_null($validateUsernameRegex)) {
            $validateUsernameRegex = sprintf("[%s]+", c('Garden.User.ValidationRegex', "\d\w_ "));
        }

        if (isset($sender->EventArguments['Comment'])) {
            $object = $sender->EventArguments['Comment'];
        } elseif (isset($sender->EventArguments['Discussion'])) {
            $object = $sender->EventArguments['Discussion'];
        } else {
            return;
        }

        switch ($object->Format) {
            case 'Html':
                $object->Body = preg_replace_callback("/(<blockquote\s+(?:class=\"(?:User)?Quote\")?\s+rel=\"([^\"]+)\">)/ui", [$this, 'QuoteAuthorCallback'], $object->Body);
                $object->Body = str_ireplace('</blockquote>', '</p></div></blockquote>', $object->Body);
                break;
//         case 'Wysiwyg':
//            $Object->Body = preg_replace_callback("/(<blockquote\s+(?:class=\"(?:User)?Quote\")?\s+rel=\"([^\"]+)\">)/ui", array($this, 'QuoteAuthorCallback'), $Object->Body);
//            $Object->Body = str_ireplace('</blockquote>','</p></div></blockquote>',$Object->Body);
//            break;

            // WHY IS BBCODE PARSING DONE FOR MARKDOWN?
            case 'Markdown':
                // BBCode quotes with authors
                $object->Body = preg_replace_callback("#(\[quote(\s+author)?=[\"']?(.*?)(\s+link.*?)?(;[\d]+)?[\"']?\])#usi", [$this, 'QuoteAuthorCallback'], $object->Body);

                // BBCode quotes without authors
                $object->Body = str_ireplace('[quote]', '<blockquote class="Quote UserQuote"><div class="QuoteText"><p>', $object->Body);

                // End of BBCode quotes
                $object->Body = str_ireplace('[/quote]', '</p></div></blockquote>', $object->Body);
                break;

            case 'Display':
            case 'Text':
            case 'TextEx':
            default:
                break;
        }
    }

    /**
     * Get HTML reference to the quote author.
     *
     * @param array $matches
     * @return string HTML.
     */
    protected function quoteAuthorCallback($matches) {
        $attribution = t('%s said:');
        $link = anchor($matches[2], '/profile/'.$matches[2], '', ['rel' => 'nofollow']);
        $attribution = sprintf($attribution, $link);
        return <<<BLOCKQUOTE
      <blockquote class="UserQuote"><div class="QuoteAuthor">{$attribution}</div><div class="QuoteText"><p>
BLOCKQUOTE;
    }

    /**
     * Quote endpoint.
     *
     * @param postController $sender
     */
    public function postController_quote_create($sender) {
        if (sizeof($sender->RequestArgs) < 2) {
            return;
        }
        $selector = $sender->RequestArgs[1];
        $sender->setData('Plugin.Quotes.QuoteSource', $selector);
        $sender->View = 'comment';
        return $sender->comment();
    }

    /**
     * Format quotes on the posting page.
     *
     * @param postController $sender
     */
    public function postController_beforeCommentRender_handler($sender) {
        $sender->permission('Garden.SignIn.Allow');

        if ($sender->data('Plugin.Quotes.QuoteSource')) {
            if (sizeof($sender->RequestArgs) < 2) {
                return;
            }
            $selector = $sender->RequestArgs[1];
            list($type, $id) = explode('_', $selector);
            $quoteData = [
                'status' => 'failed'
            ];
            $this->formatQuote($type, $id, $quoteData);
            if ($quoteData['status'] == 'success') {
                $sender->Form->setValue('Body', "{$quoteData['body']}\n");
            }
        }
    }

    /**
     * Format the quote.
     *
     * @param string $type
     * @param int $id
     * @param array $quoteData
     * @param bool $format
     */
    protected function formatQuote($type, $id, &$quoteData, $format = false) {
        // Temporarily disable Emoji parsing (prevent double-parsing to HTML)
        $emojiEnabled = Emoji::instance()->enabled;
        Emoji::instance()->enabled = false;

        if (!$format) {
            $format = c('Garden.InputFormatter');
        }

        $discussionModel = new DiscussionModel();
        $type = strtolower($type);
        switch ($type) {
            case 'comment':
                $commentModel = new CommentModel();
                $data = $commentModel->getID($id);
                $discussion = $discussionModel->getID(val('DiscussionID', $data));
                break;
            case 'discussion':
                $data = $discussionModel->getID($id);
                $discussion = $data;
                break;
        }

        if ($discussion) {
            // Check permission.
            Gdn::controller()->permission(
                ['Vanilla.Discussions.Add', 'Vanilla.Discussions.View'],
                false,
                'Category',
                val('PermissionCategoryID', $discussion)
            );

            $newFormat = $format;
            if ($newFormat == 'Wysiwyg') {
                $newFormat = 'Html';
            }
            $quoteFormat = $data->Format;
            if ($quoteFormat == 'Wysiwyg') {
                $quoteFormat = 'Html';
            }

            // Perform transcoding if possible
            $newBody = $data->Body;
            if ($quoteFormat != $newFormat) {
                if (in_array($newFormat, ['Html', 'Wysiwyg'])) {
                    $newBody = Gdn_Format::to($newBody, $quoteFormat);
                } elseif ($quoteFormat == 'Html' && $newFormat == 'BBCode') {
                    $newBody = Gdn_Format::text($newBody, false);
                } elseif ($quoteFormat == 'Text' && $newFormat == 'BBCode') {
                    $newBody = Gdn_Format::text($newBody, false);
                } else {
                    $newBody = Gdn_Format::plainText($newBody, $quoteFormat);
                }

                if (!in_array($newFormat, ['Html', 'Wysiwyg'])) {
                    Gdn::controller()->informMessage(sprintf(
                        t('The quote had to be converted from %s to %s.', 'The quote had to be converted from %s to %s. Some formatting may have been lost.'),
                        htmlspecialchars($quoteFormat),
                        htmlspecialchars($newFormat)
                    ));
                }
            }
            $data->Body = $newBody;
            $this->EventArguments['String'] = &$data->Body;
            $this->fireEvent('FilterContent');

            // Format the quote according to the format.
            switch ($format) {
                case 'Html':   // HTML
                    $quote = '<blockquote class="Quote" rel="'.htmlspecialchars($data->InsertName).'">'.$data->Body.'</blockquote>'."\n";
                    break;

                case 'BBCode':
                    $author = htmlspecialchars($data->InsertName);
                    if ($id) {
                        $iDString = ';'.($type === 'comment' ? 'c' : 'd').'-'.htmlspecialchars($id);
                    }

                    $quoteBody = $data->Body;

                    // TODO: Strip inner quotes...
//                  $QuoteBody = trim(preg_replace('`(\[quote.*/quote\])`si', '', $QuoteBody));

                    $quote = <<<BQ
[quote="{$author}{$iDString}"]{$quoteBody}[/quote]

BQ;
                    break;

                case 'Markdown':
                case 'Display':
                case 'Text':
                case 'TextEx':
                    $quoteBody = $data->Body;
                    $insertName = $data->InsertName;
                    if (preg_match('/[^\w-]/', $insertName)) {
                        $insertName = '"'.$insertName.'"';
                    }
                    $quote = '> '.sprintf(t('%s said:'), '@'.$insertName)."\n".
                        '> '.str_replace("\n", "\n> ", $quoteBody)."\n";

                    break;
                case 'Wysiwyg':
                    $attribution = sprintf(t('%s said:'), userAnchor($data, null, ['Px' => 'Insert']));
                    $quoteBody = $data->Body;

                    // TODO: Strip inner quotes...
//                  $QuoteBody = trim(preg_replace('`(<blockquote.*/blockquote>)`si', '', $QuoteBody));

                    $quote = <<<BLOCKQUOTE
<blockquote class="Quote">
  <div class="QuoteAuthor">$attribution</div>
  <div class="QuoteText">$quoteBody</div>
</blockquote>

BLOCKQUOTE;

                    break;
            }

            $quoteData = array_merge($quoteData, [
                'status' => 'success',
                'body' => $quote,
                'format' => $format,
                'authorid' => $data->InsertUserID,
                'authorname' => $data->InsertName,
                'type' => $type,
                'typeid' => $id
            ]);
        }

        // Undo Emoji disable.
        Emoji::instance()->enabled = $emojiEnabled;
    }

    /**
     * Extra parsing for Markdown.
     *
     * @param string $text
     * @return string
     */
    protected static function _stripMarkdownQuotes($text) {
        $text = preg_replace('/
              (                                # Wrap whole match in $1
                (?>
                  ^[ ]*>[ ]?            # ">" at the start of a line
                    .+\n                    # rest of the first line
                  (.+\n)*                    # subsequent consecutive lines
                  \n*                        # blanks
                )+
              )
            /xm', '', $text);

        return $text;
    }

    /**
     * Remove mentions from quotes so we don't generate notifications.
     *
     * @param string $text
     * @return string
     */
    protected static function _stripMentions($text) {
        $text = preg_replace(
            '/(^|[\s,\.>])@(\w{1,50})\b/i',
            '$1$2',
            $text
        );

        return $text;
    }
}
