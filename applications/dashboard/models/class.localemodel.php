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
 * Used to manage adding/removing different locale files.
 */
class LocaleModel {

   protected $_AvailableLocalePacks = NULL;

   public function AvailableLocalePacks() {
      if ($this->_AvailableLocalePacks === NULL) {
         $LocaleInfoPaths = SafeGlob(PATH_ROOT."/locales/*/definitions.php");
         $AvailableLocales = array();
         foreach ($LocaleInfoPaths as $InfoPath) {
            $LocaleInfo = Gdn::PluginManager()->ScanPluginFile($InfoPath, 'LocaleInfo');
            $AvailableLocales = array_merge($AvailableLocales, $LocaleInfo);
         }
         $this->_AvailableLocalePacks = $AvailableLocales;
      }
      return $this->_AvailableLocalePacks;
   }

   public function EnabledLocalePacks() {
      $Result = (array)C('EnabledLocales', array());
      return $Result;
   }

   /**
    * Enable a locale pack without installing it to the config or mappings.
    *
    * @param string $LocaleKey The key of the folder.
    */
   public function TestLocale($LocaleKey) {
      $Available = $this->AvailableLocalePacks();
      if (!isset($Available[$LocaleKey]))
         throw NotFoundException('Locale');

      // Grab all of the definition files from the locale.
      $Paths = SafeGlob(PATH_ROOT."/locales/$LocaleKey/*.php");
      foreach ($Paths as $Path) {
         Gdn::Locale()->Load($Path);
      }
   }
}