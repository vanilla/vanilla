<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Theme;

use Nette\Neon\Neon;

/**
 * JSON theme asset.
 */
class NeonAsset extends JsonAsset {

    /** @var string Type of asset. */
    protected $type = "neon";

    /**
     * Configure the JSON asset.
     *
     * @param string $data
     */
    public function __construct($data) {
        $this->data = Neon::decode($data);

        $result = true;
    }
}
