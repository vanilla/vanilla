<?php if (!defined('APPLICATION')) exit(); ?>
   <description><?php echo Gdn_Format::Text($this->Head->Title()); ?></description>
   <language><?php echo Gdn::Config('Garden.Locale', 'en-US'); ?></language>
   <link href="<?php echo htmlspecialchars(Url($this->SelfUrl, TRUE)); ?>" rel="self" type="application/rss+xml" />
   <link href="<?php echo htmlspecialchars(Url('/', TRUE)); ?>" rel="alternate" type="text/html" />
<?php
foreach ($this->DiscussionData->Result() as $Discussion) {
?>
   <entry>
      <title type="html"><![CDATA[<?php echo Gdn_Format::RssHtml($Discussion->Name); ?>]]></title>
      <link href="<?php echo $Discussion->Url; ?>"/>
      <id><?php echo $Discussion->DiscussionID . '@' . Url('/discussions'); ?></id>
      <author>
        <name><?php echo Gdn_Format::Text($Discussion->FirstName); ?></name>
        <uri><?php echo htmlspecialchars(Url('/profile/' . $Discussion->InsertUserID .'/'. $Discussion->FirstName, TRUE)); ?></uri>
      </author>
      <updated><?php echo date('c', Gdn_Format::ToTimeStamp($Discussion->DateLastComment)); ?></updated>
      <summary><![CDATA[<?php echo Gdn_Format::RssHtml($Discussion->Body, $Discussion->Format); ?>]]></summary>
   </entry>
<?php
}
