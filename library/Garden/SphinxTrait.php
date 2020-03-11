<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden;

/**
 * For classes that want Sphinx search to incorporate.
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
            throw new \Exception('Sphinx client class not found.');
        }
        $sphinxHost = c('Plugins.Sphinx.Server', c('Database.Host', 'localhost'));
        $sphinxPort = c('Plugins.Sphinx.Port', 9312);

        $client = new \SphinxClient();
        $client->setServer($sphinxHost, $sphinxPort);

        // Set some defaults
        if (method_exists($client, "setMatchMode")) {
            $client->setMatchMode(SPH_MATCH_EXTENDED2);
            $client->setSortMode(SPH_SORT_TIME_SEGMENTS, 'DateInserted');
        } else {
            // SPH_SORT_TIME_SEGMENTS is not a valid sort mode in Sphinx 3.2.1.
            $client->setSortMode(SPH_SORT_RELEVANCE);
        }
        $client->setMaxQueryTime(5000);

        return $client;
    }

    /**
     * Return sphinx index name: ex vanilla_dev_KnowledgeArticle
     *
     * @param string $index Search index name. Ex: KnowledgeArticle
     * @return string
     */
    public static function sphinxIndexName(string $index): string {
        $prefix = str_replace(['-'], '_', c('Database.Name')) . '_';
        return $prefix . $index;
    }
}
