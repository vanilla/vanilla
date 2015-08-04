<?php if (!defined('APPLICATION')) exit();
require_once Gdn::controller()->fetchViewLocation('helper_functions', 'modules', 'vanilla');

?>
<div class="Box BoxPromoted">
    <?php echo panelHeading(t('Promoted Content')); ?>
    <div class="PanelInfo DataList">
        <?php
        $Content = $this->data('Content');
        $ContentItems = sizeof($Content);

        if ($Content):

            if ($this->Group):
                $Content = array_chunk($Content, $this->Group);
            endif;

            foreach ($Content as $ContentChunk):
                if ($this->Group):
                    echo '<div class="PromotedGroup">';
                    foreach ($ContentChunk as $ContentItem):
                        WritePromotedContent($ContentItem, $this);
                    endforeach;
                    echo '</div>';
                else:
                    WritePromotedContent($ContentChunk, $this);
                endif;
            endforeach;

        endif;
        ?>
    </div>
</div>
