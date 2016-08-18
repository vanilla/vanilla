<div class="padded">
    <?php echo t('Define Thumbnail', 'Click and drag across the picture to define your thumbnail.');?>
</div>
<div class="box-crop">
    <div class="box-image box-source">
        <?php
        echo '<div class="crop-label">'.t('Picture').'</div>';
        echo img($this->getSourceImageUrl(), array('id' => 'cropbox')); ?>
    </div>
    <div class="box-image box-cropped-image">
        <?php echo '<div class="crop-label">'.t('Thumbnail').'</div>'; ?>
        <div class="thumbnail-preview padded-left" style="<?php echo 'width:'.$this->getWidth().'px;height:'.$this->getHeight().'px;'; ?>overflow:hidden;">
            <?php
            echo img($this->getExistingCropUrl(), array('id' => 'current-crop', 'style' => 'min-width: '.$this->getWidth().'px; min-height: '.$this->getHeight().'px;'));
            echo img($this->getSourceImageUrl(), array('id' => 'preview')); ?>
        </div>
        <?php
        if ($this->saveButton) {
            echo $this->form->button('Save Thumbnail', array('id' => 'save-crop', 'class' => 'Button btn btn-secondary Primary Hidden', 'style' => 'margin-top: 20px;'));
        }
        ?>
    </div>
</div>
