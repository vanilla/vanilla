<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility\Media;

/**
 * Interface for classes built to validate foreign row associations with media records.
 */
interface ForeignValidatorInterface {

    /**
     * Given a foreign type and record ID, verify whether or not the current user can attach a media item to it.
     *
     * @param string $foreignType
     * @param string|int $foreignID
     * @return bool
     */
    public function canAttach(string $foreignType, $foreignID): bool;
}
