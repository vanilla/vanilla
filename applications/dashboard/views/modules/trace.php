<div class="Trace" aria-hidden="true">
    <style>
        .Trace {
            width: 100%;
        }

        .Trace table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .Trace td {
            border-top: solid 1px #efefef;
            border-bottom: solid 1px #efefef;
            padding: 4px;
            vertical-align: top;
        }

        .Trace pre {
            margin: 0;
            overflow: auto;
        }

        .Trace .TagColumn {
            width: 100px;
        }

        .Trace .Tag-info {
            background: #00A6FF;
            color: #fff;
        }

        .Trace .Tag-warning {
            background: #FF9000;
            color: #fff;
        }

        .Trace .Tag-notice {
            background: #FF9000;
            color: #fff;
        }

        .Trace .Tag-error {
            background: #f00;
            color: #fff;
        }

        .Trace pre {
            color: #000;
        }
    </style>
    <h2>Debug Trace</h2>
    <table>
        <?php
        foreach ($this->data('Traces') as $Trace):
            list($Message, $Type) = $Trace;

            $Var = 'Debug';
            if (!in_array($Type, [TRACE_ERROR, TRACE_INFO, TRACE_NOTICE, TRACE_WARNING])) {
                $Var = $Type;
                $Type = TRACE_INFO;
            } elseif (!$Message) {
                // Don't show empty messages.
                continue;
            }
            ?>
            <tr>
                <td class="TagColumn">
                    <span
                        class="Tag Tag-<?php echo Gdn_Format::alphaNumeric($Type); ?>"><?php echo htmlspecialchars($Type); ?></span>
                </td>
                <td>
                    <?php
                    if (is_string($Message)) {
                        if ($Var != 'Debug')
                            echo '<b>'.htmlspecialchars($Var).'</b>: ';

                        echo nl2br(htmlspecialchars($Message));
                    } elseif (is_a($Message, 'Exception')) {
                        echo '<pre>';
                        echo htmlspecialchars($Message->getMessage());
                        echo "\n\n";
                        echo htmlspecialchars($Message->getTraceAsString());
                        echo '</pre>';
                    } else
                        echo "<pre><b>$Var:</b> ".htmlspecialchars(var_export($Message, true)).'</pre>';
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>
