<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Gdn_Session;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;

/**
 * Handle theme assets.
 */
class ThemeAssetModel extends PipelineModel {

    /** @var Gdn_Session */
    private $session;

    /**
     * ThemeAssetModel constructor.
     *
     * @param Gdn_Session $session
     */
    public function __construct(Gdn_Session $session) {
        parent::__construct("themeAsset");
        $this->session = $session;

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])
            ->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }
}
