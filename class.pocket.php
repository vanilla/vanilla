<?php

if (!defined('APPLICATION'))
   exit();
/*
  Copyright 2008, 2009 Vanilla Forums Inc.
  This file is part of Garden.
  Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
  Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
  You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
  Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
 */

class Pocket {
   const ENABLED = 0;
   const DISABLED = 1;
   const TESTING = 2;

   const REPEAT_BEFORE = 'before';
   const REPEAT_AFTER = 'after';
   const REPEAT_ONCE = 'once';
   const REPEAT_EVERY = 'every';
   const REPEAT_INDEX = 'index';


   /** $var string The text to display in the pocket. */
   public $Body = '';

   /** @var int Whether or not the pocket is disabled. The pocket can also be in testing-mode. */
   public $Disabled = Pocket::ENABLED;

   /** @var string The format of the pocket. */
   public $Format = 'Raw';

   /** $var string The location on the page to display the pocket. */
   public $Location;

   /** $var string A descriptive name for the pocket to help keep it organized. */
   public $Name = '';

   /** $var string The name of the page to put the pocket on. */
   public $Page = '';

   /** $var string How the pocket repeats on the page. */
   public $RepeatType = Pocket::REPEAT_INDEX;

   /** $var array The repeat frequency. */
   public $RepeatFrequency = array(1);

   /** $var array The repeat frequency. */
   public $MobileOnly = FALSE;

   /** $var array The repeat frequency. */
   public $MobileNever = FALSE;

   /** $var bool Whether to disable the pocket for embedded comments. * */
   public $EmbeddedNever = FALSE;
   
   public $ShowInDashboard = FALSE;

   public function __construct($Location = '') {
      $this->Location = $Location;
   }

   /** Whether or not this pocket should be processed based on its state.
    *
    *  @Param array $Data Data specific to the request.
    *  @return bool
    */
   public function CanRender($Data) {
      if (!$this->ShowInDashboard && InSection('Dashboard')) {
         return FALSE;
      }
      
      $IsMobile = IsMobile();
      if (($this->MobileOnly && !$IsMobile) || ($this->MobileNever && $IsMobile)) {
         return FALSE;
      }

      if ($this->EmbeddedNever && strcasecmp(Gdn::Controller()->RequestMethod, 'embed') == 0)
         return FALSE;

      // Check to see if the pocket is enabled.
      switch ($this->Disabled) {
         case Pocket::DISABLED:
            return FALSE;
         case Pocket::TESTING:
            if (!Gdn::Session()->CheckPermission('Plugins.Pockets.Manage'))
               return FALSE;
            break;
      }

      // Check to see if the page matches.
      if ($this->Page && strcasecmp($this->Page, GetValue('PageName', $Data)) != 0)
         return FALSE;

      // Check to see if this is repeating.
      $Count = GetValue('Count', $Data);
      switch ($this->RepeatType) {
         case Pocket::REPEAT_AFTER:
            if (strcasecmp($Count, Pocket::REPEAT_AFTER) != 0)
               return FALSE;
            break;
         case Pocket::REPEAT_BEFORE:
            if (strcasecmp($Count, Pocket::REPEAT_BEFORE) != 0)
               return FALSE;
            break;
         case Pocket::REPEAT_ONCE:
            if ($Count != 1)
               return FALSE;
            break;
         case Pocket::REPEAT_EVERY:
            $Frequency = (array) $this->RepeatFrequency;
            $Every = GetValue(0, $Frequency, 1);
            if ($Every < 1)
               $Every = 1;
            $Begin = GetValue(1, $Frequency, 1);
            if (($Count % $Every) != ($Begin % $Every))
               return FALSE;
            break;
         case Pocket::REPEAT_INDEX:
            if (!in_array($Count, (array) $this->RepeatFrequency))
               return FALSE;
            break;
      }

      // If we've passed all of the tests then the pocket can be processed.
      return TRUE;
   }

   /** Load the pocket's data from an array.
    *
    *  @param array $Data
    */
   public function Load($Data) {
      $this->Body = $Data['Body'];
      $this->Disabled = $Data['Disabled'];
      $this->Format = $Data['Format'];
      $this->Location = $Data['Location'];
      $this->Name = $Data['Name'];
      $this->Page = $Data['Page'];
      $this->MobileOnly = $Data['MobileOnly'];
      $this->MobileNever = $Data['MobileNever'];
      $this->EmbeddedNever = GetValue('EmbeddedNever', $Data);
      $this->ShowInDashboard = GetValue('ShowInDashboard', $Data);

      // parse the frequency.
      $Repeat = $Data['Repeat'];
      list($this->RepeatType, $this->RepeatFrequency) = Pocket::ParseRepeat($Repeat);
   }

