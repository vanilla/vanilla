<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Forum;

use Vanilla\Community\Schemas\AbstractTabSearchFormSchema;

/**
 * Mock tag schema for tests.
 */
class MockTagSearchFormSchema extends AbstractTabSearchFormSchema
{
    /** @var array */
    private $schema;

    /** @var string */
    private $tabID;

    /** @var string */
    private $submitButtonText;

    /** @var string */
    private $title;

    /**
     * DI.
     *
     * @param array $schema
     * @param string $tabID
     * @param string $submitButtonText
     * @param string $title
     */
    public function __construct(array $schema, string $tabID, string $submitButtonText, string $title)
    {
        $this->schema = $schema;
        $this->tabID = $tabID;
        $this->submitButtonText = $submitButtonText;
        $this->title = $title;
    }

    /**
     * @return array
     */
    public function schema(): array
    {
        return $this->schema;
    }

    /**
     * @return string
     */
    public function getTabID(): string
    {
        return $this->tabID;
    }

    /**
     * @return string
     */
    public function getSubmitButtonText(): string
    {
        return $this->submitButtonText;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }
}
