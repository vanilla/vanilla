<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$FirstRow = $this->CategoryData->FirstRow();
$CssClass = $FirstRow && ($FirstRow->AllowDiscussions == '0' || $FirstRow->ParentCategoryID > 0) ? ' HasParents' : '';
?>
<h1><?php echo T('Manage Categories'); ?></h1>
<div class="Info">
   <?php echo T('Categories are used to help organize discussions. '); ?>
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
      echo $this->Form->Open();
?>
<table class="FormTable Sortable AltColumns<?php echo $CssClass;?>" id="CategoryTable">
   <thead>
      <tr id="0">
         <th><?php echo T('Category'); ?></th>
         <th class="Alt"><?php echo T('Description'); ?></th>
         <th><?php echo HoverHelp(T('Url'), T('A url-friendly version of the category name for better SEO.')); ?></th>
         <th class="Alt"><?php echo T('Options'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->CategoryData->Result() as $Category) {
   $Alt = $Alt ? FALSE : TRUE;
   $CssClass = $Alt ? 'Alt' : '';
   $CssClass .= $Category->AllowDiscussions == '1' ? ' Child' : ' Parent';
      
   $CssClass = trim($CssClass);
   ?>
   <tr id="<?php echo $Category->CategoryID; ?>"<?php echo $CssClass != '' ? ' class="'.$CssClass.'"' : ''; ?>>
      <td class="First"><strong><?php echo $Category->Name; ?></strong></td>
      <td class="Alt"><?php echo $Category->Description; ?></td>
      <td><?php echo $Category->AllowDiscussions == '1' ? Url('categories/'.$Category->UrlCode.'/') : '&nbsp;'; ?></td>
      <td class="Alt Last">
         <?php
         echo Anchor(T('Edit'), 'vanilla/settings/editcategory/'.$Category->CategoryID, 'SmallButton');
         echo Anchor(T('Delete'), 'vanilla/settings/deletecategory/'.$Category->CategoryID, 'SmallButton');
         ?>
      </td>
   </tr>
<?php } ?>
   </tbody>
</table>
<?php
      echo $this->Form->Close();
   }