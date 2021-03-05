<?php if (!defined('APPLICATION')) exit();
    use Vanilla\Theme\BoxThemeShim;
?>

<div class="Box Tags">
    <?php
        BoxThemeShim::startHeading();
        echo panelHeading(t($this->ParentID > 0 ? 'Tagged' : 'Popular Tags'));
        BoxThemeShim::endHeading();
    ?>
    <ul class="TagCloud <?php BoxThemeShim::activeHtml("pageBox"); ?>">
        <?php foreach ($this->_TagData->result() as $tag) :?>
            <?php if ($tag['Name'] != '') :?>
                <li class="TagCloud-Item">
                <?php echo anchor(
                        htmlspecialchars(tagFullName($tag)).' '.wrap(number_format($tag['CountDiscussions']), 'span', ['class' => 'Count']),
                        tagUrl($tag, '', '/'),
                        ['class' => 'Tag_'.str_replace(' ', '_', $tag['Name'])]
                    );
                    ?></li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</div>