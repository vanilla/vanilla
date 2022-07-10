<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Database\Operation\JsonFieldProcessor;

/**
 * A model to handle basic key/value pairs associated with a user.
 *
 * This is very much a more modern version of the user meta table, but without the tedious interface and monolithic caching.
 */
class UserAttributesModel extends PipelineModel {
    /**
     * UserAttributeModel constructor.
     */
    public function __construct() {
        parent::__construct('userAttributes');
        $this->setPrimaryKey('userID', 'key');

        $json = new JsonFieldProcessor(['attributes']);
        $this->addPipelineProcessor($json);
    }

    /**
     * Get the attributes for a user at a certain key.
     *
     * @param int $userID The user to lookup.
     * @param string $key The key to lookup.
     * @param mixed $default The default if the attributes aren't found.
     * @return mixed Returns the attributes or **null**.
     */
    public function getAttributes(int $userID, string $key, $default = null) {
        $r = $this->select(['userID' => $userID, 'key' => $key]);
        return empty($r) ? $default : $r[0]['attributes'];
    }

    /**
     * Set the attributes for a user at a certain key.
     *
     * @param int $userID The user to set the attributes for.
     * @param string $key The key to set the attributes at.
     * @param mixed $value The new attributes value.
     */
    public function setAttributes(int $userID, string $key, $value) {
        $this->insert(['userID' => $userID, 'key' => $key, 'attributes' => $value], [Model::OPT_REPLACE => true]);
    }

    /**
     * Patch some of the attributes on a key.
     *
     * @param int $userID The user to update.
     * @param string $key The key of the attributes.
     * @param array $patch An array to merge with the current value.
     */
    public function patchAttributes(int $userID, string $key, array $patch) {
        $current = $this->getAttributes($userID, $key, []);
        if (!is_array($current)) {
            throw new \InvalidArgumentException("You cannot patch non-array attributes.");
        }
        $set = array_replace_recursive($current, $patch);
        $this->setAttributes($userID, $key, $set);
    }
}
