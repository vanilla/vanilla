<?php
/**
 * @author Dani Stark <dani.stark@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Garden\Schema\Schema;

/**
 * Class PocketService
 *
 * @package Vanilla\Utility
 */
class PocketService
{
    /** @var Schema $schema */
    private $schema;

    /** @var array pocket pages. */
    private $pages = [];

    /**
     * Extend Schema
     *
     * @param Schema $schema
     */
    public function extendSchema(Schema $schema)
    {
        if ($this->schema === null) {
            $this->schema = $schema;
        } else {
            $this->schema = $this->schema->merge($schema);
        }
    }

    /**
     * Add a pocket page.
     *
     * @param string $page
     */
    public function addPage(string $page)
    {
        $this->pages[] = $page;
    }

    /**
     * Get a pocket page.
     *
     * @return array
     */
    public function getPages(): array
    {
        return $this->pages;
    }

    /**
     * Get Schema
     *
     * @return Schema|null
     */
    public function getSchema(): ?Schema
    {
        return $this->schema;
    }
}
