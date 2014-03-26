<?php if (!defined('APPLICATION')) exit(); ?>
   <description><?php echo Gdn_Format::Text($this->Head->Title()); ?></description>
   <language><?php echo Gdn::Config('Garden.Locale', 'en-US'); ?></language>
   <atom:link href="<?php echo htmlspecialchars(Url($this->SelfUrl, TRUE)); ?>" rel="self" type="application/rss+xml" />
<?php
$Activities = $this->Data('Activities', array());
foreach ($Activities as $Activity) {
   $Author = UserBuilder($Activity, 'Activity');
?>
   <item>
      <title><?php echo Gdn_Format::Text(GetValue('Headline', $Activity)); ?></title>
      <link><?php echo Url('/activity', TRUE); ?></link>
      <pubDate><?php echo date('r', Gdn_Format::ToTimeStamp(GetValue('DateUpdated', $Activity))); ?></pubDate>
      <dc:creator><?php echo Gdn_Format::Text($Author->Name); ?></dc:creator>
      <guid isPermaLink="false"><?php echo GetValue('ActivityID', $Activity) . '@' . Url('/activity'); ?></guid>
      <?php if ($Story = GetValue('Story', $Activity)) : ?>
      <description><![CDATA[<?php echo Gdn_Format::RssHtml($Story, GetValue('Format', $Activity)); ?>]]></description>
      <?php endif; ?>
   </item>
<?php
}