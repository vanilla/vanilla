<?php if (!defined('APPLICATION')) exit();

/**
 * Object Representation of an email. 
 * 
 * All public methods return $this for
 * chaining purposes. ie. $Email->Subject('Hi')->Message('Just saying hi!')-
 * To('joe@vanillaforums.com')->Send();
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Email extends Gdn_Pluggable {

   /**
    * @var PHPMailer
    */
   public $PhpMailer;

   /**
    * @var boolean
    */
   private $_IsToSet;
   
   /**
    *
    * @var array Recipients that were skipped because they lack permission.
    */
   public $Skipped = array();

   /**
    * Constructor
    */
   function __construct() {
      $this->PhpMailer = new PHPMailer();
      $this->PhpMailer->CharSet = C('Garden.Charset', 'utf-8');
      $this->PhpMailer->SingleTo = C('Garden.Email.SingleTo', FALSE);
      $this->PhpMailer->PluginDir = CombinePaths(array(PATH_LIBRARY,'vendors/phpmailer/'));
      $this->PhpMailer->Hostname = C('Garden.Email.Hostname', '');
      $this->PhpMailer->Encoding = 'quoted-printable';
      $this->Clear();
      parent::__construct();
   }
   
   /**
    * Add a custom header to the outgoing email.
    * @param string $Name
    * @param string $Value
    * @since 2.1
    */
   public function AddHeader($Name, $Value) {
      $this->PhpMailer->AddCustomHeader("$Name:$Value");
   }


   /**
    * Adds to the "Bcc" recipient collection.
    *
    * @param mixed $RecipientEmail An email (or array of emails) to add to the "Bcc" recipient collection.
    * @param string $RecipientName The recipient name associated with $RecipientEmail. If $RecipientEmail is
    * an array of email addresses, this value will be ignored.
    * @return Email
    */
   public function Bcc($RecipientEmail, $RecipientName = '') {
      ob_start();
      $this->PhpMailer->AddBCC($RecipientEmail, $RecipientName);
      ob_end_clean();
      return $this;
   }
   
   /**
    * Adds to the "Cc" recipient collection.
    *
    * @param mixed $RecipientEmail An email (or array of emails) to add to the "Cc" recipient collection.
    * @param string $RecipientName The recipient name associated with $RecipientEmail. If $RecipientEmail is
    * an array of email addresses, this value will be ignored.
    * @return Email
    */
   public function Cc($RecipientEmail, $RecipientName = '') {
      ob_start();
      $this->PhpMailer->AddCC($RecipientEmail, $RecipientName);
      ob_end_clean();
      return $this;
   }

   /**
    * Clears out all previously specified values for this object and restores
    * it to the state it was in when it was instantiated.
    *
    * @return Email
    */
   public function Clear() {
      $this->PhpMailer->ClearAllRecipients();
      $this->PhpMailer->Body = '';
      $this->PhpMailer->AltBody = '';
      $this->From();
      $this->_IsToSet = FALSE;
      $this->MimeType(Gdn::Config('Garden.Email.MimeType', 'text/plain'));
      $this->_MasterView = 'email.master';
      $this->Skipped = array();
      return $this;
   }

   /**
    * Allows the explicit definition of the email's sender address & name.
    * Defaults to the applications Configuration 'SupportEmail' & 'SupportName'
    * settings respectively.
    *
    * @param string $SenderEmail
    * @param string $SenderName
    * @return Email
    */
   public function From($SenderEmail = '', $SenderName = '', $bOverrideSender = FALSE) {
      if ($SenderEmail == '') {
         $SenderEmail = C('Garden.Email.SupportAddress', '');
         if (!$SenderEmail) {
            $SenderEmail = 'noreply@'.Gdn::Request()->Host();
         }
      }

      if ($SenderName == '')
         $SenderName = C('Garden.Email.SupportName', C('Garden.Title', ''));
      
      if($this->PhpMailer->Sender == '' || $bOverrideSender) $this->PhpMailer->Sender = $SenderEmail;
      
      ob_start();
      $this->PhpMailer->SetFrom($SenderEmail, $SenderName, FALSE);
      ob_end_clean();
      return $this;
   }

   /**
    * Allows the definition of a masterview other than the default:
    * "email.master".
    *
    * @param string $MasterView
    * @todo To implement
    * @return Email
    */
   public function MasterView($MasterView) {
      return $this;
   }

   /**
    * The message to be sent.
    *
    * @param string $Message The message to be sent.
    * @param string $TextVersion Optional plaintext version of the message
    * @return Email
    */
   public function Message($Message) {
   
      // htmlspecialchars_decode is being used here to revert any specialchar escaping done by Gdn_Format::Text()
      // which, untreated, would result in &#039; in the message in place of single quotes.
   
      if ($this->PhpMailer->ContentType == 'text/html') {
         $TextVersion = FALSE;
         if (stristr($Message, '<!-- //TEXT VERSION FOLLOWS//')) {
            $EmailParts = explode('<!-- //TEXT VERSION FOLLOWS//', $Message);
            $TextVersion = array_pop($EmailParts);
            $Message = array_shift($EmailParts);
            $TextVersion = trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s','',$TextVersion)));
            $Message = trim($Message);
         }
         
         $this->PhpMailer->MsgHTML(htmlspecialchars_decode($Message,ENT_QUOTES));
         if ($TextVersion !== FALSE && !empty($TextVersion)) {
            $TextVersion = html_entity_decode($TextVersion);
            $this->PhpMailer->AltBody = $TextVersion;
         }
      } else {
         $this->PhpMailer->Body = htmlspecialchars_decode($Message,ENT_QUOTES);
      }
      return $this;
   }
   
   public static function GetTextVersion($Template) {
      if (stristr($Template, '<!-- //TEXT VERSION FOLLOWS//')) {
         $EmailParts = explode('<!-- //TEXT VERSION FOLLOWS//', $Template);
         $TextVersion = array_pop($EmailParts);
         $TextVersion = trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s','',$TextVersion)));
         return $TextVersion;
      }
      return FALSE;
   }
   
   public static function GetHTMLVersion($Template) {
      if (stristr($Template, '<!-- //TEXT VERSION FOLLOWS//')) {
         $EmailParts = explode('<!-- //TEXT VERSION FOLLOWS//', $Template);
         $TextVersion = array_pop($EmailParts);
         $Message = array_shift($EmailParts);
         $Message = trim($Message);
         return $Message;
      }
      return $Template;
   }

   /**
    * Sets the mime-type of the email.
    *
    * Only accept text/plain or text/html.
    *
    * @param string $MimeType The mime-type of the email.
    * @return Email
    */
   public function MimeType($MimeType) {
      $this->PhpMailer->IsHTML($MimeType === 'text/html');
      return $this;
   }

   /**
    * @todo add port settings
    */
   public function Send($EventName = '') {
      if (C('Garden.Email.Disabled')) {
         return;
      }
      
      if (Gdn::Config('Garden.Email.UseSmtp')) {
         $this->PhpMailer->IsSMTP();
         $SmtpHost = Gdn::Config('Garden.Email.SmtpHost', '');
         $SmtpPort = Gdn::Config('Garden.Email.SmtpPort', 25);
         if (strpos($SmtpHost, ':') !== FALSE) {
            list($SmtpHost, $SmtpPort) = explode(':', $SmtpHost);
         }

         $this->PhpMailer->Host = $SmtpHost;
         $this->PhpMailer->Port = $SmtpPort;
         $this->PhpMailer->SMTPSecure = Gdn::Config('Garden.Email.SmtpSecurity', '');
         $this->PhpMailer->Username = $Username = Gdn::Config('Garden.Email.SmtpUser', '');
         $this->PhpMailer->Password = $Password = Gdn::Config('Garden.Email.SmtpPassword', '');
         if(!empty($Username))
            $this->PhpMailer->SMTPAuth = TRUE;

         
      } else {
         $this->PhpMailer->IsMail();
      }
      
      if($EventName != ''){
         $this->EventArguments['EventName'] = $EventName;
         $this->FireEvent('SendMail');
      }
      
      if (!empty($this->Skipped) && $this->PhpMailer->CountRecipients() == 0) {
         // We've skipped all recipients.
         return TRUE;
      }

      $this->PhpMailer->ThrowExceptions(TRUE);
      if (!$this->PhpMailer->Send()) {
         throw new Exception($this->PhpMailer->ErrorInfo);
      }
      
      return TRUE;
   }
   
   /**
    * Adds subject of the message to the email.
    * 
    * @param string $Subject The subject of the message.
    */
   public function Subject($Subject) {
      $this->PhpMailer->Subject = $Subject;
      return $this;  
   }

   
   public function AddTo($RecipientEmail, $RecipientName = ''){
      ob_start();
      $this->PhpMailer->AddAddress($RecipientEmail, $RecipientName);
      ob_end_clean();
      return $this;
   }
   
   /**
    * Adds to the "To" recipient collection.
    *
    * @param mixed $RecipientEmail An email (or array of emails) to add to the "To" recipient collection.
    * @param string $RecipientName The recipient name associated with $RecipientEmail. If $RecipientEmail is
    * an array of email addresses, this value will be ignored.
    */
   public function To($RecipientEmail, $RecipientName = '') {

      if (is_string($RecipientEmail)) {
         if (strpos($RecipientEmail, ',') > 0) {
            $RecipientEmail = explode(',', $RecipientEmail);
            // trim no need, PhpMailer::AddAnAddress() will do it
            return $this->To($RecipientEmail, $RecipientName);
         }
         if ($this->PhpMailer->SingleTo) return $this->AddTo($RecipientEmail, $RecipientName);
         if (!$this->_IsToSet){
            $this->_IsToSet = TRUE;
            $this->AddTo($RecipientEmail, $RecipientName);
         } else
            $this->Cc($RecipientEmail, $RecipientName);
         return $this;
         
      } elseif ((is_object($RecipientEmail) && property_exists($RecipientEmail, 'Email'))
         || (is_array($RecipientEmail) && isset($RecipientEmail['Email']))) {
         
         $User = $RecipientEmail;
         $RecipientName = GetValue('Name', $User);
         $RecipientEmail = GetValue('Email', $User);
         $UserID = GetValue('UserID', $User, FALSE);
         
         if ($UserID !== FALSE) {
            // Check to make sure the user can receive email.
            if (!Gdn::UserModel()->CheckPermission($UserID, 'Garden.Email.View')) {
               $this->Skipped[] = $User;
               
               return $this;
            }
         }
         
         return $this->To($RecipientEmail, $RecipientName);
      
      } elseif ($RecipientEmail instanceof Gdn_DataSet) {
         foreach($RecipientEmail->ResultObject() as $Object) $this->To($Object);
         return $this;
        
      } elseif (is_array($RecipientEmail)) {
         $Count = count($RecipientEmail);
         if (!is_array($RecipientName)) $RecipientName = array_fill(0, $Count, '');
         if ($Count == count($RecipientName)) {
            $RecipientEmail = array_combine($RecipientEmail, $RecipientName);
            foreach($RecipientEmail as $Email => $Name) $this->To($Email, $Name);
         } else
            trigger_error(ErrorMessage('Size of arrays do not match', 'Email', 'To'), E_USER_ERROR);
         
         return $this;
      }
      
      trigger_error(ErrorMessage('Incorrect first parameter ('.GetType($RecipientEmail).') passed to function.', 'Email', 'To'), E_USER_ERROR);
   }
   
   public function Charset($Use = ''){
      if ($Use != '') {
         $this->PhpMailer->CharSet = $Use;
         return $this;
      }
      return $this->PhpMailer->CharSet;
   }
   
}