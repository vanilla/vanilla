<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<style>
   .CategoryPhoto {
      float: left;
      margin-right: 8px;
      max-height: 64px;
      max-width: 64px;
   }
</style>
<div class="Help Aside">
   <?php
   echo Wrap(T('Need More Help?'), 'h2');
   echo '<ul>';
   echo Wrap(Anchor(T("Video tutorial on managing categories"), 'settings/tutorials/category-management-and-advanced-settings'), 'li');
   echo Wrap(Anchor(T('Managing Categories'), 'http://vanillaforums.org/docs/managecategories'), 'li');
   echo Wrap(Anchor(T('Adding & Editing Categories'), 'http://vanillaforums.org/docs/managecategories#add'), 'li');
   echo '</ul>';
   ?>
</div>
<h1><?php echo T('Manage Categories'); ?></h1>
<div class="Info">
   <?php echo T('Categories are used to help organize discussions.', 'Categories are used to help organize discussions. Drag &amp; drop the categories to sort and nest them.'); ?>
</div>
<div class="FilterMenu"><?php
   if (C('Vanilla.Categories.Use')) {
      echo Anchor(T('Add Category'), 'vanilla/settings/addcategory', 'SmallButton');
      echo Wrap(Anchor(T("Don't use Categories"), 'vanilla/settings/managecategories/disable/'.Gdn::Session()->TransientKey(), 'SmallButton'));
   } else {
      echo Anchor(T('Use Categories'), 'vanilla/settings/managecategories/enable/'.Gdn::Session()->TransientKey(), 'SmallButton');
   }
?></div>
<?php 
if (C('Vanilla.Categories.Use')) {
   ?>
   <div class="Help Aside">
      <?php
      echo '<h2>', T('Did You Know?'), '</h2>';
      echo '<ul>';
      echo '<li>', sprintf(T('You can make the categories page your homepage.', 'You can make your categories page your homepage <a href="%s">here</a>.'), Url('/dashboard/settings/homepage')), '</li>';
      echo '<li>', sprintf(T('Make sure you click View Page', 'Make sure you click <a href="%s">View Page</a> to see what your categories page looks like after saving.'), Url('/categories')), '</li>';
      echo '<li>', T('Drag and drop the categories below to sort and nest them.'), '</li>';
      echo '</ul>';
      ?>
   </div>
   <h1><?php
      echo T('Category Page Layout');
      echo ' ';
      echo Anchor(T('View Page'), 'categories');
   ?></h1>
   <?php
   echo $this->Form->Open();
   echo $this->Form->Errors();
   echo '<div class="Info">'
      .T('Configure how nested categories are displayed to users.')
      .Wrap(sprintf(
         T('Vanilla.Categories.MaxDisplayDepth', 'Place nested categories in a comma-delimited list when they are %1$s'),
         $this->Form->DropDown('Vanilla.Categories.MaxDisplayDepth', GetValue('MaxDepthData', $this->Data))
      ), 'div')
      .Wrap($this->Form->CheckBox('Vanilla.Categories.DoHeadings', 'Display root categories as headings.'), 'div')
      .Wrap($this->Form->CheckBox('Vanilla.Categories.HideModule', 'Do not display the categories in the side panel.'), 'div')
   .'</div>'
   .'<div class="Buttons Wrap">'
   .$this->Form->Button('Save')
   .'</div>'
   .$this->Form->Close();

   echo Wrap(T('Organize Categories'), 'h1')
   .'<ol class="Sortable">';
   $Right = array(); // Start with an empty $Right stack
   $LastRight = 0;
   $OpenCount = 0;
   $Loop = 0;
   foreach ($this->CategoryData->Result() as $Category) {
      if ($Category->CategoryID > 0) {
         // Only check stack if there is one
         $CountRight = count($Right);
         if ($CountRight > 0) {  
            // Check if we should remove a node from the stack
            while (array_key_exists($CountRight - 1, $Right) && $Right[$CountRight - 1] < $Category->TreeRight) {
               array_pop($Right);
               $CountRight--;
            }  
         }  
         
         // Are we opening a new list?
         if ($CountRight > $LastRight) {
            $OpenCount++;
            echo "\n<ol>";
         } elseif ($OpenCount > $CountRight) {
            // Or are we closing open list and list items?
            while ($OpenCount > $CountRight) {
               $OpenCount--;
               echo "</li>\n</ol>\n";
            }
            echo '</li>';
         } elseif ($Loop > 0) {
            // Or are we closing an open list item?
            echo "</li>";
         }
         
         echo "\n".'<li id="list_'.$Category->CategoryID.'">';
         // DEBUG: echo Wrap($Category->Name.' [countright: '.$CountRight.' lastcount: '.$LastRight.' opencount: '.$OpenCount.']', 'div');
         $CategoryUrl = CategoryUrl($Category);
         
         if ($Category->Photo) {
            $Photo = Img(Gdn_Upload::Url($Category->Photo), array('class' => 'CategoryPhoto'));
         } else {
            $Photo = '';
         }
         
         echo Wrap(
            '<table'.($OpenCount > 0 ? ' class="Indented"' : '').'>
               <tr>
                  <td>
                     '.$Photo.'
                     <strong>'.htmlspecialchars($Category->Name).'</strong>
                     '.Anchor(htmlspecialchars(rawurldecode($CategoryUrl)), $CategoryUrl).'
                     '.Wrap($Category->Description, 'blockquote').'
                     './*Wrap("ID: {$Category->CategoryID}, PermID: {$Category->PermissionCategoryID}", 'div').*/'
                  </td>
                  <td class="Buttons">'
                     .Anchor(T('Edit'), 'vanilla/settings/editcategory/'.$Category->CategoryID, 'SmallButton')
                     .Anchor(T('Delete'), 'vanilla/settings/deletecategory/'.$Category->CategoryID, 'SmallButton')
                  .'</td>
               </tr>
            </table>'
         ,'div');
         
         // Add this node to the stack  
         $Right[] = $Category->TreeRight;
         $LastRight = $CountRight;
         $Loop++;
      }
   }
   if ($OpenCount > 0)
      echo "</li>\n</ol>\n</li>\n";
   else
      echo "</li>\n";
      
   echo '</ol>';
}