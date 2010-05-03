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

// Todd: Some global changes.
//  - Make all private members protected.
//  - Put members in the following group order: constants, properties, constructor, methods.
//  - Put members in alphabetical order within their groups.
//  - Document everything in the /library/* folders right away.
//  - You seem to be using a lot of similar sounding verbs which is confusing (ex. Attach, Load, Import).
//  - I think we still need to give access to the superglobals. Even though we want to use a subset of them plugin developers might need them.
class Gdn_Request {

   // Todd: All of these property names are really unclear.
   private $_Params; // Todd: If this is a container for superglobals maybe it should be called $Suprtglobals.
   private $_Environment; // Todd: Can we get rid of this and just reference in the Server property?
   private $_Resolved; // Todd: This seems like it should be named Uri

   // Data types, in order of precedence, lowest meaning highest priority
   // Todd: I'd rather these be string constants with 'Custom', 'Get', 'Post', etc.
   //  - Set the priority in the accessor method.
   //  - Need other superglobals.
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

   // Todd: CreateFromSuperglobals would be more consistent with PHP.
   public static function CreateFromEnvironment() {
      $Request = new Gdn_Request();

      $Request->_AutoAttachRelevantData();

      return $Request->_Parse();
   }

   // Todd: Allow $Method = '' for specifying Index method?
   public static function CreateFromControllerMethod($Controller, $Method, $Params=NULL, $ImportEnvironmentData=FALSE) {
      $URI = $Controller."/".$Method;
      return self::CreateFromURI($URI, $Params, $ImportEnvironmentData);
   }

   // Todd: This should work with a URI being taken right from $_SERVER['REQUEST_URI']
   //  - Can we get rid of $ImportEnvironmentData parameter?
   //  - Always put a spaceon either side of operators. (ex. $Params = NULL)
   public static function CreateFromURI($URI, $Params=NULL, $ImportEnvironmentData=FALSE) {
      $Request = new Gdn_Request();

      if ($ImportEnvironmentData !== FALSE)
         $this->_AutoAttachRelevantData();

      $Request->RequestURI($URI);

      if (!is_null($Params) && is_array($Params)) // Todd: Pretty sure is_null isn't necessary
         $Request->AttachData(self::INPUT_CUSTOM, $Params);

      return $Request->_Parse();
   }

   // Todd: Rename $Aspect to $Key, $AspectValue to $Value
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

   // Todd: Think whether the following Request* methods are really necessary.
   //  - I'm pretty sure this data is only used to parse the $_SERVER variables.
   //  - We don't want to have method bloat.
   public function RequestHost($Host=NULL) {
      return $this->_Environment('REQUEST_HOST', $Host);
   }

   public function RequestScript($ScriptName=NULL) {
      $FixedScriptName = !is_null($ScriptName) ? trim($ScriptName, '/') : $ScriptName;
      return $this->_Environment('REQUEST_SCRIPT', $FixedScriptName);
   }

   public function RequestMethod($Method=NULL) {
      return $this->_Environment('REQUEST_METHOD', $Method);
   }

   public function RequestFolder($Folder=NULL) {
      return $this->_Environment('REQUEST_FOLDER', $Folder);
   }

   // Todd: Really unclear method. Use $Key, $Value.
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
   // Todd: Maybe call this OutputFormat()
   //  - Document the types of possible output or at least have a few examples.
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
   // Todd: Call this Filename.
   public function Outfile($Outfile=NULL) {
      return $this->_Resolved('Outfile', $Outfile);
   }

   /**
    * Gets/Sets the final request to be sent to the dispatcher.
    *
    * @param $Request optional value to set
    * @return string
    */
   // Call this Path()
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
   
   public function WebPath($WithDomain = FALSE, $TrailingSlash = TRUE) {
      $Parts = array();
      if ($WithDomain) 
         $Parts[] = $this->Domain();
         
      $Parts[] = $this->WebRoot();
      
      if (Gdn::Config('Garden.RewriteUrls') === FALSE)
         $Parts[] = $this->RequestScript().'/';
         
      $Path = implode('', $Parts);
      if (!$TrailingSlash)
         $Path = trim($Path, '/');
         
      return $Path;
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
   // Todd: Put an array_key_exists.
   //  - Method name
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
   // Todd: What is relevant data? Think about AttachSuperglobals().
   //  - Auto before a member tells me it's a boolean to turn on some automatic process.
   //  - Always make protected, not private.
   //  - Need the other superglobals.
   private function _AutoAttachRelevantData() {
      // Web request. Attach GET and POST data.
      if ($this->_Environment['REQUEST_METHOD'] != 'CONSOLE') {
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
   // Todd: Rename to GetValue to match our GetValue convenience function.
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
      if (preg_match('/^(.*?)(index.php)?$/i', $this->RequestScript(), $Match))
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
      if (preg_match('/^([^.]+)\.([^.]+)$/', $LastParam,$Match)) {
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
         $ResolvedWebRoot = trim($WebRoot,'/').'/';
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

         $Domain = trim($Domain, '/').'/';
      }
      $this->Domain($Domain);

      return $this;
   }

   // Todd: I'm noticing that you could have made this a CreateFromRequest() method.
   // - Or you could have made the other ones ImportFrom*() methods.
   // - Not sure if there is a good reason to have some as creators and some as setters.
   // - Possibly think of chaining setters? (ex. $Request = Gdn_Request::Create()->FromURI('/dashboard/settings');)
   public function Import($NewRequest) {
      // Import Environment
      $this->_Environment = $NewRequest->Export('Environment');
      // Import Params
      $this->_Params = $NewRequest->Export('Params');

      // Todd: This should only return this object if you envision a chaining scenario.
      //  - Since _Parse is protected chaining is impossible outside of the object.
      //  - Would also prefer all chaining methods to end with return $this; Too much chaining makes debugging a pain.
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