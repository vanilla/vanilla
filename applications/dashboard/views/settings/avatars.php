<?php if (!defined('APPLICATION')) exit();
$session = Gdn::session();
$defaultAvatar = $this->data('DefaultAvatar');
$thumbsize = $this->data('ThumbSize');
?>
<h1><?php echo t('Avatars'); ?></h1>

<?php
echo wrap(t('DefaultAvatarDescription', 'Choose your default avatar.'), 'div', array('class' => 'Info')
);

echo $this->Form->open(array('enctype' => 'multipart/form-data'));
echo $this->Form->errors();
?>
<ul>
    <li>
        <?php
        if ($defaultAvatar) {
            echo wrap(t('DefaultAvatarBrowse', 'Upload a new default avatar.'), 'span', array('class' => 'Info'));
        }
        echo $this->Form->input('DefaultAvatar', 'file');
        if ($defaultAvatar) {
            echo '</li><li>'.wrap(anchor(t('Remove Default Avatar'), '/dashboard/settings/removedefaultavatar/'.$session->TransientKey(), 'SmallButton'), 'span', array('style' => 'padding: 10px 0;')).'</li>';
        }
        ?>
    </li>
</ul>
<?php
if ($defaultAvatar) { ?>
    <table style='table-layout: fixed;'>
        <thead>
        <tr>
            <?php
            echo '<td>'.t('Avatar').'</td>';
            echo '<td>'.t('Thumbnail').'</td>';
            ?>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td>
                <?php
                echo img(changeBasename($defaultAvatar, 'p%s'), array('id' => 'cropbox'));
                echo '<label>'.t('Define Thumbnail', 'Click and drag across the picture to define your thumbnail.').'</label>';
                ?>
            </td>
            <td>
                <div style="<?php echo 'width:'.$thumbsize.'px;height:'.$thumbsize.'px;'; ?>overflow:hidden;">
                    <?php
                    echo img(changeBasename($defaultAvatar, 'n%s'), array('class' => 'js-thumbnail thumbnail', 'style' => 'min-width: '.$thumbsize.'px; min-height: '.$thumbsize.'px;'));
                    echo img(changeBasename($defaultAvatar, 'p%s'), array('id' => 'preview'));
                    ?>
                </div>
            </td>
        </tr>
        </tbody>
    </table>
<?php } ?>
<div>
    <?php echo $this->Form->close('Save', '', array('class' => 'Button Primary', 'style' => 'margin-top: 20px;')); ?>
</div>
