<?php if (!defined('APPLICATION')) exit();

/**
 * Cache Layer: Memcached
 * 
 * A cache layer that stores its items in memcached and uses libmemcached to
 * interact with the daemons.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */
 
class Gdn_Memcached extends Gdn_Cache {

   const OPT_MOD_SPLIT = 65000;
   const OPT_PASSTHRU_CONTAINER = 'passthru';
   const O_CREATE = 1;
   
   private $Memcache;

   // Placeholder
   protected $WeightedContainers;
   
   public function __construct() {
      parent::__construct();
      $this->CacheType = Gdn_Cache::CACHE_TYPE_MEMORY;
      
      // Allow persistent connections
      
      /**
       * EXTREMELY IMPORTANT NOTE!!
       * There is a bug in Libmemcached which causes persistent connections not 
       * to be recycled, thereby initiating a spiral of memory loss. DO NOT USE
       * THIS UNLESS YOU ARE QUITE CERTAIN THIS IS SOLVED!
       */
      
      $Persist = $this->Config(Gdn_Cache::CONTAINER_PERSISTENT);
      if ($this->Config(Gdn_Cache::CONTAINER_PERSISTENT)) {
         $PoolSize = $this->Config(Gdn_Cache::CONTAINER_POOLSIZE, 10);
         $PoolKeyFormat = $this->Config(Gdn_Cache::CONTAINER_POOLKEY, "cachekey-%d");
         $PoolIndex = mt_rand(1, $PoolSize);
         $PoolKey = sprintf($PoolKeyFormat, $PoolIndex);
         $this->Memcache = new Memcached($PoolKey);
      } else {
         $this->Memcache = new Memcached;
      }
      
      $this->RegisterFeature(Gdn_Cache::FEATURE_COMPRESS, Memcached::OPT_COMPRESSION);
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
      
      foreach ($this->Option(NULL, array()) as $Option => $OptValue)
         $this->Memcache->setOption($Option, $OptValue);
      
   }
   
   /**
   * Reads in known/config servers and adds them to the instance.
   * 
   * This method is called when the cache object is invoked by the framework 
   * automatically, and needs to configure itself from the values in the global
   * config file.
   */
   public function Autorun() {
      $Servers = Gdn_Cache::ActiveStore('memcached');
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
         Gdn_Cache::CONTAINER_WEIGHT      => 1
      );
      
      $FinalContainer = array_merge($Defaults, $Options);
      $this->Containers[$CacheLocation] = $FinalContainer;
      $PathInfo = explode(':',$CacheLocation);
      
      $ServerHostname = GetValue(0, $PathInfo);
      $ServerPort = GetValue(1, $PathInfo, 11211);
      
      $AddServerResult = $this->Memcache->addServer(
         $ServerHostname,
         $ServerPort,
         GetValue(Gdn_Cache::CONTAINER_WEIGHT, $FinalContainer, 1)
      );
      
      if (!$AddServerResult) {
         $Callback = GetValue(Gdn_Cache::CONTAINER_CALLBACK, $FinalContainer, NULL);
         if (!is_null($Callback))
            call_user_func($Callback, $ServerHostname, $ServerPort);
         
         return Gdn_Cache::CACHEOP_FAILURE;
      }
      
      return Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Add($key, $value, $options = array()) {
      $finalOptions = array_merge($this->StoreDefaults, $options);
      
      $expiry = GetValue(Gdn_Cache::FEATURE_EXPIRY,$finalOptions,0);
      
      $realKey = $this->MakeKey($key, $finalOptions);
      $stored = $this->Memcache->add($realKey, $value, $expiry);
      if ($stored) {
         Gdn_Cache::LocalSet($realKey, $value);
         return Gdn_Cache::CACHEOP_SUCCESS;
      }
      return Gdn_Cache::CACHEOP_FAILURE;
   }
   
