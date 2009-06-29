<h1>{"Search results for '%s'"|translate|replace:"%s":$SearchTerm}</h1>

{$Pager->ToString('less')}
<ul class="DataList SearchResults">
{foreach from=$SearchResults item=Row}
	<li class="Row">
		<ul>
			<li class="Title">
				<h3><a href="{url dest=$Row.Url}">{$Row.Title|escape}</a></h3>
				<a href="{url dest=$Row.Url}">{$Row.Summary|escape}</a>
			</li>
			<li class="Meta">
				<span>{"Comment by"|translate} <a href="{url dest='/profile/'|cat:$Row.Name}">{$Row.Name}</a></span>
				<span>{$Row.DateInserted|date}</span>
				<span><a href="{url dest=$Row.Url}">{"permalink"|translate}</a></span>
			</li>
		</ul>
	</li>
{foreachelse}
	<li>{"Your search returned no results."|translate}</li>
{/foreach}
</ul>
{$Pager->Render()}