<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL v2
 */

namespace Garden;

use Gdn;

/**
 * For classes that want translation to incorporate.
 *
 */
trait SphinxTrait {

    /**
     * Check if a SphinxClient is available.  If not try to make it available using $apiDir.
     *
     * $apiPAth is a kludge that allows to use Sphinx on PHP7 the correct version without compiling the php extension yourself
     * Source of the class https://github.com/sphinxsearch/sphinx/blob/master/api/sphinxapi.php
     * Make sure that it matches the sphinx version you are using.
     *
     * @param string $apiDir Directory that contains sphinxapi.php
     *
     * @return bool
     */
    public static function checkSphinxClient(string $apiDir = null): bool {
        if (class_exists('SphinxClient')) {
            return true;
        }

        $sphinxClientPath = rtrim($apiDir, '/').'/sphinxapi.php';
        if (is_readable($sphinxClientPath)) {
            require_once($sphinxClientPath);
        }

        return class_exists('SphinxClient');
    }

    /**
     * Get SphinxClient object
     *
     * @return SphinxClient
     */
    public static function sphinxClient(): \SphinxClient {
        if (!self::checkSphinxClient()) {
            return null;
        }
        $sphinxHost = c('Plugins.Sphinx.Server', c('Database.Host', 'localhost'));
        $sphinxPort = c('Plugins.Sphinx.Port', 9312);

        $client = new \SphinxClient();
        $client->setServer($sphinxHost, $sphinxPort);

        // Set some defaults.
        $client->setMatchMode(SPH_MATCH_EXTENDED2);
        $client->setSortMode(SPH_SORT_TIME_SEGMENTS, 'DateInserted');
        $client->setMaxQueryTime(5000);
        $client->setFieldWeights(['name' => 3, 'body' => 1]);
        return $client;
    }

    public static function sphinxSearch(\SphinxClient $sphinx, string $query, array $indexes): array {
        $search = $sphinx->query($query, implode(' ', $indexes));
        echo(__CLASS__.':'.__METHOD__.':'.__LINE__."\n");
        die(print_r($search));
        $results = self::sphinxGetDocuments($search);
        $total = $search['total'] ?? 0;
        $searchTerms = $search['words'];

        if (is_array($searchTerms)) {
            $searchTerms = array_keys($searchTerms);
        } else {
            $searchTerms = [];
        }

        return [
            'SearchResults' => $results,
            'RecordCount' => $total,
            'SearchTerms' => $searchTerms
        ];
    }

    public static function sphinxIndexName(string $index): string {
        $prefix = str_replace(['-'], '_', c('Database.Name')) . '_';
        return $prefix . $index;
    }

    public static function sphinxGetDocuments($search): array {
        $result = [];

        // Loop through the matches to figure out which IDs we have to grab.
        $iDs = [];
        if (!is_array($search) || !isset($search['matches'])) {
            return [];
        }

        foreach ($search['matches'] as $documentID => $info) {
            print_r($documentID);
            print_r($info);
            $iD = (int) ($documentID / 10);
            $type = $documentID % 10;

            $iDs[$type][] = $iD;
        }
        die();

        // Join them with the search results.
        $result = [];
        foreach ($search['matches'] as $documentID => $info) {
            $row = $documents[$documentID] ?? false;
            if ($row === false) {
                continue;
            }
            $row['Relevance'] = $info['weight'];
            $row['Score'] = $info['attrs']['score'];
            $row['Count'] = $info['attrs']['@count'] ?? 1;
            $row['sort'] = $info['attrs']['sort'] ?? false;

            $result[] = $row;
        }
        return $result;
    }
}
