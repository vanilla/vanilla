<?php if (!defined('APPLICATION')) exit();

/**
 * Cache Layer: Memcache
 * 
 * A cache layer that stores its items in memcached and uses libmemcache to
 * interact with the daemons.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */
 
class Gdn_Memcache extends Gdn_Cache {

   const OPT_MOD_SPLIT = 65000;
   const OPT_PASSTHRU_CONTAINER = 'passthru';
   const O_CREATE = 1;
   
   private $Memcache;

   // Placeholder
   protected $WeightedContainers;
   
   public function __construct() {
      parent::__construct();
      $this->CacheType = Gdn_Cache::CACHE_TYPE_MEMORY;
      
      $this->Memcache = new Memcache;
      
      $this->RegisterFeature(Gdn_Cache::FEATURE_COMPRESS, MEMCACHE_COMPRESSED);
      $this->RegisterFeature(Gdn_Cache::FEATURE_EXPIRY);
      $this->RegisterFeature(Gdn_Cache::FEATURE_TIMEOUT);
      $this->RegisterFeature(Gdn_Cache::FEATURE_NOPREFIX);
      $this->RegisterFeature(Gdn_Cache::FEATURE_FORCEPREFIX);
      
      $this->StoreDefaults = array(
         Gdn_Cache::FEATURE_COMPRESS      => FALSE,
         Gdn_Cache::FEATURE_TIMEOUT       => FALSE,
         Gdn_Cache::FEATURE_EXPIRY        => FALSE,
         Gdn_Cache::FEATURE_NOPREFIX      => FALSE,
         Gdn_Cache::FEATURE_FORCEPREFIX   => NULL
      );
   }
   
   /**
   * Reads in known/config servers and adds them to the instance.
   * 
   * This method is called when the cache object is invoked by the framework 
   * automatically, and needs to configure itself from the values in the global
   * config file.
   */
   public function Autorun() {
      $Servers = Gdn_Cache::ActiveStore('memcache');
      if (!is_array($Servers)) 
         $Servers = explode(',',$Servers);
         
      $Keys = array(
         Gdn_Cache::CONTAINER_LOCATION,
         Gdn_Cache::CONTAINER_PERSISTENT,
         Gdn_Cache::CONTAINER_WEIGHT,
         Gdn_Cache::CONTAINER_TIMEOUT,
         Gdn_Cache::CONTAINER_ONLINE,
         Gdn_Cache::CONTAINER_CALLBACK
      );
      foreach ($Servers as $CacheServer) {
         $CacheServer = explode(' ',$CacheServer);
         $CacheServer = array_pad($CacheServer,count($Keys),NULL);
         $CacheServer = array_combine($Keys,$CacheServer);
         
         foreach ($Keys as $KeyName) {
            $Value = GetValue($KeyName, $CacheServer, NULL);
            if (is_null($Value))
               unset($CacheServer[$KeyName]);
         }
         
         $this->AddContainer($CacheServer);
      }
   }
   
