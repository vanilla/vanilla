<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
?>
<h1><?php echo T('Manage Routes'); ?></h1>
<div class="Info"><?php
   echo T('Routes can be used to redirect users to various parts of your site depending on the url.'),
   ' ',
   Anchor(T('Get more information on creating custom routes'), 'http://vanillaforums.org/page/routes');
?></div>
<div class="FilterMenu"><?php echo Anchor(T('Add Route'), 'dashboard/routes/add', 'AddRoute SmallButton'); ?></div>
<table class="AltColumns" id="RouteTable">
   <thead>
      <tr>
         <th><?php echo T('Route'); ?></th>
         <th class="Alt"><?php echo T('Target'); ?></th>
         <th class="Alt"><?php echo T('Type'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$i = 0;
$Alt = FALSE;
foreach ($this->MyRoutes as $Route => $RouteData) {
   $Alt = !$Alt;
   
   $Target = $RouteData['Destination'];
   $RouteType = T(Gdn::Router()->RouteTypes[$RouteData['Type']]);
   $Reserved = $RouteData['Reserved'];
?>
   <tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
      <td class="Info">
         <strong><?php echo $Route; ?></strong>
         <div>
         <?php
         echo Anchor(T('Edit'), '/dashboard/routes/edit/'.trim($RouteData['Key'], '='), 'EditRoute SmallButton');
         if (!$Reserved)
            echo Anchor(T('Delete'), '/routes/delete/'.trim($RouteData['Key']. '=').'/'.$Session->TransientKey(), 'DeleteRoute SmallButton');

         ?>
         </div>
      </td>
      <td class="Alt"><?php echo $Target; ?></td>
      <td class="Alt"><?php echo $RouteType; ?></td>
   </tr>
<?php
   ++$i;
}
?>
   </tbody>
</table>