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
      if ($Persist) {
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
      $this->RegisterFeature(Gdn_Cache::FEATURE_SHARD);

      if (C('Garden.Cache.Local', true)) {
         $this->RegisterFeature(Gdn_Cache::FEATURE_LOCAL);
      }

      $this->StoreDefaults = array(
         Gdn_Cache::FEATURE_COMPRESS      => FALSE,
         Gdn_Cache::FEATURE_TIMEOUT       => FALSE,
         Gdn_Cache::FEATURE_EXPIRY        => FALSE,
         Gdn_Cache::FEATURE_NOPREFIX      => FALSE,
         Gdn_Cache::FEATURE_FORCEPREFIX   => NULL,
         Gdn_Cache::FEATURE_SHARD         => FALSE,
         Gdn_Cache::FEATURE_LOCAL         => TRUE
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

      // No servers, cache temporarily offline
      if (!sizeof($Servers)) {
         SaveToConfig('Cache.Enabled', false, false);
         return false;
      }

      // Persistent, and already have servers. Shortcircuit adding.
      if ($this->Config(Gdn_Cache::CONTAINER_PERSISTENT) && count($this->servers()))
         return true;

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
            $Value = val($KeyName, $CacheServer, NULL);
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

      $CacheLocation = val(Gdn_Cache::CONTAINER_LOCATION,$Options);

      // Merge the options array with our local defaults
      $Defaults = array(
         Gdn_Cache::CONTAINER_WEIGHT      => 1
      );

      $FinalContainer = array_merge($Defaults, $Options);
      $this->Containers[$CacheLocation] = $FinalContainer;
      $PathInfo = explode(':',$CacheLocation);

      $ServerHostname = val(0, $PathInfo);
      $ServerPort = val(1, $PathInfo, 11211);

      $AddServerResult = $this->Memcache->addServer(
         $ServerHostname,
         $ServerPort,
         val(Gdn_Cache::CONTAINER_WEIGHT, $FinalContainer, 1)
      );

      if (!$AddServerResult) {
         $Callback = val(Gdn_Cache::CONTAINER_CALLBACK, $FinalContainer, NULL);
         if (!is_null($Callback))
            call_user_func($Callback, $ServerHostname, $ServerPort);

         return Gdn_Cache::CACHEOP_FAILURE;
      }

      return Gdn_Cache::CACHEOP_SUCCESS;
   }

   /**
    * Get server list with mapping keys
    *
    */
   public function ShardMap() {
      static $servers = null;
      if (is_null($servers)) {
         $serverList = $this->servers();
         $ns = count($serverList);
         $servers = array();

         if ($this->canAutoShard()) {

            // Use getServerByKey to determine server keys

            $i = $ns*10;
            do {
               $i--; // limited iterations
               $shardKey = betterRandomString(6,'a0');
               $shardServer = $this->Memcache->getServerByKey($shardKey);
               $shardServerName = $shardServer['host'];
               if ($shardServerName)
                  $servers[$shardServerName] = $shardKey;
            } while ($i > 0 && count($servers) < $ns);

         }

         if (!count($servers)) {

            // Use random server keys

            foreach ($serverList as $server) {
               $serverName = $server['host'];
               $servers[$serverName] = betterRandomString(6,'a0');
            }

         }
      }
      return $servers;
   }

   /**
    * Determine if we can auto distribute shards
    *
    * Version of memcached prior to v2.2 could not be relied upon to return
    * reliable results for getServerByKey(), so we have to generate random
    * server keys and hope for the best.
    *
    * @static boolean $canAutoShard
    * @return boolean
    */
   public function CanAutoShard() {
      static $canAutoShard = null;
      if (is_null($canAutoShard)) {
         $mcversion = phpversion('memcached');
         $canAutoShard = version_compare($mcversion, '2.2', '>=');
      }
      return $canAutoShard;
   }

   /**
    * Shard a key/value and create its manifest key
    *
    *  array
    *    type     // 'shard'
    *    hash     // hash of serialized data
    *    size     // number of bytes in serialized data
    *    shards   // sharded data
    *      [...]
    *        server  // server key, for assigning a server
    *        key     // actual key
    *        data    // data shard
    *    keys     // list of shard keys grouped by server key
    *      [server key]
    *        [...]
    *          key
    *
    * @param string $key data key
    * @param mixed $value data value to shard
    * @param int|boolean $shards number of shards, or simply bool true
    * @return array
    */
   public function Shard($key, $value, $shards) {

      $shardMap = $this->shardMap();
      $mapSize = count($shardMap);
      $shardMap = array_values($shardMap);

      // Calculate automatic shard count
      if (!is_numeric($shards)) {
         $shards = $mapSize;

         // If we're not precisely targeting keys, add a shard for safety
         if (!$this->canAutoShard())
            $shards = $mapSize + 1;
      }

      // Prepare manifest
      $data = serialize($value);
      $hash = md5($data);
      $size = strlen($data);
      $manifest = new MemcachedShard();
      $manifest->hash = $hash;
      $manifest->size = $size;

      // Determine chunk size
      $chunk = ceil($size / $shards);

      // Write keys
      $chunks = str_split($data, $chunk);
      $j = 0;
      for ($i = 0; $i < $shards; $i++) {
         $shardKey = sprintf('%s-shard%d', $key, $i);
         $serverKey = $shardMap[$j];
         $manifest->shards[] = array(
            'server' => $serverKey,
            'key'    => $shardKey,
            'data'   => $chunks[$i]
         );
         if (!key_exists($serverKey, $manifest->keys))
            $manifest->keys[$serverKey] = array();
         $manifest->keys[$serverKey][] = $shardKey;
         $j++;
         if ($j >= $mapSize) $j = 0;
      }

      return $manifest;
   }

   public function Add($key, $value, $options = array()) {
      if (!$this->online()) return Gdn_Cache::CACHEOP_FAILURE;

      $finalOptions = array_merge($this->StoreDefaults, $options);

      $expiry = val(Gdn_Cache::FEATURE_EXPIRY,$finalOptions,0);
      $useLocal = (bool)$finalOptions[Gdn_Cache::FEATURE_LOCAL];

      $realKey = $this->makeKey($key, $finalOptions);

      // Sharding, write real keys
      if (key_exists(Gdn_Cache::FEATURE_SHARD, $finalOptions) && $shards = $finalOptions[Gdn_Cache::FEATURE_SHARD]) {

         $manifest = $this->shard($realKey, $value, $shards);
         $shards = $manifest->shards;
         unset($manifest->shards);

         // Attempt to write manifest
         $added = $this->Memcache->add($realKey, $manifest, $expiry);

         // Check if things went ok
         $ok = $this->lastAction($realKey);
         if (!$ok || !$added) return Gdn_Cache::CACHEOP_FAILURE;

         // Write real keys
         foreach ($shards as $shard) {
            $this->Memcache->setByKey($shard['server'], $shard['key'], $shard['data'], $expiry);
         }
         unset($shards);

         if ($useLocal)
            $this->localSet($realKey, $value);
         return Gdn_Cache::CACHEOP_SUCCESS;
      }

      $stored = $this->Memcache->add($realKey, $value, $expiry);

      // Check if things went ok
      $ok = $this->lastAction($realKey);
      if (!$ok) return Gdn_Cache::CACHEOP_FAILURE;

      if ($stored) {
         if ($useLocal)
            $this->localSet($realKey, $value);
         return Gdn_Cache::CACHEOP_SUCCESS;
      }
      return Gdn_Cache::CACHEOP_FAILURE;
   }

   public function Store($key, $value, $options = array()) {
      if (!$this->online()) return Gdn_Cache::CACHEOP_FAILURE;

      $finalOptions = array_merge($this->StoreDefaults, $options);

      $expiry = (int)val(Gdn_Cache::FEATURE_EXPIRY,$finalOptions,0);
      $useLocal = (bool)$finalOptions[Gdn_Cache::FEATURE_LOCAL];

      $realKey = $this->makeKey($key, $finalOptions);

      // Sharding, write real keys
      if (key_exists(Gdn_Cache::FEATURE_SHARD, $finalOptions) && $shards = $finalOptions[Gdn_Cache::FEATURE_SHARD]) {

         $manifest = $this->shard($realKey, $value, $shards);
         $shards = $manifest->shards;
         unset($manifest->shards);

         // Attempt to write manifest
         $added = $this->Memcache->set($realKey, $manifest, $expiry);

         // Check if things went ok
         $ok = $this->lastAction($realKey);
         if (!$ok || !$added) return Gdn_Cache::CACHEOP_FAILURE;

         // Write real keys
         foreach ($shards as $shard) {
            $this->Memcache->setByKey($shard['server'], $shard['key'], $shard['data'], $expiry);
         }
         unset($shards);

         if ($useLocal)
            $this->localSet($realKey, $value);
         return Gdn_Cache::CACHEOP_SUCCESS;
      }

      $stored = $this->Memcache->set($realKey, $value, $expiry);

      // Check if things went ok
      $ok = $this->lastAction($realKey);
      if (!$ok) return Gdn_Cache::CACHEOP_FAILURE;

      if ($stored) {
         if ($useLocal)
            $this->localSet($realKey, $value);
         return Gdn_Cache::CACHEOP_SUCCESS;
      }
      return Gdn_Cache::CACHEOP_FAILURE;
   }

   public function Get($key, $options = array()) {
      if (!$this->online()) return Gdn_Cache::CACHEOP_FAILURE;

      $startTime = microtime(TRUE);

      $finalOptions = array_merge($this->StoreDefaults, $options);

      $useLocal = (bool)$finalOptions[Gdn_Cache::FEATURE_LOCAL];

      $localData = array();
      $realKeys = array();
      if (is_array($key)) {
         $multi = true;
         foreach ($key as $multiKey) {
            $realKey = $this->makeKey($multiKey, $finalOptions);

            // Skip this key if we already have it
            if ($useLocal) {
               $local = $this->localGet($realKey);
               if ($local !== Gdn_Cache::CACHEOP_FAILURE) {
                  $localData[$realKey] = $local;
                  continue;
               }
            }
            $realKeys[] = $realKey;
         }
      } else {
         $multi = false;
         $realKey = $this->makeKey($key, $finalOptions);

         // Completely short circuit if we already have everything
         if ($useLocal) {
            $local = $this->localGet($realKey);
            if ($local !== false)
               return $local;
         }

         $realKeys = array($realKey);
      }

      $data = array(); $hitCache = false;
      if ($numKeys = sizeof($realKeys)) {
         $hitCache = true;
         if ($numKeys > 1) {
            $data = $this->Memcache->getMulti($realKeys);
            $ok = $this->lastAction();
         } else {
            $data = $this->Memcache->get($realKey);
            // Check if things went ok
            $ok = $this->lastAction($realKey);
            $data = array($realKey => $data);
         }

         if (!$ok) return Gdn_Cache::CACHEOP_FAILURE;

         $storeData = array();
         foreach ($data as $localKey => &$localValue) {

            // Is this a sharded key manifest?
            if (is_object($localValue) && $localValue instanceof MemcachedShard) {

               $manifest = $localValue;

               // MultiGet sub-keys
               $shardKeys = array();
               foreach ($manifest->keys as $serverKey => $keys) {
                  $serverKeys = $this->Memcache->getMultiByKey($serverKey, $keys);
                  $shardKeys = array_merge($shardKeys, $serverKeys);
               }
               ksort($shardKeys);

               // Check subkeys for validity
               $shardData = implode('', array_values($shardKeys));
               unset($shardKeys);
               $dataHash = md5($shardData);
               if ($dataHash != $manifest->hash) continue;

               $localValue = unserialize($shardData);
            }

            if ($localValue !== false)
               $storeData[$localKey] = $localValue;

         }
         $data = $storeData;
         unset($storeData);

         // Cache in process memory
         if ($useLocal && sizeof($data))
            $this->localSet($data);
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

            $keyData = val($realKey, $data, false);
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
         $dataStripped[$this->stripKey($index, $finalOptions)] = $value;
      }
      $data = $dataStripped;
      unset($dataStripped);
      return $data;
   }

   public function Exists($Key, $Options = array()) {
      if (!$this->Online()) return Gdn_Cache::CACHEOP_FAILURE;

      return ($this->Get($Key, $Options) === Gdn_Cache::CACHEOP_FAILURE) ? Gdn_Cache::CACHEOP_FAILURE : Gdn_Cache::CACHEOP_SUCCESS;
   }

   public function Remove($key, $options = array()) {
      if (!$this->Online()) return Gdn_Cache::CACHEOP_FAILURE;

      $finalOptions = array_merge($this->StoreDefaults, $options);

      $realKey = $this->MakeKey($key, $finalOptions);
      $deleted = $this->Memcache->delete($realKey);

      // Check if things went ok
      $ok = $this->lastAction($realKey);
      if (!$ok) return Gdn_Cache::CACHEOP_FAILURE;

      unset(Gdn_Cache::$localCache[$realKey]);
      return ($deleted) ? Gdn_Cache::CACHEOP_SUCCESS : Gdn_Cache::CACHEOP_FAILURE;
   }

   public function Replace($key, $value, $options = array()) {
      if (!$this->Online()) return Gdn_Cache::CACHEOP_FAILURE;

      return $this->Store($key, $value, $options);
   }

   public function Increment($key, $amount = 1, $options = array()) {
      if (!$this->Online()) return Gdn_Cache::CACHEOP_FAILURE;

      $finalOptions = array_merge($this->StoreDefaults, $options);

      $initial = val(Gdn_Cache::FEATURE_INITIAL, $finalOptions, 0);
      $expiry = val(Gdn_Cache::FEATURE_EXPIRY, $finalOptions, 0);
      $requireBinary = $initial || $expiry;
      $initial = !is_null($initial) ? $initial : 0;
      $expiry = !is_null($expiry) ? $expiry : 0;

      $tryBinary = $this->Option(Memcached::OPT_BINARY_PROTOCOL, FALSE) & $requireBinary;
      $realKey = $this->MakeKey($key, $finalOptions);
      switch ($tryBinary) {
         case FALSE:
            $incremented = $this->Memcache->increment($realKey, $amount);
            if (is_null($incremented) && $initial) {
               $incremented = $this->Memcache->set($realKey, $initial);
               if ($incremented) $incremented = $initial;
            }
            break;
         case TRUE;
            $incremented = $this->Memcache->increment($realKey, $amount, $initial, $expiry);
            break;
      }

      // Check if things went ok
      $ok = $this->lastAction($realKey);
      if (!$ok) return Gdn_Cache::CACHEOP_FAILURE;

      if ($incremented !== FALSE) {
         Gdn_Cache::LocalSet($realKey, $incremented);
         return $incremented;
      }
      return Gdn_Cache::CACHEOP_FAILURE;
   }

   public function Decrement($key, $amount = 1, $options = array()) {
      if (!$this->Online()) return Gdn_Cache::CACHEOP_FAILURE;

      $finalOptions = array_merge($this->StoreDefaults, $options);

      $initial = val(Gdn_Cache::FEATURE_INITIAL, $finalOptions, NULL);
      $expiry = val(Gdn_Cache::FEATURE_EXPIRY, $finalOptions, NULL);
      $requireBinary = $initial || $expiry;
      $initial = !is_null($initial) ? $initial : 0;
      $expiry = !is_null($expiry) ? $expiry : 0;

      $tryBinary = $this->Option(Memcached::OPT_BINARY_PROTOCOL, FALSE) & $requireBinary;
      $realKey = $this->MakeKey($key, $finalOptions);
      switch ($tryBinary) {
         case FALSE:
            $decremented = $this->Memcache->decrement($realKey, $amount);
            if (is_null($decremented) && $initial) {
               $decremented = $this->Memcache->set($realKey, $initial);
               if ($decremented) $decremented = $initial;
            }
            break;
         case TRUE;
            $decremented = $this->Memcache->decrement($realKey, $amount, $initial, $expiry);
            break;
      }

      // Check if things went ok
      $ok = $this->lastAction($realKey);
      if (!$ok) return Gdn_Cache::CACHEOP_FAILURE;

      if ($decremented !== FALSE) {
         Gdn_Cache::LocalSet($realKey, $decremented);
         return $decremented;
      }
      return Gdn_Cache::CACHEOP_FAILURE;
   }

   public function Flush() {
      return $this->Memcache->flush();
   }

   /**
    * Check what memcache thinks about our last operation
    *
    * @param type $key
    * @return boolean
    */
   public function lastAction($key = null) {
      $lastCode = $this->Memcache->getResultCode();

      if ($lastCode == 47 || $lastCode == 35) {
         if ($key) {
            $server = $this->Memcache->getServerByKey($key);
            $host = $server['host'];
            $port = $server['port'];
            $this->fail("{$host}:{$port}");
         }
         return false;
      }
      return true;
   }

   public function online() {
      return (bool)sizeof($this->Containers);
   }

   public function servers() {
      return $this->Memcache->getServerList();
   }

   public function ResultCode() {
      return $this->Memcache->getResultCode();
   }

   public function ResultMessage() {
      return $this->Memcache->getResultMessage();
   }
}

class MemcachedShard {

   public $hash;
   public $size;

   public $shards = array();
   public $keys = array();

}
