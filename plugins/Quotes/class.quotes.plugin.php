<?php if (!defined('APPLICATION')) exit();

/**
 * Quotes Plugin
 *
 * This plugin allows users to quote comments for reference in their own comments
 * within a discussion.
 *
 * Changes:
 *  1.0     Initial release
 *  1.6.1   Overhaul
 *  1.6.4   Moved button to reactions area & changed js accordingly.
 *  1.6.8   Textarea target will now automatically resize to fit text body.
 *  1.6.9   Security fix.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Addons
 */
// Define the plugin:
$PluginInfo['Quotes'] = array(
    'Name' => 'Quotes',
    'Description' => "Adds an option to each comment for users to easily quote each other.",
    'Version' => '1.6.10',
    'MobileFriendly' => TRUE,
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'RequiredTheme' => FALSE,
    'RequiredPlugins' => FALSE,
    'HasLocale' => TRUE,
    'RegisterPermissions' => FALSE,
    'Author' => "Tim Gunter",
    'AuthorEmail' => 'tim@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
);

class QuotesPlugin extends Gdn_Plugin {
   public $HandleRenderQuotes = TRUE;

   public function __construct() {
      parent::__construct();

      if (function_exists('ValidateUsernameRegex')) {
         $this->ValidateUsernameRegex = ValidateUsernameRegex();
      } else {
         $this->ValidateUsernameRegex = "[\d\w_]{3,20}";
      }

      // Whether to handle drawing quotes or leave it up to some other plugin
      $this->HandleRenderQuotes = C('Plugins.Quotes.RenderQuotes', TRUE);
   }

   public function ProfileController_AfterAddSideMenu_Handler($Sender) {
      if (!Gdn::Session()->CheckPermission('Garden.SignIn.Allow')) {
         return;
      }

      $SideMenu = $Sender->EventArguments['SideMenu'];
      $ViewingUserID = Gdn::Session()->UserID;

      if ($Sender->User->UserID == $ViewingUserID) {
         $SideMenu->AddLink('Options', Sprite('SpQuote').' '.T('Quote Settings'), '/profile/quotes', FALSE, array('class' => 'Popup'));
      } else {
         $SideMenu->AddLink('Options', Sprite('SpQuote').' '.T('Quote Settings'), UserUrl($Sender->User, '', 'quotes'), 'Garden.Users.Edit', array('class' => 'Popup'));
      }
   }

   public function ProfileController_Quotes_Create($Sender) {
      $Sender->Permission('Garden.SignIn.Allow');
      $Sender->Title(T("Quotes Settings"));

      $Args = $Sender->RequestArgs;
      if (sizeof($Args) < 2) {
         $Args = array_merge($Args, array(0, 0));
      } elseif (sizeof($Args) > 2) {
         $Args = array_slice($Args, 0, 2);
      }

      list($UserReference, $Username) = $Args;

      $Sender->GetUserInfo($UserReference, $Username);
      $UserPrefs = Gdn_Format::Unserialize($Sender->User->Preferences);
      if (!is_array($UserPrefs)) {
         $UserPrefs = array();
      }

      $UserID = Gdn::Session()->UserID;
      $ViewingUserID = $UserID;

      if ($Sender->User->UserID != $ViewingUserID) {
         $Sender->Permission('Garden.Users.Edit');
         $UserID = $Sender->User->UserID;
         $Sender->SetData('ForceEditing', $Sender->User->Name);
      } else {
         $Sender->SetData('ForceEditing',  FALSE);
      }

      $QuoteFolding = GetValue('Quotes.Folding', $UserPrefs, '1');
      $Sender->Form->SetValue('QuoteFolding', $QuoteFolding);

      $Sender->SetData('QuoteFoldingOptions', array(
         'None' => T("Don't fold quotes"),
         '1' => Plural(1, '%s level deep', '%s levels deep'),
         '2' => Plural(2, '%s level deep', '%s levels deep'),
         '3' => Plural(3, '%s level deep', '%s levels deep'),
         '4' => Plural(4, '%s level deep', '%s levels deep'),
         '5' => Plural(5, '%s level deep', '%s levels deep')
      ));

      // Form submission handling.
      if ($Sender->Form->AuthenticatedPostBack()) {
         $NewFoldingLevel = $Sender->Form->GetValue('QuoteFolding', '1');
         if ($NewFoldingLevel != $QuoteFolding) {
            Gdn::UserModel()->SavePreference($UserID, 'Quotes.Folding', $NewFoldingLevel);
            $Sender->InformMessage(T("Your changes have been saved."));
         }
      }

      $Sender->Render('quotes', '', 'plugins/Quotes');
   }

