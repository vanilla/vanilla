<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden;

use Vanilla\Adapters\SphinxClient;

/**
 * For classes that want Sphinx search to incorporate.
 *
 */
trait SphinxTrait {
    /**
     * Get SphinxClient object
     *
     * @return SphinxClient
     */
    public static function sphinxClient(): SphinxClient {
        $sphinxHost = c('Plugins.Sphinx.Server', c('Database.Host', 'localhost'));
        $sphinxPort = c('Plugins.Sphinx.Port', 9312);

        $client = new SphinxClient();
        $client->setServer($sphinxHost, $sphinxPort);
        $client->setSortMode(SphinxClient::SORT_RELEVANCE);
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
