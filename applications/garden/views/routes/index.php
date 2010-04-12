<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<h1><?php echo T('Manage Routes'); ?></h1>
<div class="FilterMenu"><?php echo Anchor('Add Route', 'garden/routes/add', 'AddRoute Button'); ?></div>
<div class="Info"><?php
   echo T('Routes can be used to redirect users to various parts of your site depending on the url. ');
   echo Anchor('Get more information on creating custom routes', 'http://vanillaforums.org/page/routes');
?></div>
<table class="AltColumns" id="RouteTable">
   <thead>
      <tr>
         <th><?php echo T('Route'); ?></th>
         <th class="Alt"><?php echo T('Target'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$i = 0;
$Alt = FALSE;
foreach ($this->Routes as $Route => $Target) {
   $Alt = !$Alt;
?>
   <tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
      <td class="Info">
         <strong><?php echo $Route; ?></strong>
         <div>
         <?php
         echo Anchor('Edit', '/garden/routes/edit/'.$i, 'EditRoute');
         if (!in_array($Route, $this->ReservedRoutes)) {
            echo '<span>|</span>';
            echo Anchor('Delete', '/routes/delete/'.$i.'/'.$Session->TransientKey(), 'DeleteRoute');
         }
         ?>
         </div>
      </td>
      <td class="Alt"><?php echo $Target; ?></td>
   </tr>
<?php
   ++$i;
}
?>
   </tbody>
</table>