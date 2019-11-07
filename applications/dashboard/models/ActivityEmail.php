<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

 namespace Vanilla\Dashboard\Models;

/**
  * Dumb data object representing an email notification for an activity.
  *
  * @internal This is a temporary class. Do not use. It will be removed in the near future.
  */
class ActivityEmail {

    /** @var int[] */
    private $activityIDs = [];

    /** @var string */
    private $actionText;

    /** @var int */
    private $activityTypeID;

    /** @var string */
    private $body = "";

    /** @var string */
    private $internalRoute;

    /** @var string[] */
    private $recipients = [];

    /** @var int */
    private $recordID;

    /** @var string */
    private $recordType;

    /** @var string */
    private $subject = "";

    /**
     * Add the ID of an activity associated with this email.
     *
     * @param integer $activityID
     */
    public function addActivityID(int $activityID) {
        $this->activityIDs[] = $activityID;
    }

    /**
     * Add an email address to the list of recipients.
     *
     * @param string $email
     * @param string $name
     */
    public function addRecipient(string $email, ?string $name = null) {
        $this->recipients[] = [$email, $name];
    }

    /**
     * Get the call to action text for this email.
     *
     * @return string|null
     */
    public function getActionText(): ?string {
        return $this->actionText;
    }

    /**
     * Return all associated activity IDs.
     *
     * @return array
     */
    public function getActivityIDs(): array {
        return $this->activityIDs;
    }

    /**
     * Get the activity type for this notification.
     *
     * @return integer
     */
    public function getActivityTypeID(): ?int {
        return $this->activityTypeID;
    }

    /**
     * Get the email body.
     *
     * @return string
     */
    public function getBody(): string {
        return $this->body;
    }

    /**
     * Get the internal route behind the call to action for this email.
     *
     * @return string|null
     */
    public function getInternalRoute(): ?string {
        return $this->internalRoute;
    }

    /**
     * Get the currently-configured list of recipient email addresses.
     *
     * @return string[]
     */
    public function getRecipients(): array {
        return $this->recipients;
    }

    /**
     * Get the ID of the record associated with this notification.
     *
     * @return integer
     */
    public function getRecordID(): ?int {
        return $this->recordID;
    }

    /**
     * Get the type of record associated with this notification.
     *
     * @return string
     */
    public function getRecordType(): ?string {
        return $this->recordType;
    }

    /**
     * Get the email subject.
     *
     * @return string
     */
    public function getSubject(): string {
        return $this->subject;
    }

    /**
     * Reset to defaults.
     */
    public function reset() {
        $this->actionText = null;
        $this->activityIDs = [];
        $this->activityTypeID = null;
        $this->body = "";
        $this->internalRoute = null;
        $this->recipients = [];
        $this->recordID = null;
        $this->recordType = null;
        $this->subject = "";
    }

    /**
     * Set the call to action text for this email.
     *
     * @param string|null $actionText
     */
    public function setActionText(?string $actionText) {
        $this->actionText = $actionText;
    }

    /**
     * Set the route behind the call to action for this email.
     *
     * @param string|null $internalRoute
     */
    public function setInternalRoute(?string $internalRoute) {
        $this->internalRoute = $internalRoute;
    }

    /**
     * Set the activity type for this notification.
     *
     * @param integer|null $activityTypeID
     */
    public function setActivityTypeID(?int $activityTypeID) {
        $this->activityTypeID = $activityTypeID;
    }

    /**
     * Set the email body.
     *
     * @param string $body
     */
    public function setBody(string $body) {
        $this->body = $body;
    }

    /**
     * Set the ID of the record associated with this notification.
     *
     * @param integer|null $recordID
     */
    public function setRecordID(?int $recordID) {
        $this->recordID = $recordID;
    }

    /**
     * Set the type of record associated with this notification.
     *
     * @param string|null $recordType
     */
    public function setRecordType(?string $recordType) {
        $this->recordType = $recordType;
    }

    /**
     * Set the email subject.
     *
     * @param string $subject
     */
    public function setSubject(string $subject) {
        $this->subject = $subject;
    }
}
