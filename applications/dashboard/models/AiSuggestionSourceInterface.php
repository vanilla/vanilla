<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Vanilla\Forms\FormChoicesInterface;

interface AiSuggestionSourceInterface
{
    /**
     * Get the name of the suggestion source.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get data for the exclusion dropdown.
     *
     * @return FormChoicesInterface
     */
    public function getExclusionDropdownChoices(): FormChoicesInterface;

    /**
     * Get the translated label for the toggle for enabling and disabling this suggestion source.
     *
     * @return string
     */
    public function getToggleLabel(): string;

    /**
     * Get the translated label for the dropdown for selecting data to exclude.
     *
     * @return string
     */
    public function getExclusionLabel(): string;

    /**
     * Generate suggestions for a discussion.
     *
     * @param array $discussion a discussion array.
     *
     * @return array Returns the suggestions.
     */
    public function generateSuggestions(array $discussion): array;
}
