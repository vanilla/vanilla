<?php if (!defined('APPLICATION')) exit();
$this->RenderAsset('Messages');
?>
<div class="Column Column2">
   <h1><?php echo T('Recently Active Users'); ?></h1>
   <table id="RecentUsers" border="0" cellpadding="0" cellspacing="0" class="AltColumns">
      <!--
      <thead>
         <tr>
            <th><?php echo T('User'); ?></th>
            <th class="Alt"><?php echo T('Last Active'); ?></th>
         </tr>
      </thead>
      -->
      <tbody>
         <?php
         $Alt = '';
         foreach ($this->ActiveUserData as $User) {
            ?>
            <tr<?php
               $Alt = $Alt == '' ? ' class="Alt"' : '';
               echo $Alt;
            ?>>
               <th><?php
                  $PhotoUser = UserBuilder($User);
                  echo UserPhoto($PhotoUser);
                  echo UserAnchor($User);
               ?></th>
               <td class="Alt"><?php echo Gdn_Format::Date($User->DateLastActive, 'html'); ?></td>
            </tr>
            <?php
         }
         ?>
      </tbody>
   </table>
</div>
<div class="Column Column1 AnnounceColumn">
   <h1><?php echo T('Open Source News'); ?></h1>
   <div class="List"></div>
</div>
<div class="Column Column1 NewsColumn">
   <h1><?php echo T('Blog Posts By Vanilla Forums'); ?></h1>
   <div class="List"></div>
</div>