<?php if (!defined('APPLICATION')) exit();

echo '<h1 class="H">'.t('Edit My Thumbnail').'</h1>';
echo $this->Form->errors();
echo $this->Form->open(array('class' => 'Thumbnail'));
?>
    <div class="Info"><?php
        echo t('Define Thumbnail', 'Click and drag across the picture to define your thumbnail.');
        ?></div>
    <table>
        <thead>
        <tr>
            <td><?php echo t('Picture'); ?></td>
            <td><?php echo t('Thumbnail'); ?></td>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                <?php echo img(Gdn_Upload::url(changeBasename($this->User->Photo, 'p%s')), array('id' => 'cropbox')); ?>
            </td>
            <td>
                <div
                    style="<?php echo 'width:'.$this->ThumbSize.'px;height:'.$this->ThumbSize.'px;'; ?>overflow:hidden;">
                    <?php echo img(Gdn_Upload::url(changeBasename($this->User->Photo, 'p%s')), array('id' => 'preview')); ?>
                </div>
            </td>
        </tr>
        </tbody>
    </table>

<?php echo $this->Form->close('Save', '', array('class' => 'Button Primary'));
