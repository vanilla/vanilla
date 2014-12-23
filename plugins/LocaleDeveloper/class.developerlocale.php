<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class DeveloperLocale extends Gdn_Locale {
   public $_CapturedDefinitions = array();

   /**
    * Gets all of the definitions in the current locale.
    *
    * return array
    */
   public function AllDefinitions() {
      $Result = array_merge($this->_Definition, $this->_CapturedDefinitions);
      return $Result;
   }

   public function CapturedDefinitions() {
      return $this->_CapturedDefinitions;
   }
   
   public static function GuessPrefix() {
      $Trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
      
      foreach ($Trace as $i => $Row) {
         if ($Row['function'] == 'T') {
            if ($Trace[$i + 1]['function'] == 'Plural')
               return self::PrefixFromPath($Trace[$i + 1]['file']);
            else
               return self::PrefixFromPath($Row['file']);
         }
         if ($Row['function'] == 'Translate') {
            if (!in_array(basename($Row['file']), array('functions.general.php', 'class.gdn.php'))) {
               return self::PrefixFromPath($Row['file']);
            }
         }
      }
      
      return FALSE;
   }
   
   public static function PrefixFromPath($Path) {
      $Result = '';
      
      if (preg_match('`/plugins/([^/]+)`i', $Path, $Matches)) {
         $Plugin = strtolower($Matches[1]);
         
         if (in_array($Plugin, array('buttonbar', 'fileupload', 'facebook', 'twitter', 'quotes', 'signatures', 'splitmerge', 'tagging', 'nbbc'))) {
            $Result .= 'core';
         } else
            $Result .= $Plugin.'_plugin';
      } elseif (preg_match('`/library/`i', $Path, $Matches)) {
         $Result .= 'core';
      } elseif (preg_match('`/applications/([^/]+)`i', $Path, $Matches)) {
         $App = strtolower($Matches[1]);
         
         if (in_array($App, array('conversations', 'vanilla', 'dashboard'))) {
            // This is a core app.
            $Result .= 'core';
         } else {
            $Result .= $App.'_application';
         }
      } elseif (preg_match('`/themes/([^/]+)`i', $Path, $Matches)) {
         $Result = FALSE;
      }
      return $Result;
   }

   public function Translate($Code, $Default = FALSE) {
      $Result = parent::Translate($Code, $Default);
      
      if (!$Code || substr($Code, 0, 1) == '@')
         return $Result;
         
      $Prefix = self::GuessPrefix();
      
      if (!$Prefix) {
         return $Result;
      }
      
      if ($Prefix == 'unknown') {
         decho($Code);
         decho(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
         die();
      }
      
      if (Gdn_Theme::InSection('Dashboard'))
         $Prefix = 'dash_'.$Prefix;
      else
         $Prefix = 'site_'.$Prefix;
      
      $this->_CapturedDefinitions[$Prefix][$Code] = $Result;

      return $Result;
   }
}
