<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Object Representation of an email. All public methods return $this for
 * chaining purposes. ie. $Email->Subject('Hi')->Message('Just saying hi!')-
 * To('joe@vanillaforums.com')->Send();
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @todo This class needs to be tested on a function mail server and with SMTP
 * @namespace Garden.Core
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
    * Constructor
    */
   function __construct() {
      $this->PhpMailer = new PHPMailer(TRUE); // throw Exceptions
      $this->PhpMailer->CharSet = Gdn::Config('Garden.Charset', 'utf-8');
      $this->PhpMailer->SingleTo = Gdn::Config('Garden.Email.SingleTo', FALSE);
      $this->PhpMailer->PluginDir = PATH_LIBRARY.DS.'vendors'.DS.'phpmailer'.DS;
      $this->Clear();
      parent::__construct();
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
      $this->PhpMailer->AddBCC($RecipientEmail, $RecipientName);
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
      $this->PhpMailer->AddCC($RecipientEmail, $RecipientName);
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
      
      $this->PhpMailer->SetFrom($SenderEmail, $SenderName, FALSE);

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
    * @tod: implement
    * @return Email
    */
   public function Message($Message) {
   
      // htmlspecialchars_decode is being used here to revert any specialchar escaping done by Gdn_Format::Text()
      // which, untreated, would result in &#039; in the message in place of single quotes.
   
      if ($this->PhpMailer->ContentType == 'text/html') {
         $this->PhpMailer->MsgHTML(htmlspecialchars_decode($Message,ENT_QUOTES));
      } else {
         $this->PhpMailer->Body = htmlspecialchars_decode($Message,ENT_QUOTES);
      }
      return $this;
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
      $this->PhpMailer->AddAddress($RecipientEmail, $RecipientName);
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
      
       if (!is_string($RecipientEmail)) return $this->ToAll($RecipientEmail, $RecipientName);
      
      // Only allow one address in the "to" field. Append all extras to the "cc" field.
      if (!$this->_IsToSet) {
         $this->AddTo($RecipientEmail, $RecipientName);
         $this->_IsToSet = TRUE;
      }
      else {
         $this->Cc($RecipientEmail, $RecipientName);
      }
      return $this;
   }
   
   
    public function ToAll($Mixed, $Name = NULL, $Options = NULL) {
        
        $Prefix = GetValue('Prefix', $Options, '');

        if (is_string($Mixed)) {
            // Is it comma, semicolon separated emails?
            $Emails = preg_split('/[,;]/', $Mixed, -1, PREG_SPLIT_NO_EMPTY);
            $Names = array_fill(0, count($Emails), '');
            return $this->ToAll($Emails, $Names, $Options);
        }
        if (is_object($Mixed)) {
            if ($Mixed instanceof StdClass) {
                $RecipientEmail = $Mixed->{$Prefix.'Email'};
                $RecipientName = (property_exists($Mixed, $Prefix.'Name')) ? $Mixed->{$Prefix.'Name'} : '';
                return $this->AddTo($RecipientEmail, $RecipientName);
            }
            if ($Mixed instanceof Gdn_DataSet) {
                foreach ($Mixed->Result() as $Data) $this->ToAll($Data, NULL, $Options);
                return $this;
            }
        }
        if (is_array($Mixed)) {
            if (array_key_exists($Prefix.'Email', $Mixed)) {
                $RecipientEmail = $Mixed[$Prefix.'Email'];
                $RecipientName = array_key_exists($Prefix.'Name', $Mixed) ? $Mixed[$Prefix.'Name'] : '';
                return $this->AddTo($RecipientEmail, $RecipientName);
            }
            // Array of $Mixed collection, ex. Gdn_DataSet::Result()
            if ($Name === NULL) foreach ($Mixed as $Data) $this->ToAll($Data, NULL, $Options);
            else {
                $Emails = array_values($Mixed);
                $Name = array_values($Name);
                // Check size of arrays
                if (count($Name) != count($Emails)) throw new OutOfBoundsException(ErrorMessage('Size of arrays do not match', __CLASS__, __FUNCTION__), E_USER_ERROR);
                for ($Count = count($Emails), $Index = 0; $Index < $Count; $Index++) {
                    $this->AddTo($Emails[$Index], $Name[$Index]);
                }
                // $Mixed is Emails plain array and $Name is plain array same size? (HOLD)
                // $Mixed is [Email] => Name?
            }
            return $this;
        }
        
        throw new InvalidArgumentException(ErrorMessage('Incorrect first parameter: '.gettype($Mixed), __CLASS__, __FUNCTION__));
   }
   
   public function Charset($Use = ''){
      if ($Use != '') {
         $this->PhpMailer->CharSet = $Use;
         return $this;
      }
      return $this->PhpMailer->CharSet;
   }
   
}