<?php if (!defined('APPLICATION')) exit(); ?>
    <description><?php echo Gdn_Format::text($this->Head->title()); ?></description>
    <language><?php echo Gdn::config('Garden.Locale', 'en-US'); ?></language>
    <link href="<?php echo htmlspecialchars(url($this->SelfUrl, true)); ?>" rel="self" type="application/rss+xml"/>
    <link href="<?php echo htmlspecialchars(url('/', true)); ?>" rel="alternate" type="text/html"/>
<?php
foreach ($this->DiscussionData->result() as $Discussion) {
    ?>
    <entry>
        <title type="html"><![CDATA[<?php echo Gdn_Format::RssHtml($Discussion->Name); ?>]]></title>
        <link href="<?php echo $Discussion->Url; ?>"/>
        <id><?php echo $Discussion->DiscussionID.'@'.Url('/discussions'); ?></id>
        <author>
            <name><?php echo Gdn_Format::text($Discussion->FirstName); ?></name>
            <uri><?php echo htmlspecialchars(url('/profile/'.$Discussion->InsertUserID.'/'.$Discussion->FirstName, true)); ?></uri>
        </author>
        <updated><?php echo date('c', Gdn_Format::ToTimeStamp($Discussion->DateLastComment)); ?></updated>
        <summary><![CDATA[<?php echo Gdn_Format::RssHtml($Discussion->Body, $Discussion->Format); ?>]]></summary>
    </entry>
<?php
}
