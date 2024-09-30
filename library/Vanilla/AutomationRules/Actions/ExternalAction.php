<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Actions;

use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;

/**
 * Action that interacts with a 3rd party service.
 */
abstract class ExternalAction extends AutomationAction
{
    protected array $postRecord;

    abstract public function execute(): bool;

    /**
     * Prevent the escalation actions from being executed on existing content.
     *
     * @param Schema $schema
     * @return void
     */
    public static function getPostPatchSchema(Schema &$schema): void
    {
        $schema->addValidator("trigger", function ($postFields, ValidationField $field) {
            if (
                isset($postFields["triggerValue"]["applyToNewContentOnly"]) &&
                !$postFields["triggerValue"]["applyToNewContentOnly"]
            ) {
                $field->addError("This action can only be applied to new content.");
                return Invalid::value();
            }

            return $postFields;
        });
    }

    /**
     * @inheridoc
     */
    public function setPostRecord(array $postRecord): void
    {
        $this->postRecord = $postRecord;
    }

    /**
     * @inheridoc
     */
    public function getPostRecord(): array
    {
        return $this->postRecord;
    }

    /**
     * Escalate a post to an external service as a long-runner.
     *
     * @param array $actionValue
     * @param array $object Discussion or Comment Data.
     * @return bool
     */
    public function executeLongRunner(array $actionValue, array $object): bool
    {
        $this->setPostRecord($object);
        return $this->execute();
    }
}
