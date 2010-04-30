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
 * Represents a Request to the application, typically from the browser but potentially generated internally, in a format
 * that can be accessed directly by the Dispatcher.
 *
 * @author Tim Gunter
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */
class Gdn_Request {

   private $_Params;
   private $_Environment;
   private $_Resolved;

   // Data types, in order of precedence, lowest meaning highest priority
   const INPUT_CUSTOM   = 1;
   const INPUT_GET      = 2;
   const INPUT_POST     = 3;
   const INPUT_SERVER   = 4;
   const INPUT_ENV      = 5;

   private function __construct() {
      $this->_Params       = array();
      $this->_Resolved     = array(
         'Request'            => '',
         'Output'             => 'default',
         'Outfile'            => 'default',
         'WebRoot'            => '',
         'Domain'             => ''
      );
      $this->_Environment = array();
      $this->_LoadEnvironment();
   }
   
   public static function CreateFromEnvironment() {
      $Request = new Gdn_Request();

      $Request->_AutoAttachRelevantData();
      
      return $Request->_Parse();
   }
   
   public static function CreateFromControllerMethod($Controller, $Method, $Params=NULL, $ImportEnvironmentData=FALSE) {
      $URI = $Controller."/".$Method;
      return self::CreateFromURI($URI, $Params, $ImportEnvironmentData);
   }
   
   public static function CreateFromURI($URI, $Params=NULL, $ImportEnvironmentData=FALSE) {
      $Request = new Gdn_Request();
      
      if ($ImportEnvironmentData !== FALSE)
         $this->_AutoAttachRelevantData();
      
      $Request->RequestURI($URI);
      
      if (!is_null($Params) && is_array($Params))
         $Request->AttachData(self::INPUT_CUSTOM, $Params);
         
      return $Request->_Parse();
   }
   
   private function _Environment($Aspect, $AspectValue=NULL) {
      if ($AspectValue !== NULL)
         $this->_Environment[$Aspect] = $AspectValue;
         
      if (array_key_exists($Aspect, $this->_Environment))
         return $this->_Environment[$Aspect];
         
      return NULL;
   }
   
   public function RequestURI($URI=NULL) {
      $DecodedURI = !is_null($URI) ? urldecode($URI) : $URI;
      return $this->_Environment('REQUEST_URI', $DecodedURI);
   }
   
   public function RequestHost($Host=NULL) {
      return $this->_Environment('REQUEST_HOST', $Host);
   }
   
   public function RequestScript($ScriptName=NULL) {
      return $this->_Environment('REQUEST_SCRIPT', $ScriptName);
   }
   
   public function RequestMethod($Method=NULL) {
      return $this->_Environment('REQUEST_METHOD', $Method);
   }
   
   public function RequestFolder($Folder=NULL) {
      return $this->_Environment('REQUEST_FOLDER', $Folder);
   }
   
   private function _Resolved($Aspect, $AspectValue=NULL) {
      if ($AspectValue !== NULL)
         $this->_Resolved[$Aspect] = $AspectValue;
         
      if (array_key_exists($Aspect, $this->_Resolved))
         return $this->_Resolved[$Aspect];
         
      return NULL;
   }
   
   /**
    * Gets/Sets the Output format, typically 'default' .
    * 
    * @param $Output optional value to set
    * @return string
    */
   public function Output($Output=NULL) {
      $Output = (!is_null($Output)) ? strtolower($Output) : $Output;
      return $this->_Resolved('Output', $Output);
   }
   
   /**
    * Gets/Sets the optional filename (ContentDisposition) of the output.
    * 
    * @param $OutFile optional value to set
    * @return string
    */
   public function Outfile($Outfile=NULL) {
      return $this->_Resolved('Outfile', $Outfile);
   }
   
   /**
    * Gets/Sets the final request to be sent to the dispatcher.
    * 
    * @param $Request optional value to set
    * @return string
    */
   public function Request($Request=NULL) {
      return $this->_Resolved('Request', $Request);
   }

   /**
    * Gets/Sets the path to the application's dispatcher.
    * 
    * @param $WebRoot optional value to set
    * @return string
    */
   public function WebRoot($WebRoot=NULL) {
      return $this->_Resolved('WebRoot', $WebRoot);
   }

   /**
    * Returns the domain from the current url. ie. "http://localhost/" in
    * "http://localhost/this/that/garden/index.php/controller/action/"
    *
    * @param $Domain optional value to set
    * @return string
    */
   public function Domain($Domain=NULL) {
      return $this->_Resolved('Domain', $Domain);
   }
   
   /**
    * Attach an array of parameters to the request.
    * 
    * @param int $ParamsType type of data to import. One of (self::INPUT_CUSTOM, self::INPUT_GET, self::INPUT_POST)
    * @param array $ParamsData data array to import
    * @return void
    */
   public function AttachData($ParamsType, $ParamsData) {
      $this->_Params[$ParamsType] = $ParamsData;
   }
   
   /**
    * Detach a dataset from the request
    * 
    * @param int $ParamsType type of data to detach. One of (self::INPUT_CUSTOM, self::INPUT_GET, self::INPUT_POST)
    * @return void
    */
   public function DetachData($ParamsType) {
      unset($this->_Params[$ParamsType]);
   }
   
