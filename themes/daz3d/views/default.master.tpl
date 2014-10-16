{dazwrapper_prehead}
<!-- vanilla head start -->
{asset name="Head"}
<!-- vanilla head end -->
{dazwrapper_posthead}
	<div id="Forum">
		<div id="{$BodyID}" class="{$BodyClass}">

			<div id="Frame">
				<div class="Head" id="Head">
					<div class="Row">
						<a href="{link path="/"}">{logo}</a>
						<ul class="SiteMenu">
							{dashboard_link}
							{discussions_link}
							{activity_link}
							{inbox_link}
							{custom_menu}
							{profile_link}
							{signinout_link}
						</ul>
					</div>
				</div>
				<div id="Body">
					<div class="Row">
						<div class="BreadcrumbsWrapper P">{breadcrumbs}</div>
						<div class="Column PanelColumn" id="Panel">
							{module name="MeModule" CssClass="FlyoutRight"}
							{asset name="Panel"}
							<div class="SiteSearch">{searchbox}</div>
						</div>
						<div class="Column ContentColumn" id="Content">{asset name="Content"}</div>
					</div>
				</div>
			</div>

		</div>
	</div>
	{asset name="Foot"}
	{event name="AfterBody"}
{dazwrapper_foot}
