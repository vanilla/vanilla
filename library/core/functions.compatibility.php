<?php

/**
 * General functions Interim Compatibility Map
 * 
 * These functions are copies of existing functions but with new and improved
 * names. Parent functions will be deprecated in a future release.
 *
 * @author Todd Burry <todd@vanillaforums.com> 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.2
 */

if (!function_exists('paths')) {
   /**
    * Concatenate path elements into single string
    * 
    * Takes a variable number of arguments and concatenates them. Delimiters will 
    * not be duplicated. Example: all of the following invocations will generate 
    * the path "/path/to/vanilla/applications/dashboard"
    * 
    * '/path/to/vanilla', 'applications/dashboard'
    * '/path/to/vanilla/', '/applications/dashboard'
    * '/path', 'to', 'vanilla', 'applications', 'dashboard'
    * '/path/', '/to/', '/vanilla/', '/applications/', '/dashboard'
    * 
    * @param function arguments
    * @return the concatentated path.
    */
   function paths() {
      $paths = func_get_args();
      $delimiter = '/';
      if (is_array($paths)) {
         $mungedPath = implode($delimiter, $paths);
         $mungedPath = str_replace(array($delimiter.$delimiter.$delimiter, $delimiter.$delimiter), array($delimiter, $delimiter), $mungedPath);
         return str_replace(array('http:/', 'https:/'), array('http://', 'https://'), $mungedPath);
      } else {
         return $paths;
      }
   }
}

if (!function_exists('val')) {
   /**
    * Return the value from an associative array or an object.
    *
    * @param string $key The key or property name of the value.
    * @param mixed $collection The array or object to search.
    * @param mixed $default The value to return if the key does not exist.
    * @return mixed The value from the array or object.
    */
   function val($key, $collection, $default = false) {
      if (is_array($collection) && array_key_exists($key, $collection)) {
         return $collection[$key];
      } elseif (is_object($collection) && property_exists($collection, $key)) {
         return $collection->$key;
      }
      return $default;
   }
}

if (!function_exists('valr')) {
   /**
    * Return the value from an associative array or an object.
    * This function differs from GetValue() in that $Key can be a string consisting of dot notation that will be used to recursivly traverse the collection.
    *
    * @param string $key The key or property name of the value.
    * @param mixed $collection The array or object to search.
    * @param mixed $default The value to return if the key does not exist.
    * @return mixed The value from the array or object.
    */
   function valr($key, $collection, $default = false) {
      $path = explode('.', $key);

      $value = $collection;
      for ($i = 0; $i < count($path); ++$i) {
         $subKey = $path[$i];

         if (is_array($value) && isset($value[$subKey])) {
            $value = $value[$subKey];
         } elseif (is_object($value) && isset($value->$subKey)) {
            $value = $value->$subKey;
         } else {
            return $default;
         }
      }
      return $value;
   }
}

if (!function_exists('svalr')) {
   /**
    * Set a key to a value in a collection
    * 
    * Works with single keys or "dot" notation. If $key is an array, a simple
    * shallow array_merge is performed.
    * 
    * @param string $key The key or property name of the value.
    * @param array $collection The array or object to search.
    * @param type $value The value to set
    * @return mixed Newly set value or if array merge
    */
   function svalr($key, &$collection, $value = null) {
      if (is_array($key)) {
         $collection = array_merge($collection, $key);
         return null;
      }

      if (strpos($key,'.')) {
         $path = explode('.', $key);

         $selection = &$collection;
         $mx = count($path) - 1;
         for ($i = 0; $i <= $mx; ++$i) {
            $subSelector = $path[$i];

            if (is_array($selection)) {
               if (!isset($selection[$subSelector]))
                  $selection[$subSelector] = array();
               $selection = &$selection[$subSelector];
            } else {
               return null;
            }
         }
         return $selection = $value;
      } else {
         return $collection[$key] = $value;
      }
   }
}