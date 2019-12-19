<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill;

use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\Quill\Blots;
use Vanilla\Formatting\Quill\Formats;
use Vanilla\Formatting\Quill\Blots\AbstractBlot;

/**
 * Class for parsing Quill Deltas into BlotGroups.
 *
 * @see https://github.com/quilljs/delta Information on quill deltas.
 */
class Parser {

    /** @var array Represents the operations of an "empty" body. */
    const SINGLE_NEWLINE_CONTENTS = [[
        'insert' => "\n",
    ]];

    const PARSE_MODE_NORMAL = "normal";
    const PARSE_MODE_QUOTE = "quote";

    const BREAK_OPERATION = [
        "breakpoint" => true,
    ];

    const LINE_BLOTS = [
        Blots\Lines\BlockquoteLineTerminatorBlot::class,
        Blots\Lines\ListLineTerminatorBlot::class,
        Blots\Lines\SpoilerLineTerminatorBlot::class,
        Blots\Lines\HeadingTerminatorBlot::class,
    ];

    /** @var string[] The registered blot classes */
    private $blotClasses = [];

    /** @var string[] The registered formats classes */
    private $formatClasses = [];

    /**
     * Add a new embed type.
     *
     * @param string $blotClass The blot to register.
     *
     * @see AbstractBlot
     * @return $this
     */
    public function addBlot(string $blotClass) {
        $this->blotClasses[] = $blotClass;

        return $this;
    }

    /**
     * Register all of the bu ilt in blots and formats to parse. Primarily for use in bootstrapping.
     *
     * The embeds NEED to be first here, otherwise something like a blockquote with only a mention in it will
     * match only as a blockquote instead of as a mention.
     */
    public function addCoreBlotsAndFormats() {
        $this
            ->addBlot(Blots\Embeds\ExternalBlot::class)
            ->addBlot(Blots\Embeds\MentionBlot::class)
            ->addBlot(Blots\Embeds\EmojiBlot::class)
            ->addBlot(Blots\Lines\SpoilerLineTerminatorBlot::class)
            ->addBlot(Blots\Lines\BlockquoteLineTerminatorBlot::class)
            ->addBlot(Blots\Lines\ListLineTerminatorBlot::class)
            ->addBlot(Blots\Lines\HeadingTerminatorBlot::class)
            ->addBlot(Blots\Lines\ParagraphLineTerminatorBlot::class)
            ->addBlot(Blots\Lines\CodeLineTerminatorBlot::class)
            ->addBlot(Blots\TextBlot::class)// This needs to be the last one!!!
            ->addFormat(Formats\Link::class)
            ->addFormat(Formats\Bold::class)
            ->addFormat(Formats\Italic::class)
            ->addFormat(Formats\Code::class)
            ->addFormat(Formats\Strike::class)
        ;
    }

    /**
     * Add a new embed type.
     *
     * @param string $formatClass The Format class to register.
     *
     * @see AbstractFormat
     * @return $this
     */
    public function addFormat(string $formatClass) {
        $this->formatClasses[] = $formatClass;

        return $this;
    }

    /**
     * Parse the operations into an array of Groups.
     *
     * @param array $operations The operations to parse.
     * @param string $parseMode One of the supported parse modes.
     *
     * @see Parser::PARSE_MODE_NORMAL
     * @see Parser::PARSE_MODE_QUOTE
     *
     * @return BlotGroupCollection
     */
    public function parse(array $operations, string $parseMode = self::PARSE_MODE_NORMAL): BlotGroupCollection {
        $this->stripTrailingNewlines($operations);
        $operations = $this->splitPlainTextNewlines($operations);
        return new BlotGroupCollection($operations, $this->blotClasses, $parseMode);
    }

    /**
     * Parse out the usernames of everyone mentioned or quoted in a post.
     *
     * @param array $operations
     * @return string[]
     */
    public function parseMentionUsernames(array $operations): array {
        if (!in_array(Blots\Embeds\MentionBlot::class, $this->blotClasses)
            && !in_array(Blots\Embeds\ExternalBlot::class, $this->blotClasses)
        ) {
            return [];
        }

        $blotGroups = $this->parse($operations);
        $mentionUsernames = [];
        /** @var BlotGroup $blotGroup */
        foreach ($blotGroups as $blotGroup) {
            $mentionUsernames = array_merge($mentionUsernames, $blotGroup->getMentionUsernames());
        }

        return $mentionUsernames;
    }

    /**
     * Attempt to convert a JSON string into an array of operations.
     *
     * @param string $json
     *
     * @return array
     * @throws FormattingException If valid operations could not be produced.
     */
    public static function jsonToOperations(string $json): array {
        // Ensure that empty posts still get some body content.
        // This will ensure that they will render/validate correctly where empty is allowed
        // And that empty bodies by properly caught in length validation.
        if (empty($json)) {
            return self::SINGLE_NEWLINE_CONTENTS;
        }
        $operations = json_decode($json, true);

        $errMessage = "JSON could not be converted into quill operations.\n $json";
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($operations)) {
            throw new FormattingException($errMessage);
        }

