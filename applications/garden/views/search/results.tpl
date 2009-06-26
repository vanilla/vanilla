<h1>{"Search Results"|translate}</h1>

{$Pager->ToString('less')}
<ul class="Results">
{foreach from=$SearchResults item=Row}
	<li class="Row">
		<ul>
			<li class="Title"><h3><a href="{url dest=$Row.Url}">{$Row.Title|escape}</a></h3></li>
			<li class="Summary">{$Row.Summary|escape}</li>
			<li class="Meta">
				<span><a href="{url dest=$Row.Url}">{url dest=$Row.Url}</a></span>
				<span>{"by"|translate} <a href="{url dest='/profile/'|cat:$Row.Name}">{$Row.Name}</a></span>
				<span>{$Row.DateInserted|date}</span>
			</li>
		</ul>
	</li>
{foreachelse}
<p>{"Your search returned no results."|translate}</p>
{/foreach}
</ul>
{$Pager->Render()}