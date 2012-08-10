<?php if (!defined('APPLICATION')) exit();

echo '<h2 class="H">'.T('Edit My Thumbnail').'</h2>';
echo $this->Form->Errors();
echo $this->Form->Open(array('class' => 'Thumbnail'));
?>
<div class="Info"><?php
   echo T('Define Thumbnail', 'Click and drag across the picture to define your thumbnail.');
?></div>
<table>
   <thead>
      <tr>
         <td><?php echo T('Picture'); ?></td>
         <td><?php echo T('Thumbnail'); ?></td>
      </tr>
   </thead>
   <tbody>
      <tr>
         <td>
            <?php echo Img(Gdn_Upload::Url(ChangeBasename($this->User->Photo,'p%s')), array('id' => 'cropbox')); ?>
         </td>
         <td>
            <div style="<?php echo 'width:'.$this->ThumbSize.'px;height:'.$this->ThumbSize.'px;'; ?>overflow:hidden;">
               <?php echo Img(Gdn_Upload::Url(ChangeBasename($this->User->Photo, 'p%s')), array('id' => 'preview')); ?>
            </div>
         </td>
      </tr>
   </tbody>
</table>

<?php echo $this->Form->Close('Save', '', array('class' => 'Button Primary'));