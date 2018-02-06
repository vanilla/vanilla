<div class="padded change-picture-instructions">
    <?php echo t('Define Thumbnail', 'Click and drag across the picture to define your thumbnail.');?>
</div>
<div class="box-crop clearfix">
    <div class="box-image box-source pull-left padded-right padded-bottom">
        <?php
        echo img($this->getSourceImageUrl(), ['id' => 'cropbox']); ?>
    </div>
    <div class="box-image box-cropped-image">
        <div class="thumbnail-preview" style="<?php echo 'width:'.$this->getWidth().'px;height:'.$this->getHeight().'px;'; ?>overflow:hidden;">
            <?php
            echo img($this->getExistingCropUrl(), ['id' => 'current-crop', 'style' => 'width: '.$this->getWidth().'px; height: '.$this->getHeight().'px;']);
            echo img($this->getSourceImageUrl(), ['id' => 'preview']); ?>
        </div>
        <?php
        if ($this->saveButton) {
            echo $this->form->button('Save Thumbnail', ['id' => 'save-crop', 'class' => 'Button btn btn-secondary Primary Hidden', 'style' => 'margin-top: 20px;']);
        }
        ?>
    </div>
</div>
