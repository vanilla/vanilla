<?php if (!defined('APPLICATION')) exit(); ?>
   <description><?php echo Gdn_Format::Text($this->Head->Title()); ?></description>
   <language><?php echo Gdn::Config('Garden.Locale', 'en-US'); ?></language>
   <atom:link href="<?php echo Url('discussions/tagged'.urlencode($this->Data('Tag')).'/feed.rss'); ?>" rel="self" type="application/rss+xml" />
<?php
foreach ($this->DiscussionData->Result() as $Discussion) {
   ?>
   <item>
      <title><?php echo Gdn_Format::Text($Discussion->Name); ?></title>
      <link><?php echo Url('/discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::Url($Discussion->Name), TRUE); ?></link>
      <pubDate><?php echo date(DATE_RSS, Gdn_Format::ToTimeStamp($Discussion->DateInserted)); ?></pubDate>
      <dc:creator><?php echo Gdn_Format::Text($Discussion->FirstName); ?></dc:creator>
      <guid isPermaLink="false"><?php echo $Discussion->DiscussionID . '@' . Url('/discussions'); ?></guid>
      <description><![CDATA[<?php echo Gdn_Format::To($Discussion->Body, $Discussion->Format); ?>]]></description>
   </item>
   <?php
}