   public function Store($key, $value, $options = array()) {
      $finalOptions = array_merge($this->StoreDefaults, $options);
      
      $expiry = (int)GetValue(Gdn_Cache::FEATURE_EXPIRY,$finalOptions,0);
      
      $realKey = $this->MakeKey($key, $finalOptions);
      $stored = $this->Memcache->set($realKey, $value, $expiry);
      if ($stored) {
         Gdn_Cache::LocalSet($realKey, $value);
         return Gdn_Cache::CACHEOP_SUCCESS;
      }
      return Gdn_Cache::CACHEOP_FAILURE;
   }
   
   public function Get($key, $options = array()) {
      $startTime = microtime(TRUE);
      
      $finalOptions = array_merge($this->StoreDefaults, $options);
      
      $localData = array();
      $realKeys = array();
      if (is_array($key)) {
         $multi = true;
         foreach ($key as $multiKey) {
            $realKey = $this->MakeKey($multiKey, $finalOptions);
            
            // Skip this key if we already have it
            $local = Gdn_Cache::LocalGet($realKey);
            if ($local !== Gdn_Cache::CACHEOP_FAILURE) {
               $localData[$realKey] = $local;
               continue;
            }
            $realKeys[] = $realKey;
         }
      } else {
         $multi = false;
         $realKey = $this->MakeKey($key, $finalOptions);
         
         // Completely short circuit if we already have everything
         $local = Gdn_Cache::LocalGet($realKey);
         if ($local !== false)
            return $local;
         
         $realKeys = array($realKey);
      }
      
      $data = array(); $hitCache = false;
      if ($numKeys = sizeof($realKeys)) {
         $hitCache = true;
         if ($numKeys > 1) {
            $data = $this->Memcache->getMulti($realKeys);
         } else {
            $data = $this->Memcache->get($realKey);
            $data = array($realKey => $data);
         }

         $storeData = array();
         foreach ($data as $localKey => $localValue)
            if ($localValue !== false)
               $storeData[$localKey] = $localValue;
         $data = $storeData;
         unset($storeData);
         
         // Cache in process memory
         if (sizeof($data))
            Gdn_Cache::LocalSet($data);
      }
      
      // Merge in local data
      $data = array_merge($data, $localData);
      
      // Track debug stats
      $elapsedTime = microtime(true) - $startTime;
      if (Gdn_Cache::$trace) {
         Gdn_Cache::$trackTime += $elapsedTime;
         Gdn_Cache::$trackGets++;
         
         $keyTime = sizeof($realKeys) ? $elapsedTime / sizeof($realKeys) : $elapsedTime;
         foreach ($realKeys as $realKey) {
            TouchValue($realKey, Gdn_Cache::$trackGet, array(
               'hits'      => 0,
               'time'      => 0,
               'keysize'   => null,
               'transfer'  => 0,
               'wasted'    => 0
            ));
            
            $keyData = GetValue($realKey, $data, false);
            Gdn_Cache::$trackGet[$realKey]['hits']++;
            Gdn_Cache::$trackGet[$realKey]['time'] += $keyTime;
            
            if ($keyData !== false) {
               $keyData = serialize($keyData);

               $keySize = strlen($keyData);
               if (is_null(Gdn_Cache::$trackGet[$realKey]['keysize']))
                  Gdn_Cache::$trackGet[$realKey]['keysize'] = $keySize;
               else
                  Gdn_Cache::$trackGet[$realKey]['wasted'] += $keySize;

               Gdn_Cache::$trackGet[$realKey]['transfer'] += Gdn_Cache::$trackGet[$realKey]['keysize'];
            }
         }
      }
      
      // Miss: return the fallback
      if ($data === false) return $this->Fallback($key, $options);
      
      // Hit: Single key. Return the value
      if (!$multi) {
         $val = sizeof($data) ? array_pop($data) : false;
         return $val;
      }
      
      // Hit: Multi key. Return stripped array.
      $dataStripped = array();
      foreach ($data as $index => $value) {
         $dataStripped[$this->StripKey($index, $finalOptions)] = $value;
      }
      $data = $dataStripped;
      unset($dataStripped);
      return $data;
   }
   
