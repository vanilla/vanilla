<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill;

use Vanilla\Formatting\Quill\Blots\AbstractBlot;
use Vanilla\Formatting\TextFragmentInterface;
use Vanilla\Formatting\TextFragmentType;

/**
 * Class BlotPointerTextFragment
 */
class BlotPointerTextFragment implements TextFragmentInterface
{
    /**
     * @var AbstractBlot
     */
    private $blot;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $type;

    /**
     * BlotPointerTextFragment constructor.
     *
     * @param AbstractBlot $blot
     * @param string $path
     * @param string $type
     */
    public function __construct(AbstractBlot $blot, string $path, string $type = TextFragmentType::TEXT)
    {
        $this->blot = $blot;
        $this->path = $path;
        $this->type = $type;
    }

    /**
     * @inheritDoc
     */
    public function getInnerContent(): string
    {
        return $this->blot->getCurrentOperationField($this->path);
    }

    /**
     * Set the text of the fragment.
     *
     * @param string $text
     */
    public function setInnerContent(string $text)
    {
        $this->blot->setCurrentOperationField($this->path, $text);
    }

    /**
     * @inheritDoc
     */
    public function getFragmentType(): string
    {
        return $this->type;
    }
}
