{capture name='_smarty_debug' assign=debug_output}
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <title>Debug Console</title>
        <style type="text/css">
            {literal}
            body, h1, h2, h3, td, th, p {
                font-family: sans-serif;
                margin: 1em;
                padding: 0;
            }
            {/literal}
        </style>
    </head>
    <body>
    {literal}{debug} has been disabled, use {debug_vars} instead.{/literal}
    </body>
    </html>
{/capture}
<script type="text/javascript">
    {$id = '__Smarty__'}
    {if $display_mode}{$id = "$offset$template_name"|md5}{/if}
    _smarty_console = window.open("", "console{$id}", "width=1024,height=600,left={$offset},top={$offset},resizable,scrollbars=yes");
    _smarty_console.document.write("{$debug_output|escape:'javascript' nofilter}");
    _smarty_console.document.close();
</script>
