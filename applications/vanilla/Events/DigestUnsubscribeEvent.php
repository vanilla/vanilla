<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Vanilla\Forum\Digest\DigestEmail;

/**
 * Event for digest unsubscribe.
 */
class DigestUnsubscribeEvent
{
    protected array $eventData;

    private $digestEmail;
    private $digestUser;
    private $digestAttributes;

    /**
     * Constructor.
     *
     * @param $digestEmail
     * @param $digestUser
     * @param $digestAttributes
     */
    public function __construct(DigestEmail $digestEmail, array $digestUser, array $digestAttributes)
    {
        $this->digestEmail = $digestEmail;
        $this->digestUser = $digestUser;
        $this->digestAttributes = $digestAttributes;
    }

    /**
     * Get the event data.
     *
     * @return array
     */
    public function &getEventData(): array
    {
        $returnData = [
            "digestEmail" => $this->digestEmail,
            "digestUser" => $this->digestUser,
            "digestAttributes" => $this->digestAttributes,
        ];

        return $returnData;
    }
}
