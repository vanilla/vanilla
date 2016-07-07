<div class="header-block padded">
    <div class="title-block">
        <?php echo anchor(dashboardSymbol('caret-right'), "/embed/forum", 'btn btn-icon btn-return', ['aria-label' => t('Return')]); ?>
        <h1><?php echo t('Universal Forum Embed Code'); ?></h1>
    </div>
</div>
<p><?php echo t('To embed your Vanilla forum into your web site, use the following code.'); ?></p>

<pre id="universal-code">&lt;script type="text/javascript" src="<?php echo Asset('js/embed.js', true); ?>">&lt;/script>
    &lt;noscript>Please enable JavaScript to view the &lt;a
    href="http://vanillaforums.com/?ref_noscript">discussions powered by Vanilla.&lt;/a>&lt;/noscript>
    &lt;div class="vanilla-credit">&lt;a class="vanilla-anchor" href="http://vanillaforums.com">Discussions by &lt;span
    class="vanilla-logo">Vanilla&lt;/span>&lt;/a>&lt;/div>
</pre>
<div class="row form-group">
    <div class="info label-wrap-wide">&uarr; Copy and paste this code into the web page where you want the forum to appear.</div>
    <div class="input-wrap-right">
        <button class="btn btn-secondary btn-copy" data-clipboard-target="#universal-code" data-success-text="<?php echo t('Copied!'); ?>">
            <?php echo t('Copy Embed Code to Clipboard'); ?>
        </button>
    </div>
</div>
