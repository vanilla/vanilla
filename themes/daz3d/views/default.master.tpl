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
						<div class="ForumHeader crumbs daz-sticky" data-daz-sticky-side="top">
							<div class="SiteMenuWrapper">
								<i class="fd-gears daz-toggler" data-toggleelem="#ForumMenu"></i>
								<ul id="ForumMenu" class="SiteMenu">
									{dashboard_link}
									{discussions_link}
									<li><a href="/vanilla/discussions/bookmarked/">Bookmarks</a></li>
									{inbox_link}
									{custom_menu}
									{profile_link}
								</ul>
							</div>
							<div class="Breadcrumbs">
								<a href="#" id="DropdownLink" class="CurrentForumPage daz-toggler" data-toggleelem="span.Breadcrumbs" data-togglestrip="hide"></a>
								{breadcrumbs}
							</div>

						</div><div class="clearfloat"></div>
						<div class="Column ContentColumn page_content self-clearing" id="Content">{asset name="Content"}</div>

						{literal}
						<script>

							//variables for crumb styling
							var forumThreadTitle = $( "#Item_0 h1" ).text();
							//shorten long thread titles
							if (forumThreadTitle.length >= 34) {
								forumThreadTitle = forumThreadTitle.slice(0,30) + "&hellip;";
							};

							var $forumLastBreadcrumb = $( ".Breadcrumbs" ).find( "span" ).last();
							var $forumBreadcrumbInsertion = $forumLastBreadcrumb.parent().parent();

							if ( forumThreadTitle == "" ) { //if on a non-thread page...
								// ...style last breadcrumb and add it to the mobile dropdown
								$forumLastBreadcrumb.addClass( "CurrentForumPage" );
								$( "#DropdownLink" ).prepend( $forumLastBreadcrumb.text() );
							} else { // else on a thread page, insert the thread title in the breadcrumbs
								$forumBreadcrumbInsertion.after( "<span class='Crumb'> › </span><span class='CurrentForumPage'>" + forumThreadTitle + "</span>" );
								$( "#DropdownLink" ).prepend(forumThreadTitle);
							};

						</script>
						{/literal}

					</div>
				</div>
			</div>

		</div>
	</div>
	{asset name="Foot"}
	{event name="AfterBody"}
{dazwrapper_foot}