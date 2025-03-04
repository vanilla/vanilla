<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

/**
 * Defines the event dispatched when a machine translation needs to be generated
 */
class MachineTranslationEvent
{
    //region Constructor
    /**
     * Constructor
     *
     * @param string $action
     * @param array $payload
     * @param array|object|null $sender
     */
    public function __construct(private string $recordType, private array $recordIDs, private $sender = null)
    {
    }
    //endregion

    //region Methods

    /**
     * get Record type to translate
     *
     * @return string
     */
    public function getRecordType(): string
    {
        return $this->recordType;
    }

    /**
     * get Record IDs to translate
     *
     * @return array
     */
    public function getRecordIDs(): array
    {
        return $this->recordIDs;
    }
    //endregion
}
