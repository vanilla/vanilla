<?php if (!defined('APPLICATION')) exit();
echo '<h1>'.$this->data('Title', t('Awaiting Moderation')).'<h1>';
?>
<div class="Info">
    <?php
    echo '<div>', t("Your post will appear once it's been approved."), '</div>';
    ?>
</div>