   public function DiscussionController_BeforeDiscussionRender_Handler($Sender) {
      if (!Gdn::Session()->IsValid()) {
         return;
      }

      $UserPrefs = Gdn_Format::Unserialize(Gdn::Session()->User->Preferences);
      if (!is_array($UserPrefs)) {
         $UserPrefs = array();
      }

      $QuoteFolding = GetValue('Quotes.Folding', $UserPrefs, '1');
      $Sender->AddDefinition('QuotesFolding', $QuoteFolding);
   }

   public function PluginController_Quotes_Create($Sender) {
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }

   public function Controller_Getquote($Sender) {
      $this->DiscussionController_GetQuote_Create($Sender);
   }

   public function DiscussionController_GetQuote_Create($Sender, $Selector, $Format = FALSE) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);

      if (!$Format)
         $Format = C('Garden.InputFormatter');

      $QuoteData = array(
          'status' => 'failed'
      );
//      array_shift($Sender->RequestArgs);
//      if (sizeof($Sender->RequestArgs)) {
      $QuoteData['selector'] = $Selector;
      list($Type, $ID) = explode('_', $Selector);
      $this->FormatQuote($Type, $ID, $QuoteData, $Format);
//      }
      $Sender->SetJson('Quote', $QuoteData);
      $Sender->Render('GetQuote', '', 'plugins/Quotes');
   }

   public function DiscussionController_Render_Before($Sender) {
      $this->PrepareController($Sender);
   }

   public function PostController_Render_Before($Sender) {
      $this->PrepareController($Sender);
   }

   protected function PrepareController($Sender) {
      //if (!$this->HandleRenderQuotes) return;
      $Sender->AddJsFile('quotes.js', 'plugins/Quotes');
   }

   /**
    * Add 'Quote' option to Discussion.
    */
   public function Base_AfterFlag_Handler($Sender, $Args) {
      echo Gdn_Theme::BulletItem('Flags');
      $this->AddQuoteButton($Sender, $Args);
   }

   /**
    * Output Quote link.
    */
   protected function AddQuoteButton($Sender, $Args) {
      if (!Gdn::Session()->UserID) {
         return;
      }

      if (isset($Args['Comment'])) {
         $Object = $Args['Comment'];
         $ObjectID = 'Comment_' . $Args['Comment']->CommentID;
      } else if (isset($Args['Discussion'])) {
         $Object = $Args['Discussion'];
         $ObjectID = 'Discussion_' . $Args['Discussion']->DiscussionID;
      } else {
         return;
      }

      echo Anchor(Sprite('ReactQuote', 'ReactSprite').' '.T('Quote'), Url("post/quote/{$Object->DiscussionID}/{$ObjectID}", TRUE), 'ReactButton Quote Visible').' ';
   }

   public function DiscussionController_BeforeDiscussionDisplay_Handler($Sender) {
      $this->RenderQuotes($Sender);
   }

   public function PostController_BeforeDiscussionDisplay_Handler($Sender) {
      $this->RenderQuotes($Sender);
   }

   public function DiscussionController_BeforeCommentDisplay_Handler($Sender) {
      $this->RenderQuotes($Sender);
   }

   public function PostController_BeforeCommentDisplay_Handler($Sender) {
      $this->RenderQuotes($Sender);
   }

   protected function RenderQuotes($Sender) {
      if (!$this->HandleRenderQuotes) {
         return;
      }

      static $ValidateUsernameRegex = NULL;

      if (is_null($ValidateUsernameRegex)) {
         $ValidateUsernameRegex = sprintf("[%s]+", C('Garden.User.ValidationRegex', "\d\w_ "));
      }

      if (isset($Sender->EventArguments['Comment'])) {
         $Object = $Sender->EventArguments['Comment'];
      } elseif (isset($Sender->EventArguments['Discussion'])) {
         $Object = $Sender->EventArguments['Discussion'];
      } else {
         return;
      }

      switch ($Object->Format) {
         case 'Html':
            $Object->Body = preg_replace_callback("/(<blockquote\s+(?:class=\"(?:User)?Quote\")?\s+rel=\"([^\"]+)\">)/ui", array($this, 'QuoteAuthorCallback'), $Object->Body);
            $Object->Body = str_ireplace('</blockquote>', '</p></div></blockquote>', $Object->Body);
            break;
//         case 'Wysiwyg':
//            $Object->Body = preg_replace_callback("/(<blockquote\s+(?:class=\"(?:User)?Quote\")?\s+rel=\"([^\"]+)\">)/ui", array($this, 'QuoteAuthorCallback'), $Object->Body);
//            $Object->Body = str_ireplace('</blockquote>','</p></div></blockquote>',$Object->Body);
//            break;
         case 'Markdown':
            // BBCode quotes with authors
            $Object->Body = preg_replace_callback("#(\[quote(\s+author)?=[\"']?(.*?)(\s+link.*?)?(;[\d]+)?[\"']?\])#usi", array($this, 'QuoteAuthorCallback'), $Object->Body);

            // BBCode quotes without authors
            $Object->Body = str_ireplace('[quote]', '<blockquote class="Quote UserQuote"><div class="QuoteText"><p>', $Object->Body);

            // End of BBCode quotes
            $Object->Body = str_ireplace('[/quote]', '</p></div></blockquote>', $Object->Body);
            break;

         case 'Display':
         case 'Text':
         default:
            break;
      }
   }

   protected function QuoteAuthorCallback($Matches) {
      $Attribution = T('%s said:');
      $Link = Anchor($Matches[2], '/profile/' . $Matches[2], '', array('rel' => 'nofollow'));
      $Attribution = sprintf($Attribution, $Link);
      return <<<BLOCKQUOTE
      <blockquote class="UserQuote"><div class="QuoteAuthor">{$Attribution}</div><div class="QuoteText"><p>
BLOCKQUOTE;
   }

   public function PostController_Quote_Create($Sender) {
      if (sizeof($Sender->RequestArgs) < 2) {
         return;
      }
      $Selector = $Sender->RequestArgs[1];
      $Sender->SetData('Plugin.Quotes.QuoteSource', $Selector);
      $Sender->View = 'comment';
      return $Sender->Comment();
   }

   public function PostController_BeforeCommentRender_Handler($Sender) {
      if (isset($Sender->Data['Plugin.Quotes.QuoteSource'])) {
         if (sizeof($Sender->RequestArgs) < 2) {
            return;
         }
         $Selector = $Sender->RequestArgs[1];
         list($Type, $ID) = explode('_', $Selector);
         $QuoteData = array(
             'status' => 'failed'
         );
         $this->FormatQuote($Type, $ID, $QuoteData);
         if ($QuoteData['status'] == 'success') {
            $Sender->Form->SetValue('Body', "{$QuoteData['body']}\n");
         }
      }
   }

   protected function FormatQuote($Type, $ID, &$QuoteData, $Format = FALSE) {
      if (!$Format) {
         $Format = C('Garden.InputFormatter');
      }

      $Type = strtolower($Type);
      $Model = FALSE;
      switch ($Type) {
         case 'comment':
            $Model = new CommentModel();
            break;

         case 'discussion':
            $Model = new DiscussionModel();
            break;

         default:
            break;
      }

      //$QuoteData = array();
      if ($Model) {
         $Data = $Model->GetID($ID);
         $NewFormat = $Format;
         if ($NewFormat == 'Wysiwyg') {
            $NewFormat = 'Html';
         }
         $QuoteFormat = $Data->Format;
         if ($QuoteFormat == 'Wysiwyg') {
            $QuoteFormat = 'Html';
         }

         // Perform transcoding if possible
         $NewBody = $Data->Body;
         if ($QuoteFormat != $NewFormat) {
            if (in_array($NewFormat, array('Html', 'Wysiwyg'))) {
               $NewBody = Gdn_Format::To($NewBody, $QuoteFormat);
            } elseif ($QuoteFormat == 'Html' && $NewFormat == 'BBCode') {
               $NewBody = Gdn_Format::Text($NewBody, false);
            } elseif ($QuoteFormat == 'Text' && $NewFormat == 'BBCode') {
               $NewBody = Gdn_Format::Text($NewBody, false);
            } else {
               $NewBody = Gdn_Format::PlainText($NewBody, $QuoteFormat);
            }

            if (!in_array($NewFormat, array('Html', 'Wysiwyg'))) {
               Gdn::Controller()->InformMessage(sprintf(
                  T('The quote had to be converted from %s to %s.', 'The quote had to be converted from %s to %s. Some formatting may have been lost.'),
                  htmlspecialchars($QuoteFormat),
                  htmlspecialchars($NewFormat)
               ));
            }
         }
         $Data->Body = $NewBody;

         // Format the quote according to the format.
         switch ($Format) {
            case 'Html':   // HTML
               $Quote = '<blockquote class="Quote" rel="' . htmlspecialchars($Data->InsertName) . '">' . $Data->Body . '</blockquote>' . "\n";
               break;

            case 'BBCode':
               $Author = htmlspecialchars($Data->InsertName);
               if ($ID)
                  $IDString = ';' . htmlspecialchars($ID);

               $QuoteBody = $Data->Body;

               // TODO: Strip inner quotes...
//                  $QuoteBody = trim(preg_replace('`(\[quote.*/quote\])`si', '', $QuoteBody));

               $Quote = <<<BQ
[quote="{$Author}{$IDString}"]{$QuoteBody}[/quote]

BQ;
               break;

            case 'Markdown':
            case 'Display':
            case 'Text':
               $QuoteBody = $Data->Body;

               // Strip inner quotes and mentions...
               $QuoteBody = self::_StripMarkdownQuotes($QuoteBody);
               $QuoteBody = self::_StripMentions($QuoteBody);

               $Quote = '> ' . sprintf(T('%s said:'), '@' . $Data->InsertName) . "\n" .
               '> ' . str_replace("\n", "\n> ", $QuoteBody)."\n";

               break;
            case 'Wysiwyg':
               $Attribution = sprintf(T('%s said:'), UserAnchor($Data, NULL, array('Px' => 'Insert')));
               $QuoteBody = $Data->Body;

               // TODO: Strip inner quotes...
//                  $QuoteBody = trim(preg_replace('`(<blockquote.*/blockquote>)`si', '', $QuoteBody));

               $Quote = <<<BLOCKQUOTE
<blockquote class="Quote">
  <div class="QuoteAuthor">$Attribution</div>
  <div class="QuoteText">$QuoteBody</div>
</blockquote>

BLOCKQUOTE;

               break;
         }

         $QuoteData = array_merge($QuoteData, array(
             'status' => 'success',
             'body' => $Quote,
             'format' => $Format,
             'authorid' => $Data->InsertUserID,
             'authorname' => $Data->InsertName,
             'type' => $Type,
             'typeid' => $ID
         ));
      }
   }

   public function Setup() {
     // Nothing to do here!
   }

   protected static function _StripMarkdownQuotes($Text) {
      $Text = preg_replace('/
              (                                # Wrap whole match in $1
                (?>
                  ^[ ]*>[ ]?            # ">" at the start of a line
                    .+\n                    # rest of the first line
                  (.+\n)*                    # subsequent consecutive lines
                  \n*                        # blanks
                )+
              )
            /xm', '', $Text);

      return $Text;
   }

   protected static function _StripMentions($Text) {
      $Text = preg_replace(
      '/(^|[\s,\.>])@(\w{1,50})\b/i', '$1$2', $Text
      );

      return $Text;
   }

   public function OnDisable() {
     // Nothing to do here!
   }

   public function Structure() {
      // Nothing to do here!
   }

}
