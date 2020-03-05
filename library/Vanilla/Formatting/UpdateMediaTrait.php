<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use MediaModel;
use Gdn_Session as SessionInterface;

trait UpdateMediaTrait {

    /** @var FormatService */
    private $formatterService;

    /** @var string */
    private $mediaForeignTable;

    /** @var MediaModel */
    private $mediaModel;

    /** @var SessionInterface */
    private $sessionInterface;

    /**
     * Set outdated media attachment rows as inactive.
     *
     * @param int $foreignID
     * @param string $rawBody
     * @param string $bodyFormat
     */
    private function flagInactiveMedia(int $foreignID, string $rawBody, string $bodyFormat) {
        $attachments = $this->getFormatterService()->parseAttachments($rawBody, $bodyFormat);
        $currentMediaIDs = array_column($attachments, "mediaID");
        $foreignTable = $this->getMediaForeignTable();
        $mediaModel = $this->getMediaModel();

        $mediaModel->SQL
            ->reset()
            ->update($mediaModel->Name)
            ->set("Active", 0)
            ->where("ForeignID", $foreignID)
            ->where("ForeignTable", $foreignTable)
            ->whereNotIn("MediaID", $currentMediaIDs)
            ->put();
    }

    /**
     * Get the currently-configured FormatService instance.
     *
     * @return FormatService
     * @throws \Exception If no FormatService instance has been configured.
     */
    private function getFormatterService(): FormatService {
        if (!isset($this->formatterService) || !($this->formatterService instanceof FormatService)) {
            throw new \Exception("FormatService has not been configured.");
        }

        return $this->formatterService;
    }

    /**
     * Get the value to be used for ForeignTable values in the media table.
     *
     * @return string
     */
    private function getMediaForeignTable(): string {
        if (!isset($this->mediaForeignTable)) {
            throw new \Exception("mediaForeignTable has not been configured.");
        }
        return $this->mediaForeignTable;
    }

    /**
     * Get the currently-configured MediaModel instance.
     *
     * @return MediaModel
     * @throws \Exception If no MediaModel instance has been configured.
     */
    private function getMediaModel(): MediaModel {
        if (!isset($this->mediaModel) || !($this->mediaModel instanceof MediaModel)) {
            throw new \Exception("MediaModel has not been configured.");
        }

        return $this->mediaModel;
    }

    /**
     * Get the current session interface.
     *
     * @return SessionInterface
     */
    private function getSessionInterface(): SessionInterface {
        if (!isset($this->sessionInterface) || !($this->sessionInterface instanceof SessionInterface)) {
            throw new \Exception("SessionInterface has not been configured.");
        }

        return $this->sessionInterface;
    }

    /**
     * Update media rows to reflect current valid attachments.
     *
     * @param int $foreignID
     * @param string $rawBody
     * @param string $bodyFormat
     */
    private function refreshMediaAttachments(int $foreignID, string $rawBody, string $bodyFormat) {
        $attachments = $this->getFormatterService()->parseAttachments($rawBody, $bodyFormat);
        $currentMediaIDs = array_column($attachments, "mediaID");
        $mediaModel = $this->getMediaModel();

        if (!empty($currentMediaIDs)) {
            $mediaUpdateCriteria = ["MediaID" => $currentMediaIDs];
            // Site moderators (and up) can make associations with whatever file they'd like. Other users need to own the file.
            if (!$this->getSessionInterface()->checkRankedPermission("Garden.Moderation.Manage")) {
                $mediaUpdateCriteria["InsertUserID"] = $this->getSessionInterface()->UserID;
            }
            $mediaModel->SQL
                ->reset()
                ->update($mediaModel->Name)
                ->set([
                    "ForeignID" => $foreignID,
                    "ForeignTable" => $this->getMediaForeignTable(),
                ])
                ->where($mediaUpdateCriteria)
                ->put();
        }
    }

    /**
     * Set the FormatService instance.
     *
     * @param FormatService $formatterService
     */
    private function setFormatterService(FormatService $formatterService) {
        $this->formatterService = $formatterService;
    }

    /**
     * Set the value to be used for ForeignTable values in the media table.
     *
     * @param string $mediaForeignTable
     */
    private function setMediaForeignTable(string $mediaForeignTable) {
        $this->mediaForeignTable = $mediaForeignTable;
    }

    /**
     * Set the MediaModel instance.
     *
     * @param MediaModel $mediaModel
     */
    private function setMediaModel(MediaModel $mediaModel) {
        $this->mediaModel = $mediaModel;
    }

    /**
     * Set the current session interface.
     *
     * @param SessionInterface $sessionInterface
     */
    private function setSessionInterface(SessionInterface $sessionInterface) {
        $this->sessionInterface = $sessionInterface;
    }
}
