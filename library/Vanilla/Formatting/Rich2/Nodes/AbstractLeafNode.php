<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Rich2\Nodes;

/**
 * This is used for nodes that don't have child nodes and have their own render logic.
 */
abstract class AbstractLeafNode extends AbstractNode
{
    /**
     * @inheritdoc
     */
    protected function getHtmlStart(): string
    {
        return "";
    }

    /**
     * @inheritdoc
     */
    protected function getHtmlEnd(): string
    {
        return "";
    }

    /**
     * @inheritdoc
     */
    public function render(): string
    {
        return $this->getHtmlStart() . $this->renderHtmlContent() . $this->getHtmlEnd();
    }

    /**
     * @inheritdoc
     */
    public function renderText(): string
    {
        return $this->getTextStart() . $this->renderTextContent() . $this->getTextEnd();
    }

    /**
     * Render the HTML content for this leaf node.
     *
     * @return string
     */
    abstract protected function renderHtmlContent(): string;

    /**
     * Render the text content for this leaf node.
     *
     * @return string
     */
    abstract protected function renderTextContent(): string;
}
