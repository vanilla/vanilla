<?php use Vanilla\Theme\BoxThemeShim;

if (!defined('APPLICATION')) exit();
require_once Gdn::controller()->fetchViewLocation('helper_functions', 'modules', 'vanilla');
$Content = $this->data('Content');

if (!empty($Content) || $this->getShowIfNoResults()) :
    ?>
    <div class="Box BoxPromoted">
        <?php BoxThemeShim::startHeading(); ?>
        <?php echo panelHeading($this->getTitle()); ?>
        <?php BoxThemeShim::endHeading(); ?>
        <div class="PanelInfo DataList <?php BoxThemeShim::activeHtml('pageBox'); ?>">
            <?php
            if ($Content):
                if ($this->Group):
                    $Content = array_chunk($Content, $this->Group);
                endif;
                foreach ($Content as $ContentChunk):
                    if ($this->Group):
                        echo '<div class="PromotedGroup">';
                        foreach ($ContentChunk as $ContentItem):
                            writePromotedContent($ContentItem, $this);
                        endforeach;
                        echo '</div>';
                    else:
                        writePromotedContent($ContentChunk, $this);
                    endif;
                endforeach;
            endif;
            ?>
        </div>
    </div>
    <?php
endif;
?>
