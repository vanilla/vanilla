<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;

/**
 * Schema extension that adds profiling to the validation.
 */
class TracedSchema extends Schema
{
    /**
     * @param Schema $originalSchema
     */
    public function __construct(Schema $originalSchema)
    {
        parent::__construct();
        $this->schema = $originalSchema->schema;
        $this->flags = $originalSchema->flags;
        if ($id = $originalSchema->getID()) {
            $this->setID($id);
        }
        $this->validators = $originalSchema->validators;
        $this->filters = $originalSchema->filters;
    }

    /**
     * @override To add tracing to the validation.
     * @inheritdoc
     */
    public function validate($data, $sparse = false)
    {
        $spanData = [];
        if ($id = $this->getID()) {
            $spanData = [
                "schemaID" => $id,
            ];
        }
        $span = Timers::instance()->startGeneric("validateSchema", $spanData);
        try {
            $result = parent::validate($data, $sparse);
            return $result;
        } finally {
            $span->finish();
        }
    }
}
