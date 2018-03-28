<?php if (!defined('APPLICATION')) exit();
require_once $this->fetchViewLocation('helper_functions');

if (!isset($this->Prefix))
    $this->Prefix = 'Discussion';
?>
<div class="Box BoxDiscussions">
    <?php echo panelHeading(t('Recent Discussions')); ?>
    <ul class="PanelInfo PanelDiscussions DataList">
        <?php
        foreach ($this->data('Discussions')->result() as $Discussion) {
            writeModuleDiscussion($Discussion, $this->Prefix, $this->getShowPhotos());
        }
        if ($this->data('Discussions')->numRows() >= $this->Limit) {
            ?>
            <li class="ShowAll"><?php echo anchor(t('More…'), 'discussions'); ?></li>
        <?php } ?>
    </ul>
</div>
