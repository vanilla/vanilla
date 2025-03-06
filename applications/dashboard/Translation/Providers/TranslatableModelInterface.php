<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */
namespace MachineTranslation\Providers;

use Gdn_Model;

/**
 * Key functionality required for the Community Machine Translation feature.
 */
interface TranslatableModelInterface
{
    /**
     * Get the content type.
     *
     * @return string
     */
    public function getContentType(): string;

    /**
     * Get the content primary key.
     *
     * @return string
     */
    public function getPrimaryKey(): string;

    /**
     * Get the key of the data object where translatable content is in.
     *
     * @return string
     */
    public function getObjectKey(array $data): string;

    /**
     * Get values of data to translation.
     *
     * @param int|null $primaryID used first to get data
     * @param array|null $data if primaryID is null, use this data
     *
     * @return array
     */
    public function getContentToTranslate(int $primaryID = null, array $data = null): array;

    /**
     * Get array keys of the date content that should be translation/replaced.
     *
     * @return array
     */
    public function getContentKeysToTranslate(): array;
}
