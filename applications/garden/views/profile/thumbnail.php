<?php if (!defined('APPLICATION')) exit();

echo '<h2>'.Gdn::Translate('Edit My Thumbnail').'</h2>';
echo $this->Form->Open(array('class' => 'Thumbnail'));
echo $this->Form->Errors();
?>
<div class="Info"><?php
   echo Gdn::Translate('Drag around and resize the square below to define your thumbnail icon.');
?></div>
<table>
   <thead>
      <tr>
         <th><?php echo Gdn::Translate('Original'); ?></th>
         <td><?php echo Gdn::Translate('Thumbnail'); ?></td>
      </tr>
   </thead>
   <tbody>
      <tr>
         <th>
            <?php echo Img('uploads/p'.$this->User->Photo, array('id' => 'cropbox')); ?></th>
         <td>
            <div style="<?php echo 'width:'.$this->ThumbSize.'px;height:'.$this->ThumbSize.'px;'; ?>overflow:hidden;">
               <?php echo Img('uploads/p'.$this->User->Photo, array('id' => 'preview')); ?>
            </div>
         </td>
      </tr>
   </tbody>
</table>
<?php echo $this->Form->Close('Save');