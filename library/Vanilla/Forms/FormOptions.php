<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forms;

/**
 * Class FormOptions
 *
 * @package Vanilla\Forms
 */
class FormOptions
{
    /** @var string */
    private $description;

    /** @var string */
    private $label;

    /** @var string */
    private $placeholder;

    /** @var string */
    private $tooltip;

    /**
     * FormOptions constructor.
     *
     * @param string $description
     * @param string $label
     * @param string $placeholder
     * @param string $tooltip
     */
    public function __construct(
        string $label = "",
        string $description = "",
        string $placeholder = "",
        string $tooltip = ""
    ) {
        $this->description = $description;
        $this->label = $label;
        $this->placeholder = $placeholder;
        $this->tooltip = $tooltip;
    }

    /**
     * Get form option description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Get form option label.
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Get form option placeholder.
     *
     * @return string
     */
    public function getPlaceHolder(): string
    {
        return $this->placeholder;
    }

    /**
     * Get form option tooltip.
     *
     * @return string
     */
    public function getTooltip(): string
    {
        return $this->tooltip;
    }
}
