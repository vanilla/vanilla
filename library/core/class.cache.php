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
   
   const FEATURE_COMPRESS     = 'f_compress';
   const FEATURE_EXPIRY       = 'f_expiry';
   const FEATURE_TIMEOUT      = 'f_timeout';
   
   const CONTAINER_LOCATION   = 'c_location';
   const CONTAINER_PERSISTENT = 'c_persistent';
   const CONTAINER_WEIGHT     = 'c_weight';
   const CONTAINER_TIMEOUT    = 'c_timeout';
   const CONTAINER_ONLINE     = 'c_online';
   const CONTAINER_CALLBACK   = 'c_callback';
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
   public static function Initialize() {
      
      $AllowCaching = C('Cache.Enabled');
      $ActiveCache = C('Cache.Method', FALSE);
      $ActiveCacheClass = 'Gdn_'.ucfirst($ActiveCache);
      
      if (!$AllowCaching || !$ActiveCache || !class_exists($ActiveCacheClass))
         return new Gdn_Dirtycache();
         
      return new $ActiveCacheClass();
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