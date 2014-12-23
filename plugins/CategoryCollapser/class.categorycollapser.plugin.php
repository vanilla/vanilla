<?php if (!defined('APPLICATION')) exit();

// Define the plugin:
$PluginInfo['CategoryCollapser'] = array(
   'Name' => 'Category Collapser',
   'Description' => 'Allows categories to be collapsed and expanded. Requires using category table view and displaying root categories as headings.',
   'Version' => '1.0.1',
   'Author' => "Vanilla Forums",
   'AuthorUrl' => 'http://vanillaforums.com',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'MobileFriendly' => TRUE
);

class CategoryCollapserPlugin extends Gdn_Plugin {
   /**
	 * Just include our JS on all category pages.
	 */
   public function CategoriesController_Render_Before($Sender) {
      $Sender->AddJsFile("category_collapse.js", "plugins/CategoryCollapser");
      $Style = Wrap('
      .Expando {
         float: right;
         background: url(plugins/CategoryCollapser/design/tagsprites.png) no-repeat 0 -52px;
         height: 16px;
         width: 16px;
         color: transparent;
         text-shadow: none;
         cursor: pointer; }
      .Expando-Collapsed .Expando {
         background-position: 0 -69px; }', 'style');
      $Sender->AddAsset('Head', $Style);
   }
}