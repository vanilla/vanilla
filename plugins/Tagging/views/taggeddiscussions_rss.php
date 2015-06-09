<?php if (!defined('APPLICATION')) exit(); ?>
    <description><?php echo Gdn_Format::text($this->Head->title()); ?></description>
    <language><?php echo Gdn::config('Garden.Locale', 'en-US'); ?></language>
    <atom:link href="<?php echo url('discussions/tagged'.urlencode($this->data('Tag')).'/feed.rss'); ?>" rel="self"
               type="application/rss+xml"/>
<?php
foreach ($this->DiscussionData->Result() as $Discussion) {
    ?>
    <item>
        <title><?php echo Gdn_Format::text($Discussion->Name); ?></title>
        <link><?php echo htmlspecialchars(url('/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::url($Discussion->Name), true)); ?></link>
        <pubDate><?php echo date(DATE_RSS, Gdn_Format::ToTimeStamp($Discussion->DateInserted)); ?></pubDate>
        <dc:creator><?php echo Gdn_Format::text($Discussion->FirstName); ?></dc:creator>
        <guid isPermaLink="false"><?php echo $Discussion->DiscussionID.'@'.Url('/discussions'); ?></guid>
        <description><![CDATA[<?php echo Gdn_Format::to($Discussion->Body, $Discussion->Format); ?>]]></description>
    </item>
<?php
}
