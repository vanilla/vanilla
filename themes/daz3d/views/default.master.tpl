{dazwrapper_prehead}
<!-- vanilla head start -->
{asset name="Head"}
<!-- vanilla head end -->
{dazwrapper_posthead}
	<div id="Forum">
		<div id="{$BodyID}" class="{$BodyClass}">

			<div id="Frame">
				<div class="Head" id="Head"></div>
				<div id="Body">
					<div class="Row">
						<div class="ForumHeader">
							<ul class="SiteMenu">
								{dashboard_link}
								{discussions_link}
								<li><a href="/vanilla/discussions/bookmarked/">Bookmarks</a></li>
								{inbox_link}
								{custom_menu}
								{profile_link}
							</ul>
							<br class="display_medium_down clearfloat" />
							<div class="Breadcrumbs">
								{breadcrumbs}
							</div>

							<!-- Injecting thread title and/or styling vanilla breadcrumbs to match Shop breadcrumbs -->
							{literal}
							<script>
								$forumThreadTitle = $( "#Item_0 h1" ).text();
								$forumLastBreadcrumb = $( ".Breadcrumbs" ).find( "span" ).last();
								$forumBreadcrumbInsertion = $forumLastBreadcrumb.parent().parent();

								// If on a non-thread page, style the last breadcrumb,
								// else add the thread name to the breadcrumbs	
								if ( $forumThreadTitle == "" ) {
									$forumLastBreadcrumb.addClass( "CurrentForumPage" );
								} else {
									$forumBreadcrumbInsertion.after( "<span class='Crumb'> › </span><span class='CurrentForumPage'>" + $forumThreadTitle + "</span>" );
								}
							</script>
							{/literal}

						</div><div class="clearfloat"></div>
						<div class="Column ContentColumn page_content" id="Content">{asset name="Content"}</div>
					</div>
				</div>
			</div>

		</div>
	</div>
	{asset name="Foot"}
	{event name="AfterBody"}
{dazwrapper_foot}