   public function Exists($Key, $Options = array()) {
      return ($this->Get($Key, $Options) === Gdn_Cache::CACHEOP_FAILURE) ? Gdn_Cache::CACHEOP_FAILURE : Gdn_Cache::CACHEOP_SUCCESS;
   }
   
   public function Remove($key, $options = array()) {
      $finalOptions = array_merge($this->StoreDefaults, $options);
      
      $realKey = $this->MakeKey($key, $finalOptions);
      $deleted = $this->Memcache->delete($realKey);
      unset(Gdn_Cache::$localCache[$realKey]);
      return ($deleted) ? Gdn_Cache::CACHEOP_SUCCESS : Gdn_Cache::CACHEOP_FAILURE;
   }
   
   public function Replace($Key, $Value, $Options = array()) {
      return $this->Store($Key, $Value, $Options);
   }
   
   public function Increment($Key, $Amount = 1, $Options = array()) {
      $FinalOptions = array_merge($this->StoreDefaults, $Options);
      
      $Initial = GetValue(Gdn_Cache::FEATURE_INITIAL, $FinalOptions, 0);
      $Expiry = GetValue(Gdn_Cache::FEATURE_EXPIRY, $FinalOptions, 0);
      $RequireBinary = $Initial || $Expiry;
      $Initial = !is_null($Initial) ? $Initial : 0;
      $Expiry = !is_null($Expiry) ? $Expiry : 0;
      
      $TryBinary = $this->Option(Memcached::OPT_BINARY_PROTOCOL, FALSE) & $RequireBinary;
      $RealKey = $this->MakeKey($Key, $FinalOptions);
      switch ($TryBinary) {
         case FALSE:
            $Incremented = $this->Memcache->increment($RealKey, $Amount);
            break;
         case TRUE;
            $Incremented = $this->Memcache->increment($RealKey, $Amount, $Initial, $Expiry);
            break;
      }
      if ($Incremented !== FALSE) {
         Gdn_Cache::LocalSet($RealKey, $Incremented);
         return $Incremented;
      }
      return Gdn_Cache::CACHEOP_FAILURE;
   }
   
   public function Decrement($Key, $Amount = 1, $Options = array()) {
      $FinalOptions = array_merge($this->StoreDefaults, $Options);
      
      $Initial = GetValue(Gdn_Cache::FEATURE_INITIAL, $FinalOptions, NULL);
      $Expiry = GetValue(Gdn_Cache::FEATURE_EXPIRY, $FinalOptions, NULL);
      $RequireBinary = $Initial || $Expiry;
      $Initial = !is_null($Initial) ? $Initial : 0;
      $Expiry = !is_null($Expiry) ? $Expiry : 0;
      
      $TryBinary = $this->Option(Memcached::OPT_BINARY_PROTOCOL, FALSE) & $RequireBinary;
      $RealKey = $this->MakeKey($Key, $FinalOptions);
      switch ($TryBinary) {
         case FALSE:
            $Decremented = $this->Memcache->decrement($RealKey, $Amount);
            break;
         case TRUE;
            $Decremented = $this->Memcache->decrement($RealKey, $Amount, $Initial, $Expiry);
            break;
      }
      if ($Decremented !== FALSE) {
         Gdn_Cache::LocalSet($RealKey, $Decremented);
         return $Decremented;
      }
      return Gdn_Cache::CACHEOP_FAILURE;
   }
   
   public function Flush() {
      return $this->Memcache->flush();
   }
   
   public function ResultCode() {
      return $this->Memcache->getResultCode();
   }
   
   public function ResultMessage() {
      return $this->Memcache->getResultMessage();
   }
}
