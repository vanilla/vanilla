<?php if (!defined('APPLICATION')) exit();

echo '<h2>'.T('Edit My Thumbnail').'</h2>';
echo $this->Form->Errors();
echo $this->Form->Open(array('class' => 'Thumbnail'));
?>
<div class="Info"><?php
   echo T('Drag around and resize the square below to define your thumbnail icon.');
?></div>
<table>
   <thead>
      <tr>
         <th><?php echo T('Original'); ?></th>
         <td><?php echo T('Thumbnail'); ?></td>
      </tr>
   </thead>
   <tbody>
      <tr>
         <th>
            <?php echo Img('uploads/'.ChangeBasename($this->User->Photo,'p%s'), array('id' => 'cropbox')); ?></th>
         <td>
            <div style="<?php echo 'width:'.$this->ThumbSize.'px;height:'.$this->ThumbSize.'px;'; ?>overflow:hidden;">
               <?php echo Img('uploads/'.ChangeBasename($this->User->Photo, 'p%s'), array('id' => 'preview')); ?>
            </div>
         </td>
      </tr>
   </tbody>
</table>
<?php echo $this->Form->Close('Save');