   /**
   const CONTAINER_LOCATION = 1;
   const CONTAINER_PERSISTENT = 2;
   const CONTAINER_WEIGHT = 3;
   const CONTAINER_TIMEOUT = 4;
   const CONTAINER_ONLINE = 5;
   const CONTAINER_CALLBACK = 6;
   */
   public function AddContainer($Options) {
      
      $Required = array(
         Gdn_Cache::CONTAINER_LOCATION
      );
      
      $KeyedRequirements = array_fill_keys($Required, 1);
      if (sizeof(array_intersect_key($Options, $KeyedRequirements)) != sizeof($Required)) {
         $Missing = implode(", ",array_keys(array_diff_key($KeyedRequirements,$Options)));
         return $this->Failure("Required parameters not supplied. Missing: {$Missing}");
      }
      
      $CacheLocation = GetValue(Gdn_Cache::CONTAINER_LOCATION,$Options);
      
      // Merge the options array with our local defaults
      $Defaults = array(
         Gdn_Cache::CONTAINER_PERSISTENT  => TRUE,
         Gdn_Cache::CONTAINER_WEIGHT      => 1,
         Gdn_Cache::CONTAINER_TIMEOUT     => 1,
         Gdn_Cache::CONTAINER_RETRYINT    => 15,
         Gdn_Cache::CONTAINER_ONLINE      => TRUE,
         Gdn_Cache::CONTAINER_CALLBACK    => NULL
      );
      
      $FinalContainer = array_merge($Defaults, $Options);
      $this->Containers[$CacheLocation] = $FinalContainer;
      $PathInfo = explode(':',$CacheLocation);
      
      $this->Memcache->addServer(
         GetValue(0,$PathInfo),
         GetValue(1,$PathInfo,11211),
         GetValue(Gdn_Cache::CONTAINER_PERSISTENT,$FinalContainer),
         GetValue(Gdn_Cache::CONTAINER_WEIGHT,$FinalContainer),
         GetValue(Gdn_Cache::CONTAINER_TIMEOUT,$FinalContainer),
         GetValue(Gdn_Cache::CONTAINER_RETRYINT,$FinalContainer),
         GetValue(Gdn_Cache::CONTAINER_ONLINE,$FinalContainer),
         GetValue(Gdn_Cache::CONTAINER_CALLBACK,$FinalContainer)
      );
      
      return Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Add($Key, $Value, $Options = array()) {
      $FinalOptions = array_merge($this->StoreDefaults, $Options);
      
      $Flags = 0;
      $Compress = 0;
      if (GetValue(Gdn_Cache::FEATURE_COMPRESS,$FinalOptions))
         $Compress = (int)$this->HasFeature(Gdn_Cache::FEATURE_COMPRESS);
         
      $Flags |= $Compress;
      
      $Expiry = GetValue(Gdn_Cache::FEATURE_EXPIRY,$FinalOptions,0);
      
      $RealKey = $this->MakeKey($Key, $FinalOptions);
      $Stored = $this->Memcache->add($RealKey, $Value, $Flags, $Expiry);
      return ($Stored) ? Gdn_Cache::CACHEOP_SUCCESS : Gdn_Cache::CACHEOP_FAILURE;
   }
   
   public function Store($Key, $Value, $Options = array()) {
      $FinalOptions = array_merge($this->StoreDefaults, $Options);
      
      $Flags = 0;
      $Compress = 0;
      if (GetValue(Gdn_Cache::FEATURE_COMPRESS,$FinalOptions))
         $Compress = (int)$this->HasFeature(Gdn_Cache::FEATURE_COMPRESS);
      
      $Flags |= $Compress;
      
      $Expiry = (int)GetValue(Gdn_Cache::FEATURE_EXPIRY,$FinalOptions,0);
      
      $RealKey = $this->MakeKey($Key, $FinalOptions);
      $Stored = $this->Memcache->set($RealKey, $Value, $Flags, $Expiry);
      return ($Stored) ? Gdn_Cache::CACHEOP_SUCCESS : Gdn_Cache::CACHEOP_FAILURE;
   }
   
   public function Get($Key, $Options = array()) {
      $FinalOptions = array_merge($this->StoreDefaults, $Options);
      
      $Flags = 0;
      $Compress = 0;
      if (GetValue(Gdn_Cache::FEATURE_COMPRESS,$FinalOptions))
         $Compress = (int)$this->HasFeature(Gdn_Cache::FEATURE_COMPRESS);
         
      $Flags |= $Compress;
      
      $RealKey = $this->MakeKey($Key, $FinalOptions);
      $Data = $this->Memcache->get($RealKey, $Flags);
      return ($Data === FALSE) ? $this->Fallback($Key,$Options) : $Data;
   }
   
   public function Exists($Key, $Options = array()) {
      return ($this->Get($Key, $Options) === Gdn_Cache::CACHEOP_FAILURE) ? Gdn_Cache::CACHEOP_FAILURE : Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Remove($Key, $Options = array()) {
      $FinalOptions = array_merge($this->StoreDefaults, $Options);
      
      $RealKey = $this->MakeKey($Key, $FinalOptions);
      $Deleted = $this->Memcache->delete($RealKey);
      return ($Deleted) ? Gdn_Cache::CACHEOP_SUCCESS : Gdn_Cache::CACHEOP_FAILURE;
   }
   
   public function Replace($Key, $Value, $Options = array()) {
      return $this->Store($Key, $Value, $Options);
   }
   
   public function Increment($Key, $Amount = 1, $Options = array()) {
      $FinalOptions = array_merge($this->StoreDefaults, $Options);
      
      $RealKey = $this->MakeKey($Key, $FinalOptions);
      $Incremented = $this->Memcache->increment($RealKey, $Amount);
      return ($Incremented !== FALSE) ? $Incremented : Gdn_Cache::CACHEOP_FAILURE;
   }
   
   public function Decrement($Key, $Amount = 1, $Options = array()) {
      $FinalOptions = array_merge($this->StoreDefaults, $Options);
      
      $RealKey = $this->MakeKey($Key, $FinalOptions);
      return $this->Memcache->decrement($RealKey, $Amount);
   }
   
   public function Flush() {
      return $this->Memcache->flush();
   }
}
