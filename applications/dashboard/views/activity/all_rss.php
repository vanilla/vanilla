<?php if (!defined('APPLICATION')) exit(); ?>
    <description><?php echo Gdn_Format::text($this->Head->title()); ?></description>
    <language><?php echo Gdn::config('Garden.Locale', 'en-US'); ?></language>
    <atom:link href="<?php echo htmlspecialchars(url($this->SelfUrl, true)); ?>" rel="self" type="application/rss+xml"/>
<?php
$Activities = $this->data('Activities', array());
foreach ($Activities as $Activity) {
    $Author = UserBuilder($Activity, 'Activity');
    ?>
    <item>
        <title><?php echo Gdn_Format::text(val('Headline', $Activity)); ?></title>
        <link><?php echo url('/activity', true); ?></link>
        <pubDate><?php echo date('r', Gdn_Format::ToTimeStamp(val('DateUpdated', $Activity))); ?></pubDate>
        <dc:creator><?php echo Gdn_Format::text($Author->Name); ?></dc:creator>
        <guid isPermaLink="false"><?php echo val('ActivityID', $Activity).'@'.Url('/activity'); ?></guid>
        <?php if ($Story = val('Story', $Activity)) : ?>
            <description><![CDATA[<?php echo Gdn_Format::RssHtml($Story, val('Format', $Activity)); ?>]]>
            </description>
        <?php endif; ?>
    </item>
<?php
}
