<?php if (!defined('APPLICATION')) exit(); ?>
<description><?php echo htmlspecialchars($this->data('Title')); ?></description>
<atom:link href="<?php echo htmlspecialchars(url($this->SelfUrl, TRUE)); ?>" rel="self" type="application/rss+xml" />

<?php foreach ($this->data('Data', []) as $Row): ?>
   <item>
      <title><?php echo Gdn_Format::text(getValue('Name', $Row)); ?></title>
      <link><?php echo $Row['Url']; ?></link>
      <pubDate><?php echo date('r', Gdn_Format::toTimeStamp($Row['DateInserted'])); ?></pubDate>
      <dc:creator><?php echo Gdn_Format::text($Row['InsertName']); ?></dc:creator>
      <guid isPermaLink="false"><?php echo "{$Row['RecordID']}@{$Row['RecordType']}"; ?></guid>
      <description><![CDATA[<?php echo Gdn_Format::rssHtml($Row['Body'], $Row['Format']); ?>]]></description>
   </item>
<?php endforeach; ?>
