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
 * Your app will automatically be able to find any models from your app when you instantiate them.
 * You can also access the UserModel and RoleModel (in Dashboard) from anywhere in the framework.
 *
 * @since 1.0
 * @package Skeleton
 */
class SkeletonController extends Gdn_Controller {
   /** @var array List of objects to prep. They will be available as $this->$Name. */
   public $Uses = array('Form');
   
   /**
    * If you use a constructor, always call parent.
    * Delete this if you don't need it.
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