   public static $NameTranslations = array('conversations' => 'inbox', 'messages' => 'inbox', 'categories' => 'discussions', 'discussion' => 'comments');

   public static function PageName($NameOrObject = NULL) {
      if (is_object($NameOrObject))
         $Name = GetValue('PageName', $NameOrObject, GetValue('ControllerName', $NameOrObject, get_class($NameOrObject)));
      else
         $Name = $NameOrObject;

      $Name = strtolower($Name);
      if (StringEndsWith($Name, 'controller', FALSE))
         $Name = substr($Name, 0, -strlen('controller'));

      if (array_key_exists($Name, self::$NameTranslations))
         $Name = self::$NameTranslations[$Name];
      return $Name;
   }

   public static function ParseRepeat($Repeat) {
      if (StringBeginsWith($Repeat, Pocket::REPEAT_EVERY)) {
         $RepeatType = Pocket::REPEAT_EVERY;
         $Frequency = substr($Repeat, strlen(Pocket::REPEAT_EVERY));
      } elseif (StringBeginsWith($Repeat, Pocket::REPEAT_INDEX)) {
         $RepeatType = Pocket::REPEAT_INDEX;
         $Frequency = substr($Repeat, strlen(Pocket::REPEAT_INDEX));
      } elseif (StringBeginsWith($Repeat, Pocket::REPEAT_ONCE)) {
         $RepeatType = Pocket::REPEAT_ONCE;
      } elseif (StringBeginsWith($Repeat, Pocket::REPEAT_BEFORE)) {
         $RepeatType = Pocket::REPEAT_BEFORE;
      } elseif (StringBeginsWith($Repeat, Pocket::REPEAT_AFTER)) {
         $RepeatType = Pocket::REPEAT_AFTER;
      }

      if (isset($Frequency)) {
         $Frequency = explode(',', $Frequency);
         $Frequency = array_map('trim', $Frequency);
      } else {
         $Frequency = array();
      }

      return array($RepeatType, $Frequency);
   }

   /** Render the pocket to the page.
    *
    *  @param array $Data additional data for the pocket.
    */
   public function Render($Data = NULL) {
      echo $this->ToString($Data);
   }

   /** Set the repeat of the pocket.
    *
    *  @param string $Type The repeat type, contained in the various Pocket::REPEAT_* constants.
    *   - every: Repeats every x times. If $Frequency is an array then it will be interpretted as array($Frequency, $Begin).
    *   - indexes: Renders only at the given indexes, starting at 1.
    *  @param int|array $Frequency The frequency of the repeating, see the $Type parameter for how this works.
    */
   public function Repeat($Type, $Frequency) {
      $this->RepeatType = $Type;
      $this->RepeatFrequency = $Frequency;
   }

   public function ToString($Data = NULL) {
      static $Plugin;
      if (!isset($Plugin))
         $Plugin = Gdn::PluginManager()->GetPluginInstance('PocketsPlugin', Gdn_PluginManager::ACCESS_CLASSNAME);
      $Plugin->EventArguments['Pocket'] = $this;
      $Plugin->FireEvent('ToString');
      
      if (strcasecmp($this->Format, 'raw') == 0)
         return $this->Body;
      else
         return Gdn_Format::To($this->Body, $this->Format);
   }
   
   public static function Touch($Name, $Value) {
      $Model = new Gdn_Model('Pocket');
      $Pockets = $Model->GetWhere(array('Name' => $Name))->ResultArray();
         
      if (empty($Pockets)) {
         $Pocket = array(
            'Name' => $Name,
            'Location' => 'Content',
            'Sort' => 0,
            'Repeat' => Pocket::REPEAT_BEFORE,
            'Body' => $Value,
            'Format' => 'Raw',
            'Disabled' => Pocket::DISABLED,
            'MobileOnly' => 0,
            'MobileNever' => 0,
            'EmbeddedNever' => 0,
            'ShowInDashboard' => 0
            );
         $Model->Save($Pocket);
      }
   }

}