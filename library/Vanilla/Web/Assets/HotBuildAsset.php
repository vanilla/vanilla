<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @since 2.8
 */

namespace Vanilla\Web\Assets;

class HotBuildAsset extends AbstractAsset {
    /** @var string */
    private $ip;

    /** @var string */
    private $section;

    public function __construct(\Gdn_Request $request, CacheBusterInterface $cacheBuster, string $section, string $ip = "127.0.0.1") {
        parent::__construct($request, $cacheBuster);
        $this->ip = $ip;
        $this->section = $section;
    }

    public function getWebPath(): string {
        return "http://$this->ip:3030/$this->section-hot-bundle.js";
    }
}
