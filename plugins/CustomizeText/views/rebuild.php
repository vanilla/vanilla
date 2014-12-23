<?php if (!defined('APPLICATION')) exit();

// ajax request common pages to fill the locale file with translations to edit.
$Urls = array(
	'/vanilla/discussions/index',
	'/vanilla/discussions/bookmarked',
	'/vanilla/discussions/mine',
	'/vanilla/drafts',
	'/vanilla/discussion/1/test', // try to hit a discussion
	'/vanilla/post/discussion',
	'/vanilla/post/comment',
	'/dashboard/activity',
	'/dashboard/search',
	'/dashboard/profile/notifications',
	'/dashboard/profile/activity/1/test', // try to hit an admin user
	'/dashboard/profile/activity/2/test', // try to hit a non-admin user
	'/dashboard/profile/discussions/1/test',
	'/dashboard/profile/discussions/2/test',
	'/dashboard/profile/comments/1/test',
	'/dashboard/profile/comments/2/test',
	'/dashboard/profile/picture',
	'/dashboard/profile/edit',
	'/dashboard/profile/password',
	'/dashboard/profile/preferences',
	'/conversations/messages/inbox',
	'/conversations/messages/1', // try to hit a message
	'/conversations/messages/add',
	'/dashboard/entry/signin',
	'/dashboard/entry/register',
	'/dashboard/entry/passwordrequest'
);
array_map('Url', $Urls);
$Locale = Gdn::Locale();
$Definitions = $Locale->GetDeveloperDefinitions();
$CountDefinitions = count($Definitions);
?>
<h1><?php echo T('Customize Text'); ?></h1>
<div class="Info">
   <?php
		echo 'There are currently <span class="CountDefinitions">'. $CountDefinitions . '</span> text definitions available for editing.';
   ?>
	<p><em><span class="Loading"></span> Searching for more text definitions.</em></p>
</div>
<script type="text/javascript" language="javascript">
jQuery(document).ready(function($) {
	crawlUrl = function(index, arr) {
		var url = arr[index];
		gdn.informMessage('Crawling: '+url, {'id':'crawlstate'});
		$.ajax({
			url: gdn.url(url),
			complete: function(data) {
				if (index+1 < arr.length) {
					// hit the next url
					crawlUrl(index+1, arr);
				} else {
					// Finished
					gdn.informMessage('Crawling complete!', {'id':'crawlstate'});
					// $('#Content').get('<?php echo Url('/settings/customizetext/rebuild?DeliveryType=VIEW'); ?>');
					$.ajax({
						url: '<?php echo Url('/settings/customizetext/rebuildcomplete?DeliveryType=VIEW'); ?>',
						success: function(data) {
							$('#Content').html(data);
						}
					})
				}
			}
		});
	};		
	var urls = <?php echo json_encode($Urls); ?>;
	crawlUrl(0, urls);
});
</script>