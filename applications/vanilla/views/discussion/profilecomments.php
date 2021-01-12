<?php if (!defined('APPLICATION')) exit();
foreach ($this->data('Comments') as $Comment) {
    $Permalink = commentUrl($Comment);
    $User = userBuilder($Comment, 'Insert');
    $this->EventArguments['User'] = $User;
    /** @var \Vanilla\Formatting\Html\HtmlSanitizer $htmlSanitizer */
    $htmlSanitizer = Gdn::getContainer()->get(\Vanilla\Formatting\Html\HtmlSanitizer::class);
    $sanitizedBody = Gdn::formatService()->renderPlainText($Comment->Body, $Comment->Format);
    $sanitizedBody = $htmlSanitizer->filter($sanitizedBody);
    ?>
    <li id="<?php echo 'Comment_'.$Comment->CommentID; ?>" class="Item">
        <?php $this->fireEvent('BeforeItemContent'); ?>
        <div class="ItemContent">
            <div class="Message">
                <?php
                    echo sliceString($sanitizedBody, 250);
                ?>
            </div>
            <div class="Meta">
                <span class="MItem"><?php echo t('Comment in', 'in').' '; ?>
                    <b><?php echo anchor(Gdn_Format::text($Comment->DiscussionName), $Permalink, '', ['rel' => 'nofollow']); ?></b></span>
                <span class="MItem"><?php printf(t('Comment by %s'), userAnchor($User)); ?></span>
                <span class="MItem"><?php echo anchor(Gdn_Format::date($Comment->DateInserted), $Permalink); ?></span>
            </div>
        </div>
    </li>
<?php
}
