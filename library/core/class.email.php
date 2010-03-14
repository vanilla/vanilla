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
      $this->PhpMailer = new PHPMailer();
      $this->PhpMailer->CharSet = Gdn::Config('Garden.Charset', 'utf-8');
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
      if ($SenderEmail == '')
         $SenderEmail = Gdn::Config('Garden.Email.SupportAddress', '');

      if ($SenderName == '')
         $SenderName = Gdn::Config('Garden.Email.SupportName', '');
      
      if($bOverrideSender != FALSE){
         $this->PhpMailer->ClearReplyTos();
         $this->PhpMailer->Sender = $SenderEmail;
      }
         
      $this->PhpMailer->SetFrom($SenderEmail, $SenderName);

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
      if ($this->PhpMailer->ContentType == 'text/html') {
         $this->PhpMailer->MsgHTML($Message);
      } else {
         $this->PhpMailer->Body = $Message;
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
   public function Send() {
      if (Gdn::Config('Garden.Email.UseSmtp')) {
         $this->PhpMailer->IsSMTP();
         $SmtpHost = Gdn::Config('Garden.Email.SmtpHost', '');
         $SmtpPort = Gdn::Config('Garden.Email.SmtpPort', 25);
         if (strpos($SmtpHost, ':') !== FALSE) {
            list($SmtpHost, $SmtpPort) = explode(':', $SmtpHost);
         }

         $this->PhpMailer->Host = $SmtpHost;
         $this->PhpMailer->Port = $SmtpPort;
         $this->PhpMailer->Username = $Username = Gdn::Config('Garden.Email.SmtpUser', '');
         $this->PhpMailer->Password = $Password = Gdn::Config('Garden.Email.SmtpPassword', '');
         if(!empty($Username))
            $this->PhpMailer->SMTPAuth = TRUE;

         
      } else {
         $this->PhpMailer->IsMail();
      }

      if (!$this->PhpMailer->Send()) {
         throw new Exception($this->PhpMailer->ErrorInfo);
      }

      return true;
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

   /**
    * Adds to the "To" recipient collection.
    *
    * @param mixed $RecipientEmail An email (or array of emails) to add to the "To" recipient collection.
    * @param string $RecipientName The recipient name associated with $RecipientEmail. If $RecipientEmail is
    * an array of email addresses, this value will be ignored.
    */
   
   public function To($RecipientEmail, $RecipientName = '') {
      if (is_string($RecipientEmail) && StrPos($RecipientEmail, ',') > 0) {
         $RecipientEmail = explode(',', $RecipientEmail);
         $RecipientEmail = array_map('trim', $RecipientEmail);
         $RecipientName = array_fill(0, Count($RecipientEmail), $RecipientName);
      } elseif ($RecipientEmail instanceof Gdn_DataSet) 
            $RecipientEmail = ConsolidateArrayValuesByKey($RecipientEmail->ResultArray(), 'Email', 'Name', '');
      
      if (!is_array($RecipientEmail)) {
         // Only allow one address in the "to" field. Append all extras to the "cc" field.
         if (!$this->_IsToSet) {
            $this->PhpMailer->AddAddress($RecipientEmail, $RecipientName);
            $this->_IsToSet = True;
         }
         else {
            $this->Cc($RecipientEmail, $RecipientName);
         }
   
         return $this;
      }
      
      if ($this->PhpMailer->Mailer == 'smtp' || Gdn::Config('Garden.Email.UseSmtp'))
         throw new Exception('You cannot address emails to more than one address when using SMTP.');
      
      $this->PhpMailer->SingleTo = True;
      
      if(array_key_exists(0, $RecipientEmail) && is_object($RecipientEmail[0])){
         $RecipientName = array();
         $Count = Count($RecipientEmail);
         for($i = 0; $i < $Count; $i++){
            $RecipientName[$i] = ObjectValue('Name', $RecipientEmail[$i]);
            $RecipientEmail[$i] = ObjectValue('Email', $RecipientEmail[$i]);
         }
      }
      
      $Count = Count($RecipientEmail);
      if(is_array($RecipientName) && $Count == Count($RecipientName))
         $RecipientEmail = array_combine($RecipientEmail, $RecipientName);
      elseif($RecipientName == '') $RecipientEmail = array_combine($RecipientEmail, array_fill(0, $Count, ''));
      
      foreach($RecipientEmail as $Email => $Name)
         $this->PhpMailer->AddAddress($Email, $Name);
      
      return $this;
   }
   
   public function Charset($Use = ''){
      if ($Use != '') {
         $this->PhpMailer->CharSet = $Use;
         return $this;
      }
      return $this->PhpMailer->CharSet;
   }
   
}