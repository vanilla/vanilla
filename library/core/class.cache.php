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
 *
 * @author Tim Gunter
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */
 
abstract class Gdn_Cache {
   
   /**
   * List of cache containers
   * @var array
   */
   protected $Containers;
   
   /**
   * List of features this cache system supports
   * @var array
   */
   protected $Features;
   
   // Allows items to be internally compressed/decompressed
   const FEATURE_COMPRESS     = 'f_compress';
   // Allows items to autoexpire
   const FEATURE_EXPIRY       = 'f_expiry';
   // Allows set/get timeouts
   const FEATURE_TIMEOUT      = 'f_timeout';
   
   /**
   * Location - SERVER:IP, Filepath, etc
   */
   const CONTAINER_LOCATION   = 'c_location';
   
   /**
   * Persistent - IsPersistent Flag
   */
   const CONTAINER_PERSISTENT = 'c_persistent';
   
   /**
   * Weight - Allows for differently weighted storage locations
   */
   const CONTAINER_WEIGHT     = 'c_weight';
   
   /**
   * Persistent - Retry Inverval Flag
   */
   const CONTAINER_RETRYINT = 'c_retryint';
   
   /**
   * Timeout - How long to wait while connecting to this container
   */
   const CONTAINER_TIMEOUT    = 'c_timeout';
   
   /**
   * Online - If this container is ready for requests
   */
   const CONTAINER_ONLINE     = 'c_online';
   
   /**
   * Callback - Method to call if the location fails
   */
   const CONTAINER_CALLBACK   = 'c_callback';
   
   /**
   * Cachefile - ??
   */
   const CONTAINER_CACHEFILE  = 'c_cachefile';
   
   const CACHEOP_FAILURE = FALSE;
   const CACHEOP_SUCCESS = TRUE;

   public function __construct() {
      $this->Containers = array();
      $this->Features = array();
   }
   
   /**
   * Determines the currently installed cache solution and returns a fresh instance of its object
   * 
   * @return Gdn_Cache
   */
   public static function Initialize($ForceEnable = FALSE, $ForceMethod = FALSE) {
      $AllowCaching = C('Cache.Enabled');
      $AllowCaching |= $ForceEnable;
      
      $ActiveCache = C('Cache.Method', FALSE);
      Gdn::PluginManager()->EventArguments['ActiveCache'] = &$ActiveCache;
      Gdn::PluginManager()->FireEvent('BeforeActiveCache');
      
      if ($ForceMethod !== FALSE) $ActiveCache = $ForceMethod;
      $ActiveCacheClass = 'Gdn_'.ucfirst($ActiveCache);
      
      if (!$AllowCaching || !$ActiveCache || !class_exists($ActiveCacheClass))
         $CacheObject = new Gdn_Dirtycache();
      else
         $CacheObject = new $ActiveCacheClass();
      
      if (method_exists($CacheObject,'Autorun'))
         $CacheObject->Autorun();
         
      return $CacheObject;
   }
   
   /**
   * put your comment there...
   * 
   * @param string $Key
   * @param mixed $Value
   * @param array $Options
   * @return boolean TRUE on success or FALSE on failure.
   */
   abstract public function Add($Key, $Value, $Options = array());
   
   /**
   * put your comment there...
   * 
   * @param string $Key
   * @param mixed $Value
   * @param array $Options
   * @return boolean TRUE on success or FALSE on failure.
   */
   abstract public function Store($Key, $Value, $Options = array());
   
   /**
   * put your comment there...
   *
   * @param string $Key
   * @return array augmented container struct for existing key or FALSE if not found.
   */
   abstract public function Exists($Key);
   
   /**
   * put your comment there...
   * 
   * @param string $Key
   * @param array $Options
   * @return mixed key value or FALSE on failure.
   */
   abstract public function Get($Key, $Options = array());
   
   /**
   * put your comment there...
   * 
   * @param string $Key
   * @param array $Options
   * @return boolean TRUE on success or FALSE on failure.
   */
   abstract public function Remove($Key, $Options = array());
   
   /**
   * put your comment there...
   * 
   * @param string $Key
   * @param mixed $Value
   * @param array $Options
   * @return boolean TRUE on success or FALSE on failure.
   */
   abstract public function Replace($Key, $Value, $Options = array());
   
   /**
   * put your comment there...
   * 
   * @param string $Key
   * @param mixed $Amount
   * @return integer new value or FALSE on failure.
   */
   abstract public function Increment($Key, $Amount = 1, $Options = array());
   
   /**
   * put your comment there...
   * 
   * @param string $Key
   * @param mixed $Amount
   * @return integer new value or FALSE on failure.
   */
   abstract public function Decrement($Key, $Amount = 1, $Options = array());
   
   /**
   * put your comment there...
   * 
   * @param array $Options
   * @return boolean TRUE on success or FALSE on failure.
   */
   abstract public function AddContainer($Options);
   
   /**
   * put your comment there...
   * 
   * @param int $Feature feature constant
   */
   public function RegisterFeature($Feature, $Meta = TRUE) {
      $this->Features[$Feature] = $Meta;
   }
   
   /**
   * put your comment there...
   * 
   * @param int $Feature feature contant
   */
   public function UnregisterFeature($Feature) {
      if (isset($this->Features[$Features]))
         unset($this->Features[$Feature]);
   }
   
   /**
   * put your comment there...
   * 
   * @param int $Feature feature constant
   */
   public function HasFeature($Feature) {
      return isset($this->Features[$Feature]) ? $this->Features[$Feature] : Gdn_Cache::CACHEOP_FAILURE;
   }
   
   protected function Failure($Message) {
      if (defined("DEBUG") && DEBUG)
         throw new Exception($Message);
      else
         return Gdn_Cache::CACHEOP_FAILURE;
   }
}