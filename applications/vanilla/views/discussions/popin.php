<?php if (!defined('APPLICATION')) exit(); ?>
<ul class="PopList Popin">
    <?php
    if (count($this->data('Discussions'))):
        ?>
        <li class="Item Title">
            <?php echo wrap($this->data('Title'), 'strong'); ?>
        </li>
        <?php
        foreach ($this->data('Discussions') as $Row):
            ?>
            <li class="Item" rel="<?php echo url($Row->Url); ?>">
                <div class="Author Photo"><?php echo userPhoto($Row, ['Px' => 'First']); ?></div>
                <div class="ItemContent">
                    <b class="Subject"><?php echo anchor($Row->Name, $Row->Url.'#latest'); ?></b>

                    <div class="Meta">
                        <?php
                        echo ' <span class="MItem">'.plural($Row->CountComments, '%s comment', '%s comments').'</span> ';

                        if ($Row->CountUnreadComments === TRUE) {
                            echo ' <strong class="HasNew"> '.t('new').'</strong> ';
                        } elseif ($Row->CountUnreadComments > 0) {
                            echo ' <strong class="HasNew"> '.plural($Row->CountUnreadComments, '%s new', '%s new plural').'</strong> ';
                        }

                        echo ' <span class="MItem">'.Gdn_Format::date($Row->DateLastComment).'</span> ';
                        ?>
                    </div>
                </div>
            </li>
        <?php endforeach; ?>
        <li class="Item Center">
            <?php
            echo anchor(t('All Bookmarks'), '/discussions/bookmarked');
            ?>
        </li>
    <?php else: ?>
        <li class="Item Empty Center"><?php echo t('You do not have any bookmarks.', 'You do not have any bookmarks.'); ?></li>
    <?php endif; ?>
</ul>