        // Also normalizing an empty array to have at least 1 operation.
        if (empty($operations)) {
            return self::SINGLE_NEWLINE_CONTENTS;
        }

        if (!self::areArrayKeysSequentialInts($operations)) {
            throw new FormattingException($errMessage);
        }

        return $operations;
    }

    /**
     * Determine if an array is sequential (IE. not assosciative).
     *
     * @param array $arr
     * @return bool
     */
    private static function areArrayKeysSequentialInts(array &$arr): bool {
        for (reset($arr), $base = 0; key($arr) === $base++;) {
            next($arr);
        }
        return is_null(key($arr));
    }

    /**
     * Get the matching format for a sequence of operations if applicable.
     *
     * @param array $currentOp The current operation.
     * @param array $previousOp The next operation.
     * @param array $nextOp The previous operation.
     *
     * @return Formats\AbstractFormat[] The formats matching the given operations.
     */
    public function getFormatsForOperations(array $currentOp, array $previousOp = [], array $nextOp = []) {
        $formats = [];
        /** @var Formats\AbstractFormat $format */
        foreach ($this->formatClasses as $format) {
            if ($format::matches([$currentOp])) {
                /** @var Formats\AbstractFormat $formatInstance */
                $formats[] = new $format($currentOp, $previousOp, $nextOp);
            }
        }

        return $formats;
    }

    /**
     * Call parse and then simplify each blotGroup into test data.
     *
     * @param array $ops The ops to parse
     *
     * @return array
     */
    public function parseIntoTestData(array $ops): array {
        $parseData = $this->parse($ops);
        $groupData = [];
        foreach ($parseData as $blotGroup) {
            $groupData[] = $blotGroup->getTestData();
        }

        return $groupData;
    }

    /**
     * Determine if an operation is a text insert with no attributes.
     *
     * @param array $op The operation
     *
     * @return bool
     */
    private function isOperationBareInsert(array $op): bool {
        return !array_key_exists("attributes", $op)
            && array_key_exists("insert", $op)
            && is_string($op["insert"]);
    }

    /**
     * Determine if an operation represents the terminating operation ("eg, nextBlot") of an LineBlot).
     *
     * @param array $operation The operation
     *
     * @return bool
     */
    private function isOpALineBlotTerminator(array $operation): bool {
        $validLineBlotClasses = array_intersect(static::LINE_BLOTS, $this->blotClasses);
        /** @var AbstractBlot $blotClass */
        foreach ($validLineBlotClasses as $blotClass) {
            if ($blotClass::matches([$operation])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Split normal text operations with newlines inside of them into their own operations.
     *
     * @param array $operations The operations in questions.
     *
     * @return array The new operations
     */
    private function splitPlainTextNewlines(array $operations): array {
        $newOperations = [];
        foreach ($operations as $opIndex => $op) {
            // Other types of inserts should not get split, they need special handling inside of their blot.
            // Just skip over them.
            if (!$this->isOperationBareInsert($op)) {
                $newOperations[] = $op;
                continue;
            }

            // Split up into an array of newlines (individual) and groups of all other characters.
            preg_match_all("/((\\n)+)|([^\\n]+)/", $op["insert"], $matches);
            $subInserts = $matches[0];
            if (count($subInserts) <= 1) {
                $newOperations[] = $op;
                continue;
            }

            // If we are going from a non-bare insert to a bare on and we have a newline we need to add an empty break.
            $prevOp = $operations[$opIndex - 1] ?? [];
            $needsExtraBreak = $this->isOperationBareInsert($op) && $this->isOpALineBlotTerminator($prevOp) && $subInserts[0] !== "\n";
            if ($needsExtraBreak) {
                $newOperations[] = static::BREAK_OPERATION;
            }

            // Make an operation for each sub insert.
            foreach ($subInserts as $subInsert) {
                $newOperations[] = ["insert" => $subInsert];
            }
        }

        return $newOperations;
    }

    /**
     * Strip trailing whitespace off of the end of rich-editor contents.
     *
     * The last line is always a line terminator so we know that we can strip strip any whitespace and replace it with
     * a single line-terminator.
     *
     * @param array[] $operations The quill operations to loop through.
     */
    private function stripTrailingNewlines(array &$operations) {
        $lastIndex = count($operations) - 1;
        $lastOp = &$operations[$lastIndex];
        if ($lastOp && $this->isOperationBareInsert($lastOp)) {
            $lastOp["insert"] = preg_replace('/\s+$/', "\n", $lastOp["insert"]);
        }
    }

    /**
     * Get all registered blot classes.
     *
     * @return array
     */
    public function getBlotClasses(): array {
        return $this->blotClasses;
    }

    /**
     * Replace all registered blot classes.
     *
     * @param array
     */
    public function setBlotClasses(array $blotClasses) {
        $this->blotClasses = $blotClasses;
    }

    /**
     * Get all registered format classes.
     *
     * @return array
     */
    public function getFormatClasses(): array {
        return $this->formatClasses;
    }

    /**
     * Replace all registered format classes.
     *
     * @param array
     */
    public function setFormatClasses(array $formatClasses) {
        $this->formatClasses = $formatClasses;
    }
}
