<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Exception\ForbiddenException;
use Vanilla\Exception\PermissionException;

/**
 * Resolve smart IDs against a user.
 *
 * User smart IDs can lookup fields in the user table, also UserAuthentication table for SSO.
 */
class UserSmartIDResolver {
    private $emailEnabled = true;
    private $viewEmail = false;

    /**
     * @var \Gdn_Session
     */
    private $session;

    /**
     * UserSmartIDResolver constructor.
     *
     * @param \Gdn_Session $session
     */
    public function __construct(\Gdn_Session $session) {
        $this->session = $session;
    }

    /**
     * Lookup the user ID from the smart ID.
     *
     * @param SmartIDMiddleware $sender The middleware invoking the lookup.
     * @param string $pk The primary key of the lookup (UserID).
     * @param string $column The column to lookup.
     * @param string $value The value to lookup.
     * @return mixed Returns the smart using **SmartIDMiddleware::fetchValue()**.
     */
    public function __invoke(SmartIDMiddleware $sender, string $pk, string $column, string $value) {
        if ($column === 'email') {
            if (!$this->canViewEmail()) {
                throw new PermissionException('personalInfo.view');
            }
            if (!$this->isEmailEnabled()) {
                throw new ForbiddenException('Email addresses are disabled.');
            }
        }

        if ($column === 'me' && empty($value)) {
            if ($this->session->isValid()) {
                return $this->session->UserID;
            } else {
                throw new ForbiddenException('You must sign in.');
            }
        } elseif (in_array($column, ['name', 'email'])) {
            // These are basic field lookups on the user table.
            return $sender->fetchValue('User', $pk, [$column => $value]);
        } else {
            // Try looking up a secondary user ID.
            return $sender->fetchValue('UserAuthentication', $pk, ['providerKey' => $column, 'foreignUserKey' => $value]);
        }
    }

    /**
     * Whether or not email addresses are enabled.
     *
     * @return bool Returns the emailEnabled.
     */
    public function isEmailEnabled(): bool {
        return $this->emailEnabled;
    }

    /**
     * Set the whether or not email addresses are enabled.
     *
     * @param bool $emailEnabled The new value.
     * @return $this
     */
    public function setEmailEnabled(bool $emailEnabled) {
        $this->emailEnabled = $emailEnabled;
        return $this;
    }

    /**
     * Whether or not the user has permission to view emails.
     *
     * @return bool Returns the canViewEmail.
     */
    public function canViewEmail(): bool {
        return $this->viewEmail;
    }

    /**
     * Set whether or not the user has permission to view emails.
     *
     * @param bool $viewEmail The new value.
     * @return $this
     */
    public function setViewEmail(bool $viewEmail) {
        $this->viewEmail = $viewEmail;
        return $this;
    }

}
