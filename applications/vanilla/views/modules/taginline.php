<?php if (!defined('APPLICATION')) exit(); ?>
        <div class="InlineTags Meta">
            <?php echo t('Tagged'); ?>:
            <ul>
                <?php foreach ($this->_TagData->resultArray() as $tag) : ?>
                    <?php if ($tag['Name'] != '') :?>
                        <li><?php
                            echo anchor(
                                htmlspecialchars(tagFullName($tag)),
                                tagUrl($tag, '', '/'),
                                ['class' => 'Tag_'.str_replace(' ', '_', $tag['Name'])]
                            );
                            ?></li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>