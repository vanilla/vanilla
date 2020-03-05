{capture name='_smarty_debug' assign=debug_output}
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
    <head>
        <title>Smarty Debug Console</title>
        <style type="text/css">
            {literal}
            body, h1, h2, h3, td, th, p {
                font-family: sans-serif;
                font-weight: normal;
                font-size: 0.9em;
                padding: 0;
            }

            h1 {
                margin: 0;
                text-align: left;
                padding: 8px;
                color: black;
                font-weight: bold;
                font-size: 1.2em;
            }

            h2 {
                color: white;
                text-align: left;
                font-weight: bold;
                padding: 2px;
                border-top: 1px solid black;
            }
            h3 {
                text-align: left;
                font-weight: bold;
                color: #333434;
                font-size: 0.7em;
                padding: 0;
                margin: 0 0 8px;
            }

            body {
                margin: 0;
            }

            p {
                margin: 0;
                font-style: italic;
                text-align: center;
            }

            table {
                width: 100%;
                border-spacing: 0;
            }

            th, td {
                font-family: monospace;
                vertical-align: top;
                text-align: left;
                padding: 12px;
            }

            th {
                text-transform: uppercase;
            }

            td {
                color: green;
            }

            .odd {
                background-color: #eeeeee;
            }

            .even {
                background-color: #fafafa;
            }

            .exectime {
                font-size: 0.8em;
                font-style: italic;
            }

            #bold div {
                color: black;
                font-weight: bold;
            }
            #blue h3 {
                color: blue;
            }
            #normal div {
                color: black;
                font-weight: normal;
            }
            #table_assigned_vars th {
                color: #333434;
                font-weight: bold;
            }

            #table_config_vars th {
                color: maroon;
            }

            {/literal}
        </style>
    </head>
    <body>

    <h1>Debug Console</h1>

    <table id="table_assigned_vars">
        <thead>
            <tr>
                <th>Variable</th>
                <th>Value</th>
            </tr>
        </thead>
        <tbody>
        {foreach $assigned_vars as $vars}
            <tr class="{if $vars@iteration % 2 eq 0}odd{else}even{/if}">
                <td>${$vars@key}
                    {if isset($vars['nocache'])}<b>Nocache</b><br />{/if}
                </td>
                <td>{$vars['value']|debug_print_var:10:80 nofilter}</td>
         {/foreach}
        </tbody>
    </table>
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
