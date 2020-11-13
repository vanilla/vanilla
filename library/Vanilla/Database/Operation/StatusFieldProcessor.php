<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\Operation;

use Vanilla\CurrentTimeStamp;
use Vanilla\Database\Operation;

/**
 * A processor that watches a status field for changes and sets audit information on other fields.
 */
class StatusFieldProcessor implements Processor {
    /** @var string */
    private $statusField = 'status';

    /** @var string */
    private $dateField = 'dateOfStatus';

    /** @var string */
    private $userField = 'statusUserID';

    /** @var string */
    private $ipAddressField = 'statusIPAddress';

    /** @var bool */
    private $setOnInsert = true;

    /**
     * @var \Gdn_Request
     */
    private $request;

    /**
     * @var \Gdn_Session
     */
    private $session;

    /**
     * StatusFieldProcessor constructor.
     *
     * @param \Gdn_Request $request
     * @param \Gdn_Session $session
     */
    public function __construct(\Gdn_Request $request, \Gdn_Session $session) {
        $this->request = $request;
        $this->session = $session;
    }

    /**
     * Get the name of the date field to update.
     *
     * @return string
     */
    public function getDateField(): string {
        return $this->dateField;
    }

    /**
     * Set the name of the date field to update.
     *
     * @param string $dateField
     * @return self
     */
    public function setDateField(string $dateField): self {
        $this->dateField = $dateField;
        return $this;
    }

    /**
     * Get the name of the user field to update.
     *
     * @return string
     */
    public function getUserIDField(): string {
        return $this->userField;
    }

    /**
     * Set the name of the user field to update.
     *
     * @param string $userField
     * @return self
     */
    public function setUserField(string $userField): self {
        $this->userField = $userField;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function handle(Operation $operation, callable $stack) {
        switch ($operation->getType()) {
            case Operation::TYPE_INSERT:
                if ($this->getSetOnInsert() || $operation->hasSetItem($this->getStatusField())) {
                    $this->setStatusFields($operation);
                }
                break;
            case Operation::TYPE_UPDATE:
                if ($operation->hasSetItem($this->getStatusField())) {
                    $this->setStatusFields($operation);
                }
                break;
            case Operation::TYPE_SELECT:
                $result = $stack($operation);
                $ipField = $this->getIpAddressField();
                if (!empty($ipField)) {
                    foreach ($result as &$row) {
                        if (!empty($row[$ipField])) {
                            $row[$ipField] = ipDecode($row[$ipField]);
                        }
                    }
                }
                return $result;
        }
        return $stack($operation);
    }

    /**
     * Whether or not to set the audit fields on insert regardless of whether the status field is specified.
     *
     * @return bool
     */
    public function getSetOnInsert(): bool {
        return $this->setOnInsert;
    }

    /**
     * Whether or not to set the audit fields on insert regardless of whether the status field is specified.
     *
     * @param bool $setOnInsert
     * @return $this
     */
    public function setSetOnInsert(bool $setOnInsert): self {
        $this->setOnInsert = $setOnInsert;
        return $this;
    }

    /**
     * Set the name of the status field to watch.
     *
     * @return string
     */
    public function getStatusField(): string {
        return $this->statusField;
    }

    /**
     * Get the name of the status field to watch.
     *
     * @param string $statusField
     * @return self
     */
    public function setStatusField(string $statusField): self {
        $this->statusField = $statusField;
        return $this;
    }

    /**
     * Set the status audit fields on the operation.
     *
     * @param Operation $op
     */
    private function setStatusFields(Operation $op) {
        if (!empty($this->getUserIDField()) && !$op->hasSetItem($this->userField)) {
            $op->setSetItem($this->userField, $this->session->UserID);
        }
        if (!empty($this->getDateField()) && !$op->hasSetItem($this->dateField)) {
            $op->setSetItem($this->dateField, CurrentTimeStamp::getMySQL());
        }
        if (!empty($this->getIpAddressField()) && !$op->hasSetItem($this->ipAddressField)) {
            $op->setSetItem($this->ipAddressField, $this->request->getIP());
        }
    }

    /**
     * Get the name of the IP address field update.
     *
     * @return string
     */
    public function getIpAddressField(): string {
        return $this->ipAddressField;
    }

    /**
     * Set the name of the IP address field update.
     *
     * @param string $ipAddressField
     * @return self
     */
    public function setIpAddressField(string $ipAddressField): self {
        $this->ipAddressField = $ipAddressField;
        return $this;
    }

    /**
     * Get the current IP address used for field updates.
     *
     * @return string|null
     */
    public function getCurrentIPAddress(): ?string {
        return $this->request->getIP();
    }

    /**
     * Get the ID of the current user.
     *
     * @return int
     */
    public function getCurrentUserID(): int {
        return $this->session->UserID;
    }
}
