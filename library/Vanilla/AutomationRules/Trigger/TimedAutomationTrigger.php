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
use Vanilla\CurrentTimeStamp;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Schema\RangeExpression;
use Vanilla\Forms\FieldMatchConditional;

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

        $currentDate = CurrentTimeStamp::getDateTime();

        $endDate = $currentDate->sub(
            DateInterval::createFromDateString(
                $triggerValue["triggerTimeDelay"]["length"] . " " . $triggerValue["triggerTimeDelay"]["unit"]
            )
        );
        // If there was no last run, use current trigger interval value
        if ($lastRunDate === null) {
            if ($triggerValue["applyToNewContentOnly"] === true) {
                // We need to find records with in the minute range
                $sinceDate = $endDate->sub(DateInterval::createFromDateString("1 minute"));
            } elseif (empty($triggerValue["triggerTimeLookBackLimit"]["length"])) {
                $sinceDate = $endDate->sub(DateInterval::createFromDateString("1 minute"));
            } else {
                $sinceDate = $currentDate->sub(
                    DateInterval::createFromDateString(
                        $triggerValue["triggerTimeLookBackLimit"]["length"] .
                            " " .
                            $triggerValue["triggerTimeLookBackLimit"]["unit"]
                    )
                );
            }
        } else {
            $sinceDate = $lastRunDate->sub(
                DateInterval::createFromDateString(
                    $triggerValue["triggerTimeDelay"]["length"] . " " . $triggerValue["triggerTimeDelay"]["unit"]
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
            "triggerTimeDelay" => [
                "type" => "object",
                "required" => true,
                "x-control" => SchemaForm::timeDuration(
                    new FormOptions(
                        "Trigger Delay",
                        "Set the duration after which the rule will trigger.  Whole numbers only.",
                        "",
                        "Set the duration something needs to exist and meet the rule criteria for prior to the the rule triggering and acting upon it"
                    ),
                    null,
                    ["hour", "day", "week", "year"]
                ),
                "properties" => [
                    "length" => ["type" => "string"],
                    "unit" => ["type" => "string"],
                ],
            ],
        ];
    }

    /**
     * Get additional settings schema for the trigger, this will go in the end of trigger/action form fields.
     *
     * @return array
     */
    public static function getAdditionalSettingsSchema(): array
    {
        return [
            "applyToNewContentOnly" => [
                "type" => "boolean",
                "default" => false,
                "x-control" => SchemaForm::checkBox(
                    new FormOptions(
                        "Apply to new content only",
                        "When enabled, this rule will only be applied to new content that meets the trigger criteria."
                    )
                ),
            ],
            "triggerTimeLookBackLimit" => [
                "type" => "object",
                "x-control" => SchemaForm::timeDuration(
                    new FormOptions("Look-back Limit", "Do not apply the rule to content that is older than this.", ""),
                    new FieldMatchConditional(
                        "additionalSettings.triggerValue.applyToNewContentOnly",
                        Schema::parse([
                            "type" => "boolean",
                            "const" => false,
                        ])
                    ),
                    ["hour", "day", "week", "year"]
                ),
                "properties" => [
                    "length" => ["type" => "string"],
                    "unit" => ["type" => "string"],
                ],
            ],
        ];
    }

    /**
     *  Get TimeInterval parse Schema
     */
    public static function getTimeIntervalParseSchema(): array
    {
        return [
            "applyToNewContentOnly:b" => [
                "type" => "boolean",
                "default" => false,
            ],
            "triggerTimeDelay:o" => [
                "length:i",
                "unit:s" => [
                    "enum" => ["hour", "day", "week", "year"],
                ],
            ],
            "triggerTimeLookBackLimit:o?" => [
                "length:i?" => [
                    "nullable" => true,
                ],
                "unit:s?" => [
                    "nullable" => true,
                    "enum" => ["hour", "day", "week", "year"],
                ],
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
            ->addFilter("triggerTimeLookBackLimit", function ($triggerTimeLookBackLimit) {
                if (isset($triggerTimeLookBackLimit["unit"]) && $triggerTimeLookBackLimit["unit"] == "") {
                    $triggerTimeLookBackLimit["unit"] = "hour";
                }
                return $triggerTimeLookBackLimit;
            })
            ->addValidator("triggerTimeDelay", function ($triggerTimeDelay, ValidationField $field) {
                if (empty($triggerTimeDelay["length"]) || empty($triggerTimeDelay["unit"])) {
                    $field->addError("missingField");
                    return Invalid::value();
                } elseif (
                    !is_numeric($triggerTimeDelay["length"]) ||
                    floor($triggerTimeDelay["length"]) !== ceil($triggerTimeDelay["length"]) ||
                    $triggerTimeDelay["length"] < 0
                ) {
                    $field->addError("Trigger Delay should be positive whole numbers only.");
                    return Invalid::value();
                }
                return $triggerTimeDelay;
            })
            ->addValidator("triggerTimeLookBackLimit", function ($triggerTimeLookBackLimit, ValidationField $field) {
                if (
                    (!empty($triggerTimeLookBackLimit["length"]) && !is_numeric($triggerTimeLookBackLimit["length"])) ||
                    floor($triggerTimeLookBackLimit["length"]) !== ceil($triggerTimeLookBackLimit["length"]) ||
                    $triggerTimeLookBackLimit["length"] < 0
                ) {
                    $field->addError("Look-back Limit should be positive whole numbers only.");
                    return Invalid::value();
                }
                return $triggerTimeLookBackLimit;
            });
    }

    /**
     * @inheritdoc
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
            "required" => true,
            "default" => array_keys($formChoices),
            "enum" => array_keys($formChoices),
            "x-control" => SchemaForm::dropDown(
                new FormOptions("Post Type"),
                new StaticFormChoices($formChoices),
                null,
                true
            ),
        ];

        $schema["additionalSettings"] = self::getAdditionalSettingsSchema();

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
        ])
            ->addFilter("triggerValue", function ($triggerValue) {
                if (
                    isset($triggerValue["applyToNewContentOnly"]) &&
                    $triggerValue["applyToNewContentOnly"] === true &&
                    !empty($triggerValue["triggerTimeLookBackLimit"])
                ) {
                    unset($triggerValue["triggerTimeLookBackLimit"]);
                }
                return $triggerValue;
            })
            ->addValidator("triggerValue", function ($postFields, ValidationField $field) {
                if ($postFields["applyToNewContentOnly"] === false) {
                    if (empty($postFields["triggerTimeLookBackLimit"]["length"])) {
                        $field->setName("triggerValue.triggerTimeLookBackLimit")->addError("Field is required.", [
                            "code" => 403,
                        ]);
                        return Invalid::value();
                    }
                    if (empty($postFields["triggerTimeLookBackLimit"]["unit"])) {
                        $field->setName("triggerValue.triggerTimeLookBackLimit")->addError("Field is required.", [
                            "code" => 403,
                        ]);
                        return Invalid::value();
                    }

                    $maxTime = strtotime(
                        "+{$postFields["triggerTimeLookBackLimit"]["length"]}" .
                            " " .
                            $postFields["triggerTimeLookBackLimit"]["unit"]
                    );
                    $triggerTime = strtotime(
                        "+{$postFields["triggerTimeDelay"]["length"]}" . " " . $postFields["triggerTimeDelay"]["unit"]
                    );
                    if ($maxTime < $triggerTime) {
                        $field
                            ->setName("triggerValue.triggerTimeLookBackLimit")
                            ->addError("Look-back Limit should be greater than Trigger Delay.");
                        return Invalid::value();
                    }
                }
                return $postFields;
            });
    }

    /**
     * provide `where` condition based on trigger values and date offset
     *
     * @param array $triggerValue
     * @param DateTimeImmutable|null $lastRunDate
     * @return array
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
