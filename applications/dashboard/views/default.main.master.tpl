{extends file="default.master.tpl"}
{block name="_body"}
    <div id="Body">
        <div class="_container">
            <div class="breadcrumbsWrapper">{breadcrumbs}</div>
        </div>
        <div class="_pageContents">
            <div class="_fullBackgroundContainer"></div>
                {asset name="Header"}
                <div class="_messages"></div>
                {asset name="Content"}
                {asset name="Foot"}
                <div class="_stickyBottom"></div>
            <div class="_overlays"></div>
        </div>
    </div>
{/block}
