<?php if (!defined('APPLICATION')) exit();
$Session = Gdn::Session();
echo $this->Form->Open();
?>
<h1><?php echo Gdn::Translate('Manage Routes'); ?></h1>
<p><?php
   echo Gdn::Translate('Routes can be used to redirect users to various parts of your site depending on the url. Use this screen to manage your custom routes. ');
   echo Anchor('Get more information on creating custom routes', 'http://gardenplatform.com/page/routes');
?></p>
<p><?php echo Anchor('Add Route', 'garden/routes/add', 'AddRoute Button'); ?></p>
<table class="AltRows" id="RouteTable">
   <thead>
      <tr>
         <th><?php echo Gdn::Translate('Route'); ?></th>
         <th class="Alt"><?php echo Gdn::Translate('Target'); ?></th>
         <th><?php echo Gdn::Translate('Options'); ?></th>
      </tr>
   </thead>
   <tbody>
<?php
$i = 0;
$Alt = FALSE;
foreach ($this->Routes as $Route => $Target) {
   $Alt = $Alt ? FALSE : TRUE;
?>
   <tr<?php echo $Alt ? ' class="Alt"' : ''; ?>>
      <td><?php echo Anchor($Route, '/garden/routes/edit/'.$i, 'EditRoute'); ?></td>
      <td class="Alt"><?php echo $Target; ?></td>
      <td><?php
         if (!in_array($Route, $this->ReservedRoutes))
            echo Anchor('Delete', '/routes/delete/'.$i.'/'.$Session->TransientKey(), 'DeleteRoute');
         else
            echo '&nbsp;';
         ?></td>
   </tr>
<?php
   ++$i;
}
?>
   </tbody>
</table>
<?php echo $this->Form->Close();