<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\AutomationRules\Trigger;

use DateTimeImmutable;
use DateInterval;
use DiscussionModel;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Schema\RangeExpression;

abstract class TimedAutomationTrigger extends AutomationTrigger
{
    /**
     * Get the date range for the time interval.
     *
     * @param array $triggerValue
     * @param DateTimeImmutable|null $lastRunDate
     * @return RangeExpression
     */
    protected function getTimeBasedDateRange(
        array $triggerValue,
        ?DateTimeImmutable $lastRunDate = null
    ): RangeExpression {
        /**
         * Logic overview for the time based date range calculation
         * 1. Get the current date
         * 2. Calculate the end date by subtracting the trigger time threshold from the current date
         * 3. If this is an initial or manual run, $timeSinceLastRun will always be null, so use the max time threshold if it exists
         * 4. If max time threshold does not exist, that mean we need to find records within the time threshold and a minute range(exactly same time)
         * 5. If max time threshold exists, use that to calculate the "since date" by subtracting the max time threshold from the current date
         * 6. If last run exists, use that to calculate the "since date" by subtracting the threshold time from the last run time.
         */

        $currentDate = new DateTimeImmutable();
        $endDate = $currentDate->sub(
            DateInterval::createFromDateString(
                $triggerValue["triggerTimeThreshold"] . " " . $triggerValue["triggerTimeUnit"]
            )
        );
        // If there was no last run, use current trigger interval value
        if ($lastRunDate === null) {
            if (empty($triggerValue["maxTimeThreshold"])) {
                // We need to find records with in the minute range
                $sinceDate = $endDate->sub(DateInterval::createFromDateString("1 minute"));
            } else {
                $sinceDate = $currentDate->sub(
                    DateInterval::createFromDateString(
                        $triggerValue["maxTimeThreshold"] . " " . $triggerValue["maxTimeUnit"]
                    )
                );
            }
        } else {
            $sinceDate = $lastRunDate->sub(
                DateInterval::createFromDateString(
                    $triggerValue["triggerTimeThreshold"] . " " . $triggerValue["triggerTimeUnit"]
                )
            );
        }
        return new RangeExpression(">=", $sinceDate, "<=", $endDate);
    }

