<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Web\Exception\ClientException;

/**
 * Handle custom themes.
 */
trait FsThemeMissingTrait {
    /**
     * @inheritdoc
     */
    public function postTheme(array $body): array {
        throw new ClientException(__CLASS__.' does not provide '.__FUNCTION__.' method!', 501);
        return [];
    }

    /**
     * @inheritdoc
     */
    public function patchTheme(int $themeID, array $body): array {
        throw new ClientException(__CLASS__.' does not provide '.__FUNCTION__.' method!', 501);
        return [];
    }

    /**
     * @inheritdoc
     */
    public function deleteTheme(int $themeID) {
        throw new ClientException(__CLASS__.' does not provide '.__FUNCTION__.' method!', 501);
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getCurrent(): ?array {
        throw new ClientException(__CLASS__.' does not provide '.__FUNCTION__.' method!', 501);
        return [];
    }

    /**
     * @inheritdoc
     */
    public function setAsset(int $themeID, string $assetKey, string $data): array {
        throw new ClientException(__CLASS__.' does not provide '.__FUNCTION__.' method!', 501);
        return [];
    }

    /**
     * @inheritdoc
     */
    public function deleteAsset($themeKey, string $assetKey) {
        throw new ClientException(__CLASS__.' does not provide '.__FUNCTION__.' method!', 501);
        return [];
    }

    /**
     * @inheritdoc
     */
    public function sparseAsset(int $themeID, string $assetKey, string $data): array {
        throw new ClientException(__CLASS__.' does not provide '.__FUNCTION__.' method!', 501);
        return [];
    }
}
