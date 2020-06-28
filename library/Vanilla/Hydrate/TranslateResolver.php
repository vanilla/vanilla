<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Hydrate;

use Garden\Hydrate\Resolvers\AbstractDataResolver;
use Garden\Schema\Schema;

/**
 * A resolver that translates strings.
 */
class TranslateResolver extends AbstractDataResolver {
    /**
     * @var \Gdn_Locale
     */
    private $locale;

    /**
     * TranslateResolver constructor.
     *
     * @param \Gdn_Locale $locale
     */
    public function __construct(\Gdn_Locale $locale) {
        $this->locale = $locale;
        $this->schema = new Schema([
            'type' => 'object',
            'properties' => [
                'code' => ['type' => 'string'],
                'default' => ['type' => 'string'],
            ],
            'required' => ['code'],
        ]);
    }


    /**
     * {@inheritDoc}
     */
    protected function resolveInternal(array $data, array $params) {
        $result = $this->locale->translate($data['code'], $data['default'] ?? false);
        return $result;
    }
}
