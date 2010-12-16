<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<h1><?php echo T('Manage Categories'); ?></h1>
<div class="Info">
   <?php echo T('Categories are used to help organize discussions.', 'Categories are used to help organize discussions. Drag &amp; drop the categories to sort and nest them.'); ?>
</div>
<div class="FilterMenu"><?php
   echo Anchor(T('Add Category'), 'vanilla/settings/addcategory', 'SmallButton');
   if (C('Vanilla.Categories.Use')) {
      echo Wrap(Anchor(T("Don't use Categories"), 'vanilla/settings/managecategories/disable/'.Gdn::Session()->TransientKey(), 'SmallButton'));
   } else {
      echo Wrap(Anchor(T('Use Categories'), 'vanilla/settings/managecategories/enable/'.Gdn::Session()->TransientKey(), 'SmallButton'));
   }
?></div>
<?php 
if (C('Vanilla.Categories.Use')) {
   ?>
   <table cellpadding="0" cellspacing="0" border="0">
      <thead>
         <tr>
            <th><?php echo T('Category'); ?></th>
            <th class="Right"><?php echo T('Options'); ?></th>
         </tr>
      </thead>
   </table>
   <?php
   echo '<ol class="Sortable">';
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
         $CategoryUrl = Url('categories/'.$Category->UrlCode.'/', TRUE);
         echo Wrap(
            '<table'.($OpenCount > 0 ? ' class="Indented"' : '').'>
               <tr>
                  <td>
                     <strong>'.$Category->Name.'</strong>
                     '.Anchor($CategoryUrl, $CategoryUrl).'
                     '.Wrap($Category->Description, 'blockquote').'
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