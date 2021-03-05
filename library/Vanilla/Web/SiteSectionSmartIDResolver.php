<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Subcommunities\Models\SubcommunitySiteSection;

/**
 * Class SiteSectionSmartIDResolver
 *
 * @package Vanilla\Web
 */
class SiteSectionSmartIDResolver {

    /**
     * Lookup the site section ID from the smart ID.
     *
     * @param SmartIDMiddleware $sender The middleware invoking the lookup.
     * @param string $pk The primary key of the lookup (UserID).
     * @param string $column The column to lookup.
     * @param string $value The value to lookup.
     * @return mixed Returns the smart using **SmartIDMiddleware::fetchValue()**.
     */
    public function __invoke(SmartIDMiddleware $sender, string $pk, string $column, string $value) {
        if (!is_numeric($value)) {
            $id = $sender->fetchValue('Subcommunity', 'SubcommunityID', ['Folder' => $value]);
        } else {
            $id = $sender->fetchValue('Subcommunity', 'SubcommunityID', ['SubcommunityID' => $value]);
        }

        if ($id) {
            return SubcommunitySiteSection::SUBCOMMUNITY_SECTION_PREFIX.$id;
        }
        return null;
    }
}