   /**
    * Export a named dataset from the request
    *
    * @param int $ParamsType type of data to export. One of (self::INPUT_CUSTOM, self::INPUT_GET, self::INPUT_POST)
    * @return array
    */
   public function ExportData($ParamsType) {
      if (!isset($this->_Params[$ParamsType]))
         return array();
         
      return $this->_Params[$ParamsType];
   }
   
   /**
    *
    *
    *
    */
   private function _AutoAttachRelevantData() {
      // Web request. Attach GET and POST data.
      if ($this->_Environment['REQUEST_METHOD'] != 'CONSOLE')
      {
         $this->AttachData(self::INPUT_SERVER, $_SERVER);
         $this->AttachData(self::INPUT_GET, $_GET);
         $this->AttachData(self::INPUT_POST, $_POST);
      } else {
         $this->AttachData(self::INPUT_ENV, $_ENV);
      }
   }
   
   /**
    * Search the currently attached data arrays for the requested parameter and
    * return it. Return $Default of not found.
    *
    * @param string $ParameterName name of the parameter to retrieve
    * @param mixed $Default value to return if parameter not found
    * @return mixed
    */
   public function Parameter($ParameterName,$Default=FALSE) {
      for ($i=1; $i <= 5; $i++) {
         if (!array_key_exists($i, $this->_Params)) continue;
         if (array_key_exists($ParameterName, $this->_Params[$i]))
            return filter_var($this->_Params[$i][$ParameterName],FILTER_SANITIZE_STRING);
      }
      return $Default;
   }
   
   /**
    * Load the basics of the current environment
    *
    * @return array associative array of condensed environment variables
    */
   private function _LoadEnvironment() {
      
      $this->RequestHost(     isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
      $this->RequestMethod(   isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'CONSOLE');
      $this->RequestURI(      isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_ENV['REQUEST_URI']);
      $this->RequestScript(   isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : $_ENV['SCRIPT_NAME']);
      
      if (PHP_SAPI === 'cgi' && isset($_ENV['SCRIPT_URL']))
         $this->RequestScript($_ENV['SCRIPT_URL']);
   }
   
   /**
    * Parse the Environment data into the Resolved array.
    *
    * @return Gdn_Request
    */
   private function _Parse() {
   
      /**
       * Resolve Request Folder
       */
   
      // Get the folder from the script name.
      $Match = array();
      if (preg_match('/^(.*?)(\/index.php)?$/i', $this->RequestScript(), $Match))
         $this->RequestFolder($Match[1]);
      else
         $this->RequestFolder('');
         
      /**
       * Resolve final request to send to dispatcher
       */
      // Get the dispatch string from the URI
      if (preg_match('/^'.str_replace('/', '\/', $this->RequestFolder()).'(?:\/index.php)?\/?(.*?)\/?(?:[#?].*)?$/i', $this->RequestURI(), $Match))
         $this->Request($Match[1]);
      else
         $this->Request('');
      
      /**
       * Resolve optional output modifying file extensions (rss, json, etc)
       */
      
      $UrlParts = explode('/', $this->Request());
      $LastParam = array_pop(array_slice($UrlParts, -1, 1));
      $Match = array();
      if (preg_match('/^([^.]+)\.([^.]+)$/', $LastParam,$Match))
      {
         $this->Output($Match[2]);
         $this->Outfile($Match[0]);
         $this->Request(implode('/',array_slice($UrlParts, 0, -1)));
      }

      /**
       * Resolve WebRoot
       */
            
      // Attempt to get the webroot from the configuration array
      $WebRoot = Gdn::Config('Garden.WebRoot');
      
      // Attempt to get the webroot from the server
      if ($WebRoot === FALSE || $WebRoot == '') {
         $WebRoot = explode('/', ArrayValue('PHP_SELF', $_SERVER, ''));
         
         // Look for index.php to figure out where the web root is.
         $Key = array_search('index.php', $WebRoot);
         if ($Key !== FALSE) {
            $WebRoot = implode('/', array_slice($WebRoot, 0, $Key));
         } else {
            $WebRoot = '';
         }
      }
      
      if (is_string($WebRoot) && $WebRoot != '') {
         // Strip forward slashes from the beginning of webroot
         $ResolvedWebRoot = preg_replace('/(^\/+)/', '', $WebRoot);
      } else {
         $ResolvedWebRoot = '';
      }
      $this->WebRoot($ResolvedWebRoot);

      /**
       * Resolve Domain
       */

      // Attempt to get the domain from the configuration array
      $Domain = Gdn::Config('Garden.Domain', '');

      if ($Domain === FALSE || $Domain == '')
         $Domain = ArrayValue('HTTP_HOST', $_SERVER, '');
      
      if ($Domain != '' && $Domain !== FALSE) {
         if (substr($Domain, 0, 7) != 'http://')
            $Domain = 'http://'.$Domain;

         if (substr($Domain, -1, 1) != '/')
            $Domain = $Domain . '/';
      }
      $this->Domain($Domain);
      
      return $this;
   }
   
   public function Import($NewRequest) {
      // Import Environment
      $this->_Environment = $NewRequest->Export('Environment');
      // Import Params
      $this->_Params = $NewRequest->Export('Params');
      
      return $this->_Parse();
   }
   
   public function Export($Export) {
      switch ($Export) {
         case 'Environment':  return $this->_Environment;
         case 'Params':       return $this->_Params;
         case 'Resolved':     return $this->_Resolved;
         default:             return NULL;
      }
   }

}