    /**
     * Get Schema for the time interval.
     *
     * @return array
     */
    public static function getTimeIntervalSchema(): array
    {
        return [
            "triggerTimeThreshold:i" => [
                "minimum" => 1,
                "step" => 1,
                "x-control" => SchemaForm::textBox(
                    new FormOptions(
                        "Trigger Delay",
                        "Set the duration after which the rule will trigger.  Whole numbers only.",
                        "",
                        "Set the duration something needs to exist and meet the rule criteria for prior to the the rule triggering and acting upon it"
                    ),
                    "number"
                ),
            ],
            "triggerTimeUnit:s" => [
                "enum" => ["hour", "day", "week", "year"],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Trigger Time Unit", "Select the time unit."),
                    new StaticFormChoices([
                        "hour" => "Hour",
                        "day" => "Day",
                        "week" => "Week",
                        "year" => "Year",
                    ])
                ),
            ],
            "maxTimeThreshold:i?" => [
                "step" => 1,
                "x-control" => SchemaForm::textBox(
                    new FormOptions(
                        "Oldest Retrieved Content Cap",
                        "Any data older than this will be excluded from triggering the rule.  Whole numbers only.",
                        "",
                        "For the initial run or when running this rule once, there may be data that you feel goes too far into the past to act on. This number is the cut-off time; anything older than this will not be included in the run."
                    ),
                    "number"
                ),
            ],
            "maxTimeUnit:s?" => [
                "enum" => ["hour", "day", "week", "year"],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Oldest Content Unit", "Select the time unit."),
                    new StaticFormChoices([
                        "hour" => "Hour",
                        "day" => "Day",
                        "week" => "Week",
                        "year" => "Year",
                    ])
                ),
            ],
        ];
    }

    /**
     *  Get TimeInterval parse Schema
     */
    public static function getTimeIntervalParseSchema(): array
    {
        return [
            "maxTimeThreshold:i?" => [
                "type" => "integer",
                "nullable" => true,
            ],
            "maxTimeUnit:s?" => [
                "type" => "string",
                "nullable" => true,
                "enum" => ["hour", "day", "week", "year"],
            ],
            "triggerTimeThreshold:i",
            "triggerTimeUnit" => [
                "type" => "string",
                "nullable" => false,
                "enum" => ["hour", "day", "week", "year"],
            ],
        ];
    }

    /**
     * Add validation for time parameters in the schema
     *
     * @param Schema $schema
     * @return void
     */
    protected static function addTimedValidations(Schema &$schema)
    {
        $schema
            ->addValidator("maxTimeThreshold", function ($maxTimeThreshold, ValidationField $field) {
                if (
                    !empty($maxTimeThreshold) &&
                    (!is_numeric($maxTimeThreshold) ||
                        floor($maxTimeThreshold) !== ceil($maxTimeThreshold) ||
                        $maxTimeThreshold < 0)
                ) {
                    $field->addError("Oldest Retrieved Content Cap should be positive whole numbers only.");
                    return Invalid::value();
                }
                return $maxTimeThreshold;
            })
            ->addValidator("triggerTimeThreshold", function ($triggerTimeThreshold, ValidationField $field) {
                if (empty($triggerTimeThreshold)) {
                    $field->addError("missingField");
                    return Invalid::value();
                } elseif (
                    !is_numeric($triggerTimeThreshold) ||
                    floor($triggerTimeThreshold) !== ceil($triggerTimeThreshold) ||
                    $triggerTimeThreshold < 0
                ) {
                    $field->addError("Trigger Delay should be positive whole numbers only.");
                    return Invalid::value();
                }
                return $triggerTimeThreshold;
            });
    }

    /**
     * @inheridoc
     */
    public static function getDiscussionSchema(): Schema
    {
        $formChoices = [];
        $enum = DiscussionModel::discussionTypes();
        foreach ($enum as $key => $value) {
            $formChoices[$value["apiType"]] = $key;
        }

        $schema = self::getTimeIntervalSchema();
        $schema["postType"] = [
            "type" => "array",
            "items" => [
                "type" => "string",
            ],
            "default" => array_keys($formChoices),
            "enum" => array_keys($formChoices),
            "x-control" => SchemaForm::dropDown(
                new FormOptions("Post Type", "Select a post type."),
                new StaticFormChoices($formChoices),
                null,
                true
            ),
        ];

        return Schema::parse($schema);
    }

    /**
     * Get the trigger value schema
     *
     * @return Schema
     */
    public static function getDiscussionTriggerValueSchema(): Schema
    {
        $triggerSchema = Schema::parse(
            array_merge(self::getTimeIntervalParseSchema(), [
                "postType" => [
                    "type" => "array",
                    "items" => ["type" => "string"],
                    "nullable" => false,
                ],
            ])
        )->addValidator("postType", function ($postTypes, ValidationField $field) {
            $validPostTypes = array_values(array_filter(array_column(\DiscussionModel::discussionTypes(), "apiType")));
            $failed = false;
            if (!is_array($postTypes) || empty($postTypes)) {
                $failed = true;
            } else {
                foreach ($postTypes as $type) {
                    if (!in_array($type, $validPostTypes)) {
                        $failed = true;
                    }
                }
            }
            if ($failed) {
                $field->addError("Invalid post type, Valid post types are: " . json_encode($validPostTypes));
                return Invalid::value();
            }
            return $postTypes;
        });
        self::addTimedValidations($triggerSchema);
        return $triggerSchema;
    }

    /**
     * Get the trigger post/patch Schema for time based triggers
     *
     * @return Schema
     */
    public static function getTimedTriggerSchema(): Schema
    {
        return Schema::parse([
            "triggerType:s" => [
                "enum" => [static::getType()],
            ],
            "triggerValue:o" => static::getTriggerValueSchema(),
        ])->addValidator("triggerValue", function ($postFields, ValidationField $field) {
            if (!empty($postFields["maxTimeThreshold"]) && empty($postFields["maxTimeUnit"])) {
                $field->setName("triggerValue.maxTimeUnit")->addError("Field is required.", [
                    "code" => 403,
                ]);
                return Invalid::value();
            }
            if (
                !empty($postFields["maxTimeUnit"]) &&
                !empty($postFields["triggerTimeUnit"]) &&
                !empty($postFields["triggerTimeThreshold"]) &&
                !empty($postFields["maxTimeThreshold"])
            ) {
                $maxTimeThreshold = strtotime("+{$postFields["maxTimeThreshold"]}" . " " . $postFields["maxTimeUnit"]);
                $triggerTimeThreshold = strtotime(
                    "+{$postFields["triggerTimeThreshold"]}" . " " . $postFields["triggerTimeUnit"]
                );
                if ($maxTimeThreshold < $triggerTimeThreshold) {
                    $field
                        ->setName("triggerValue.maxTimeThreshold")
                        ->addError("Oldest Retrieved Content Cap should be greater than Trigger Time Threshold.");
                    return Invalid::value();
                }
            }
            return $postFields;
        });
    }

    /**
     * @inheridoc
     */
    public function getWhereArray(array $triggerValue, ?DateTimeImmutable $lastRunDate = null): array
    {
        return [];
    }

    /**
     * Get the trigger value schema for time based triggers
     *
     * @return Schema
     */
    abstract public static function getTriggerValueSchema(): Schema;
}
