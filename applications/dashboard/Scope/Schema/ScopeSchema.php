<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Scope\Schema;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Vanilla\Dashboard\Scope\Models\ScopeModel;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\ArrayUtils;

class ScopeSchema
{
    /**
     * Applies schema params for saving scoped records.
     *
     * @param Schema $schema
     * @return void
     */
    public static function applyInputSchema(Schema $schema): void
    {
        $schema
            ->merge(
                Schema::parse([
                    "scope:o?" => [
                        "categoryIDs:a?" => ["items" => ["type" => "integer"]],
                        "siteSectionIDs:a?" => ["items" => ["type" => "string"]],
                    ],
                    "scopeType:s?" => ["enum" => [ScopeModel::SCOPE_TYPE_GLOBAL, ScopeModel::SCOPE_TYPE_SCOPED]],
                ])
            )
            ->addValidator("scope.categoryIDs", \CategoryModel::createCategoryIDsValidator())
            ->addValidator("scope.siteSectionIDs", function ($siteSectionIDs, ValidationField $field) {
                $siteSectionModel = \Gdn::getContainer()->get(SiteSectionModel::class);
                $validSiteSectionIDs = array_map(
                    fn(\Vanilla\Contracts\Site\SiteSectionInterface $siteSection) => $siteSection->getSectionID(),
                    $siteSectionModel->getAll()
                );
                $siteSectionIDs = array_unique($siteSectionIDs);
                $invalidSiteSectionIDs = array_diff($siteSectionIDs, $validSiteSectionIDs);
                if (!empty($invalidSiteSectionIDs)) {
                    $field->addError(
                        "The following site sections are not valid: " . implode(", ", $invalidSiteSectionIDs)
                    );
                    return Invalid::value();
                }
                return true;
            })
            ->addFilter("", function ($data) {
                if (!ArrayUtils::isArray($data)) {
                    return $data;
                }
                if (isset($data["scopeType"]) && $data["scopeType"] === ScopeModel::SCOPE_TYPE_GLOBAL) {
                    $data["scope"] = ["categoryIDs" => [], "siteSectionIDs" => []];
                }
                return $data;
            });
    }

    /**
     * Applies schema params for displaying scoped records.
     *
     * @param Schema $schema
     * @return void
     */
    public static function applyOutputSchema(Schema $schema): void
    {
        $schema->merge(
            Schema::parse([
                "scope:o?" => ["categoryIDs:a?", "siteSectionIDs:a?", "allowedCategoryIDs:a?"],
                "scopeType:s?",
            ])
        );
    }

    /**
     * Applies schema params for filtering by scope.
     *
     * @param Schema $schema
     * @return void
     */
    public static function applyFilterSchema(Schema $schema): void
    {
        $schema->merge(
            Schema::parse([
                "scope:o?" => [
                    "categoryIDs:a?" => [
                        "items" => [
                            "type" => "integer",
                        ],
                        "style" => "form",
                        "description" => "Filter by specific categoryIDs this tag is scoped to.",
                    ],
                    "siteSectionIDs:a?" => [
                        "items" => [
                            "type" => "string",
                        ],
                        "style" => "form",
                        "description" => "Filter by specific siteSectionIDs this tag is scoped to.",
                    ],
                ],
                "scopeType:a?" => [
                    "items" => [
                        "type" => "string",
                        "enum" => [ScopeModel::SCOPE_TYPE_GLOBAL, ScopeModel::SCOPE_TYPE_SCOPED],
                    ],
                    "style" => "form",
                    "description" => "Filter by scope type. Can include 'global', 'restricted', or both.",
                ],
            ])
        );
    }
}
