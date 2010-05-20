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
 * A wrapper for the Exception class so that methods can throw a specific application as a means of validation or user error, rather than a critical exception.
 */
class Gdn_UserException extends Exception {
	/** Constructs the Gdn_ApplicationException.
	 *
	 * @param string $Message A user readable message for the exception.
	 * @param Exception $Previous The previous exception used for exception chaining.
	 */
   public function __constuct($Message, $Previous = NULL) {
		parent::__construct($Message, 0, $Previous);
	}
}