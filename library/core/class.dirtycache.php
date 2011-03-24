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
 * This is a cache implementation that caches nothing and always reports cache misses.
 * 
 * @author Tim Gunter
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */
 
class Gdn_Dirtycache extends Gdn_Cache {
   
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
      return Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Exists($Key) {
      return Gdn_Cache::CACHEOP_FAILURE;
   }
   
   public function Get($Key, $Options = array()) {
      return Gdn_Cache::CACHEOP_FAILURE;
   }
   
   public function Remove($Key, $Options = array()) {
      return Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Replace($Key, $Value, $Options = array()) {
      return Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Increment($Key, $Amount = 1, $Options = array()) {
      return Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Decrement($Key, $Amount = 1, $Options = array()) {
      return Gdn_Cache::CACHEOP_SUCCESS;
   }
}