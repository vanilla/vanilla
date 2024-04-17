<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\Schema\Schema;
use Vanilla\CurrentTimeStamp;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\DebugUtils;

/**
 * Class AttachmentService
 */
class AttachmentService
{
    private $providers = [];

    /**
     * Add a provider to the service.
     *
     * @param AttachmentProviderInterface $provider
     * @return void
     */
    public function addProvider(AttachmentProviderInterface $provider)
    {
        $this->providers[$provider->getTypeName()] = $provider;
    }

    /**
     * Get all the providers in the service.
     *
     * @return AttachmentProviderInterface[]
     */
    public function getAllProviders(): array
    {
        return $this->providers;
    }

    /**
     * Get a provider by source name.
     *
     * @param string $typeName
     *
     * @return AttachmentProviderInterface
     */
    public function getProvider(string $typeName): ?AttachmentProviderInterface
    {
        return $this->providers[$typeName] ?? null;
    }

    /**
     * Get a catalog of all the providers in the service.
     *
     * @return array
     */
    public function getCatalog()
    {
        $catalog = [];
        foreach ($this->getAllProviders() as $provider) {
            if ($provider->hasPermissions()) {
                $catalog[$provider->getTypeName()] = [
                    "attachmentType" => $provider->getTypeName(),
                    "label" => $provider->getCreateLabelCode(),
                    "submitButton" => $provider->getSubmitLabelCode(),
                    "recordTypes" => $provider->getRecordTypes(),
                    "title" => $provider->getTitleLabelCode(),
                    "externalIDLabel" => $provider->getExternalIDLabelCode(),
                    "logoIcon" => $provider->getLogoIconName(),
                ];
            }
        }
        return $catalog;
    }

    /**
     * Given a list of attachment rows, refresh them.
     *
     * @param array $attachmentRows
     * @return array
     */
    public function refreshAttachments(array $attachmentRows): array
    {
        $resultRowsByID = [];
        $attachmentIDsOrdered = array_column($attachmentRows, "AttachmentID");
        $attachmentsByType = ArrayUtils::arrayColumnArrays($attachmentRows, null, "Type");

        foreach ($attachmentsByType as $type => $attachmentRows) {
            $provider = $this->getProvider($type);
            if (!$provider) {
                continue;
            }
            $refreshedRows = $provider->refreshAttachments($attachmentRows);
            foreach ($refreshedRows as $refreshedRow) {
                $resultRowsByID[$refreshedRow["AttachmentID"]] = $refreshedRow;
            }
        }

        $resultsOrdered = [];
        foreach ($attachmentIDsOrdered as $attachmentID) {
            if (isset($resultRowsByID[$attachmentID])) {
                $resultsOrdered[] = $resultRowsByID[$attachmentID];
            }
        }
        return $resultsOrdered;
    }

    public function refreshStale(array $attachmentRows): array
    {
        $attachmentIDsOrdered = array_column($attachmentRows, "AttachmentID");
        $staleAttachments = array_filter($attachmentRows, function ($attachment) {
            $provider = $this->getProvider($attachment["Type"]);
            if (!$provider) {
                return false;
            }
            $dateUpdated = new \DateTime($attachment["DateUpdated"] ?? $attachment["DateInserted"]);
            $refreshSeconds = $provider->getRefreshTimeSeconds();

            $cutoffDate = CurrentTimeStamp::getDateTime()->modify("-{$refreshSeconds} seconds");
            if ($dateUpdated->getTimestamp() > $cutoffDate->getTimestamp()) {
                return false;
            }
            return true;
        });

        $attachmentsByID = array_column($attachmentRows, null, "AttachmentID");
        $refreshed = $this->refreshAttachments($staleAttachments);
        $refreshedByID = array_column($refreshed, null, "AttachmentID");

        $results = [];
        foreach ($attachmentsByID as $attachmentID => $attachment) {
            $results[] = $refreshedByID[$attachmentID] ?? $attachment;
        }
        return $results;
    }

    /**
     * Given a list of attachment rows, normalize them, filter them to known providers, and validate them.
     *
     * @param array $attachments
     * @return array
     */
    public function normalizeAttachments(array $attachments): array
    {
        $normalizedAttachments = [];
        $schema = Schema::parse([":a" => \AttachmentModel::getAttachmentSchema()]);
        foreach ($attachments as $attachment) {
            $type = $attachment["Type"];
            $provider = $this->getProvider($type);
            if (!$provider) {
                continue;
            }

            if (!$provider->hasPermissions()) {
                // Current user doesn't have access.
                continue;
            }

            // Some standard normalization
            $normalized = $attachment;
            $normalized["attachmentType"] = $provider->getTypeName();
            $normalized["sourceUrl"] = $normalized["SourceURL"];
            $normalized = array_merge($normalized, \AttachmentModel::splitForeignID($normalized["ForeignID"]));

            try {
                $normalized = $provider->normalizeAttachment($normalized);
                $normalized["DateUpdated"] = $normalized["DateUpdated"] ?? $normalized["DateInserted"];
                $normalized["LastModifiedDate"] = $normalized["LastModifiedDate"] ?? $normalized["DateInserted"];

                if ($lastModifiedDate = $normalized["LastModifiedDate"] ?? null) {
                    $normalized["metadata"][] = [
                        "labelCode" => "Last Modified",
                        "value" => (new \DateTime($lastModifiedDate))->format(\DateTime::RFC3339_EXTENDED),
                        "format" => "date-time",
                    ];
                }

                $normalized = ArrayUtils::camelCase($normalized);
                $normalizedAttachments[] = $normalized;
            } catch (\Throwable $ex) {
                if (DebugUtils::isTestMode()) {
                    throw $ex;
                } else {
                    ErrorLogger::warning($ex, ["attachment", $attachment["Type"]]);
                }
            }
        }

        $result = $schema->validate($normalizedAttachments);
        return $result;
    }
}
