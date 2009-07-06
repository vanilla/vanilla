<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/**
 * Object Representation of an email. All public methods return $this for
 * chaining purposes. ie. $Email->Subject('Hi')->Message('Just saying hi!')-
 * To('joe@lussumo.com')->Send();
 *
 * @author Mark O'Sullivan
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @todo This class needs to be tested on a function mail server and with SMTP
 * @namespace Lussumo.Garden.Core
 */

class Gdn_Email extends Gdn_Pluggable {

   /**
    * @var PHPMailer
    */
   private $_PhpMailer;

   /**
    * @var boolean
    */
   private $_IsToSet;

   /**
    * Constructor
    */
   function __construct() {
      $this->_PhpMailer = new PHPMailer();
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
      $this->_PhpMailer->AddBCC($RecipientEmail, $RecipientName);
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
      $this->_PhpMailer->AddCC($RecipientEmail, $RecipientName);
      return $this;
   }

   /**
    * Clears out all previously specified values for this object and restores
    * it to the state it was in when it was instantiated.
    *
    * @return Email
    */
   public function Clear() {
      $this->_PhpMailer->ClearAllRecipients();
      $this->_PhpMailer->Body = '';
      $this->From();
      $this->_IsToSet = False;
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
   public function From($SenderEmail = '', $SenderName = '') {
      if ($SenderEmail == '')
         $SenderEmail = Gdn::Config('Garden.Email.SupportAddress', '');

      if ($SenderName == '')
         $SenderName = Gdn::Config('Garden.Email.SupportName', '');

      $this->_PhpMailer->From = $SenderEmail;
      $this->_PhpMailer->FromName = $SenderName;
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
      if ($this->_PhpMailer->ContentType == 'text/html') {
         $this->_PhpMailer->MsgHTML($Message);
      } else {
         $this->_PhpMailer->Body = $Message;
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
      $this->_PhpMailer->IsHTML($MimeType === 'text/html');
      return $this;
   }

   /**
    * @todo add port settings
    */
   public function Send() {
      if (Gdn::Config('Garden.Email.UseSmtp')) {
         $this->_PhpMailer->IsSMTP();
         $SmtpHost = Gdn::Config('Garden.Email.SmtpHost', '');
         $SmtpPort = Gdn::Config('Garden.Email.SmtpPort', 25);
         if (strpos($SmtpHost, ':') !== FALSE) {
            list($SmtpHost, $SmtpPort) = explode(':', $SmtpHost);
         }

         $this->_PhpMailer->Host = $SmtpHost;
         $this->_PhpMailer->Port = $SmtpPort;
         $this->_PhpMailer->Username = $Username = Gdn::Config('Garden.Email.SmtpUser', '');
         $this->_PhpMailer->Password = $Password = Gdn::Config('Garden.Email.SmtpPassword', '');
         if(!empty($Username))
            $this->_PhpMailer->SMTPAuth = TRUE;

         
      } else {
         $this->_PhpMailer->IsMail();
      }

      if (!$this->_PhpMailer->Send()) {
         throw new Exception($this->_PhpMailer->ErrorInfo);
      }

      return true;
   }

   /**
    * Adds subject of the message to the email.
    * 
    * @param string $Subject The subject of the message.
    */
   public function Subject($Subject) {
      $this->_PhpMailer->Subject = $Subject;
   }

   /**
    * Adds to the "To" recipient collection.
    *
    * @param mixed $RecipientEmail An email (or array of emails) to add to the "To" recipient collection.
    * @param string $RecipientName The recipient name associated with $RecipientEmail. If $RecipientEmail is
    * an array of email addresses, this value will be ignored.
    */
   public function To($RecipientEmail, $RecipientName = '') {
      // Only allow one address in the "to" field. Append all extras to the "cc" field.
      if (!$this->_IsToSet) {
         $this->_PhpMailer->AddAddress($RecipientEmail, $RecipientName);
         $this->_IsToSet = True;
      }
      else {
         $this->Cc($RecipientEmail, $RecipientName);
      }

      return $this;
   }
}