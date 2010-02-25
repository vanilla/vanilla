<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
$FirstRow = $this->CategoryData->FirstRow();
$CssClass = $FirstRow && ($FirstRow->AllowDiscussions == '0' || $FirstRow->ParentCategoryID > 0) ? ' HasParents' : '';
echo $this->Form->Open();
?>
<h1><?php echo Gdn::Translate('Manage Categories'); ?></h1>
<div class="FilterMenu"><?php echo Anchor('Add Category', 'vanilla/settings/addcategory', 'Button'); ?></div>
<table class="FormTable Sortable AltColumns<?php echo $CssClass;?>" id="CategoryTable">
   <thead>
      <tr id="0">
         <th><?php echo Gdn::Translate('Category'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Description'); ?></th>
         <th><?php echo Gdn::Translate('Url'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Options'); ?></th>
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
      <td class="First"><a href="<?php echo Url('vanilla/settings/editcategory/'.$Category->CategoryID); ?>"><?php echo $Category->Name; ?></a></td>
      <td class="Alt"><?php echo $Category->Description; ?></td>
      <td><?php echo $Category->AllowDiscussions == '1' ? Url('categories/'.$Category->UrlCode.'/') : '&nbsp;'; ?></td>
      <td class="Alt"><?php echo Anchor('Delete', 'vanilla/settings/deletecategory/'.$Category->CategoryID); ?></td>
   </tr>
<?php } ?>
   </tbody>
</table>
<?php
echo $this->Form->Close();