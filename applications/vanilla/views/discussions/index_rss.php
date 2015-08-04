<?php if (!defined('APPLICATION')) exit(); ?>
    <description><?php echo Gdn_Format::text($this->Head->title()); ?></description>
    <language><?php echo Gdn::config('Garden.Locale', 'en-US'); ?></language>
    <atom:link href="<?php echo htmlspecialchars(url($this->SelfUrl, true)); ?>" rel="self" type="application/rss+xml"/>
<?php
foreach ($this->DiscussionData->result() as $Discussion) {
    ?>
    <item>
        <title><?php echo Gdn_Format::text($Discussion->Name); ?></title>
        <link><?php echo $Discussion->Url; ?></link>
        <pubDate><?php echo date('r', Gdn_Format::ToTimeStamp($Discussion->DateInserted)); ?></pubDate>
        <category><?php echo Gdn_Format::text($Discussion->Category); ?></category>
        <dc:creator><?php echo Gdn_Format::text($Discussion->FirstName); ?></dc:creator>
        <guid isPermaLink="false"><?php echo $Discussion->DiscussionID.'@'.Url('/discussions'); ?></guid>
        <description><![CDATA[<?php echo Gdn_Format::RssHtml($Discussion->Body, $Discussion->Format); ?>]]>
        </description>
    </item>
<?php
}
