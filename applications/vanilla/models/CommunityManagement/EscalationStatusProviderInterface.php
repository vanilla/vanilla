<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\CommunityManagement;

interface EscalationStatusProviderInterface
{
    /**
     * Get the ID of the status.
     *
     * @return string
     */
    public function getStatusID(): string;

    /**
     * Get the translation code used for the escalation status label.
     *
     * @return string
     */
    public function getStatusLabelCode(): string;
}
