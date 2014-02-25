<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<div class="Help Aside">
   <?php
   echo '<h2>', T('Need More Help?'), '</h2>';
   echo '<ul>';
   echo Wrap(Anchor(T("Video tutorial on managing appearance"), 'settings/tutorials/appearance'), 'li');
   echo '</ul>';
   ?>
</div>
<h1><?php echo T('Manage Messages'); ?></h1>
<div class="Info"><?php echo T('Messages can appear anywhere in your application.', 'Messages can appear anywhere in your application, and can be used to inform your users of news and events. Use this page to re-organize your messages by dragging them up or down.'); ?></div>
<div class="FilterMenu"><?php echo Anchor(T('Add Message'), 'dashboard/message/add', 'AddMessage SmallButton'); ?></div>
<?php if ($this->MessageData->NumRows() > 0) { ?>
<table id="MessageTable" border="0" cellpadding="0" cellspacing="0" class="AltColumns Sortable">
   <thead>
      <tr id="0">
         <th><?php echo T('Location'); ?></th>
         <th class="Alt"><?php echo T('Message'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$Alt = FALSE;
foreach ($this->MessageData->Result() as $Message) {
   $Message = $this->MessageModel->DefineLocation($Message);
   $Alt = $Alt ? FALSE : TRUE;
   ?>
   <tr id="<?php
      echo $Message->MessageID;
      echo $Alt ? '" class="Alt' : '';
   ?>">
      <td class="Info nowrap"><?php
         printf(
            T('%1$s on %2$s'),
            ArrayValue($Message->AssetTarget, $this->_GetAssetData(), 'Custom Location'),
            ArrayValue($Message->Location, $this->_GetLocationData(), 'Custom Page')
         );
         
         if (GetValue('CategoryID', $Message) && $Category = CategoryModel::Categories($Message->CategoryID)) {
            echo '<div>'.
               Anchor($Category['Name'], CategoryUrl($Category));
            
            if (GetValue('IncludeSubcategories', $Message)) {
               echo ' '.T('and subcategories');
            }
               
            echo '</div>';
         }
      ?><div>
         <strong><?php echo $Message->Enabled == '1' ? T('Enabled') : T('Disabled'); ?></strong>
         <?php
         echo Anchor(T('Edit'), '/dashboard/message/edit/'.$Message->MessageID, 'EditMessage SmallButton');
         echo Anchor(T('Delete'), '/dashboard/message/delete/'.$Message->MessageID.'/'.$Session->TransientKey(), 'DeleteMessage SmallButton');
         ?>
         </div>
      </td>
      <td class="Alt"><div class="Message <?php echo $Message->CssClass; ?>"><?php echo Gdn_Format::Text($Message->Content); ?></div></td>
   </tr>
<?php } ?>
   </tbody>
</table>
<?php } ?>