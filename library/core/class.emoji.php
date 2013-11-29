<?php if (!defined('APPLICATION')) exit();

/**
 *
 */

class Emoji {
   /// Properties ///

   /**
    *
    * @var Emoji The singleton instance of this class.
    */
   protected static $instance;

   /// Methods ///

   /**
    * Get the singleton instance of this class.
    * @return Emoji
    */
   public static function instance() {
      if (Emoji::$instance === null) {
         Emoji::$instance = new Emoji();
      }

      return Emoji::$instance;
   }
}