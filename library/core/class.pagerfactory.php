<?php if (!defined('APPLICATION')) exit();

/**
 * Handles creating and returning a pager
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_PagerFactory {

   public function GetPager($PagerType, $Sender) {
      $PagerType = $PagerType.'Module';
         
      if (!class_exists($PagerType))
         $PagerType = 'PagerModule';

      if (!class_exists($PagerType))
         return FALSE;

      return new $PagerType($Sender);
   }
}