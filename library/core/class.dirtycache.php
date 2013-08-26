<?php if (!defined('APPLICATION')) exit();

/**
 * Cache Layer: Dirty
 * 
 * This is a cache implementation that caches nothing and always reports 
 * cache misses.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */
 
class Gdn_Dirtycache extends Gdn_Cache {
   protected $Cache = array();
   
   public function __construct() {
      parent::__construct();
      $this->CacheType = Gdn_Cache::CACHE_TYPE_NULL;
   }
   
   public function AddContainer($Options) {
      return Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Add($Key, $Value, $Options = array()) {
      return $this->Store($Key, $Value, $Options);
   }
   
   public function Store($Key, $Value, $Options = array()) {
      $this->Cache[$Key] = $Value;
      return Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Exists($Key) {
      return Gdn_Cache::CACHEOP_FAILURE;
   }
   
   public function Get($Key, $Options = array()) {
      if (is_array($Key)) {
         $Result = array();
         foreach ($Key as $k) {
            if (isset($this->Cache[$k]))
               $Result[$k] = $this->Cache[$k];
         }
         return $Result;
      } else {
         if (isset($this->Cache[$Key]))
            return $this->Cache[$Key];
         else
            return Gdn_Cache::CACHEOP_FAILURE;
      }
   }
   
   public function Remove($Key, $Options = array()) {
      unset($this->Cache[$Key]);
      
      return Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Replace($Key, $Value, $Options = array()) {
      $this->Cache[$Key] = $Value;
      return Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Increment($Key, $Amount = 1, $Options = array()) {
      return Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Decrement($Key, $Amount = 1, $Options = array()) {
      return Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Flush() {
      return TRUE;
   }
}