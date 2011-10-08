<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2011 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * An interface that all payment plugins must follow.
 *
 * @package Garden
 * @since 2.1.0
 */
interface Gdn_IPayment extends Gdn_IPlugin {
   /**
    * Initiate a payment with the remote processor.
    *
    * @param array $Data Information required for the transaction.
    *    Keys: Amount (int), UserID (int), Token (string), Description (string), Card (array).
    * @return bool Whether payment was successful.
    */
   public function SendPayment($Data);
}