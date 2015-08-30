<?php if (!defined('APPLICATION')) return; ?>
<div id="Sql" class="DebugInfo">
    <h2>Debug Information</h2>
    <?php
    // Add the canonical Url.
    if (method_exists($Sender, 'CanonicalUrl')) {
        $CanonicalUrl = htmlspecialchars($Sender->canonicalUrl(), ENT_COMPAT, c('Garden.Charset', 'UTF-8'));

        echo '<div class="CanonicalUrl"><b>'.t('Canonical Url')."</b>: <a href=\"$CanonicalUrl\" accesskey=\"r\">$CanonicalUrl</a></div>";
    }
    ?>

    <?php
    // Add some cache info.
    if (Gdn::cache()->activeEnabled()) {
        echo '<h3>Cache Information</h3>';
        echo '<pre>';
        echo '<b>Cache Revision</b>: '.Gdn::cache()->GetRevision()."\n";
        echo '<b>Permissions Revision</b>: '.Gdn::userModel()->GetPermissionsIncrement()."\n";

        if (property_exists('Gdn_Cache', 'GetCount')) {
            echo '<b>Cache Gets</b>: '.sprintf('%s in %ss', Gdn_Cache::$GetCount, Gdn_Cache::$GetTime);
        }
        echo '</pre>';

        if (property_exists('Gdn_Cache', 'trackGet') && sizeof(Gdn_Cache::$trackGet)) {

            uasort(Gdn_Cache::$trackGet, function ($a, $b) {
                return $b['hits'] - $a['hits'];
            });

            $numKeys = sizeof(Gdn_Cache::$trackGet);
            $duplicateGets = 0;
            $wastedBytes = 0;
            $totalBytes = 0;
            foreach (Gdn_Cache::$trackGet as $key => $keyData) {
                if ($keyData['hits'] > 1) $duplicateGets += ($keyData['hits'] - 1);
                $wastedBytes += $keyData['wasted'];
                $totalBytes += $keyData['transfer'];
            }
            $wastedKB = round($wastedBytes / 1024, 2);
            $totalKB = round($totalBytes / 1024, 2);

            echo "Gets\n";
            echo '<pre>';
            echo '<b>Trips to cache</b>: '.sprintf('%s in %ss', Gdn_Cache::$trackGets, Gdn_Cache::$trackTime)."\n";
            echo '<b>Unique Keys</b>: '.sprintf('%s keys', $numKeys)."\n";
            echo '<b>Total Transfer</b>: '.sprintf('%skB', $totalKB)."\n";
            echo '<b>Wasted Transfer</b>: '.sprintf('%skB over %s duplicate key gets', $wastedKB, $duplicateGets)."\n";
            echo '</pre>';

            foreach (Gdn_Cache::$trackGet as $key => $keyData) {
                echo $key;
                echo '<span>'.round($keyData['keysize'] / 1024, 2).'kB</span> ';
                echo '<small>'.@number_format($keyData['time'], 6).'s</small><br/>';
                if ($keyData['hits'] > 1) {
                    echo '<pre>';
                    echo '<b>Fetched</b>: '.$keyData['hits'];
                    if ($keyData['wasted']) {
                        $keyWastedKB = round($keyData['wasted'] / 1024, 2);
                        echo "\n<b>Wasted</b>: {$keyWastedKB}kB";
                    }
                    echo '</pre>';
                }
            }
        }
    }
    ?>

    <?php
    // Add the queries.
    $Database = Gdn::database();
    $SQL = $Database->sql();

    if (!is_null($Database)) {
        $String = '';
        $Queries = $Database->Queries();
        $String .= '<h3>'.count($Queries).' queries in '.$Database->ExecutionTime().'s</h3>';
        foreach ($Queries as $Key => $QueryInfo) {
            $Query = $QueryInfo['Sql'];
            // this is a bit of a kludge. I found that the regex below would mess up when there were incremented named parameters. Ie. it would replace :Param before :Param0, which ended up with some values like "'4'0".
            if (isset($QueryInfo['Parameters']) && is_array($QueryInfo['Parameters'])) {
                $tmp = $QueryInfo['Parameters'];

                $Query = $SQL->ApplyParameters($Query, $tmp);
            }
            $String .= $QueryInfo['Method']
                .' <small>'.$QueryInfo['connection'].'</small>'
                .' <small>'.@number_format($QueryInfo['Time'], 6).'s</small> '
                .(isset($QueryInfo['Cache']) ? '<div><b>Cache:</b> '.var_export($QueryInfo['Cache'], true).'</div>' : '')
                .'<pre>'.htmlspecialchars($Query).';</pre>';
        }
        echo $String;
    }

    global $Start;
    echo '<h3>Page completed in '.round(Now() - $_SERVER['REQUEST_TIME'], 4).'s</h3>';
    ?>

    <h3>Controller Data</h3>
<pre>
<?php
echo DebuggerPlugin::FormatData(Gdn::controller()->Data);
?>
</pre>


</div>
