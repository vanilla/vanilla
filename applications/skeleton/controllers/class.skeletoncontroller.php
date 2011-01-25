<?php if (!defined('APPLICATION')) exit();
/**
 * Skeleton Controller for new applications.
 * 
 * Repace 'Skeleton' with your app's short name wherever you see it.
 *
 * @package Skeleton
 */
 
/**
 * A brief description of the controller.
 *
 * @since 1.0
 * @package Skeleton
 */
class SkeletonController extends Gdn_Controller {
   /**
    * Do-nothing construct to let children constructs bubble up.
    *
    * @access public
    */
   public function __construct() {
      parent::__construct();
   }
   
   /**
    * This is a good place to include JS, CSS, and modules used by all methods of this controller.
    *
    * Always called by dispatcher before controller's requested method.
    * 
    * @since 1.0
    * @access public
    */
   public function Initialize() {
      // There are 4 delivery types used by Render().
      // DELIVERY_TYPE_ALL is the default and indicates an entire page view.
      if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
         $this->Head = new HeadModule($this);
         
      // Call Gdn_Controller's Initialize() as well.
      parent::Initialize();
   }
}
