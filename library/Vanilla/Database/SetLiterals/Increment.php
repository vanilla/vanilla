<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Database\SetLiterals;

/**
 * A data class that represents an incremented value.
 *
 * Instances of this class are meant to pass to `Gdn_SQLDriver::set()` or to models in order to increment a field.
 * Here are some examples:
 *
 * ```php
 * Gdn::sql()->put('Discussion', ['CountComments' => new Increment(1)]);
 *
 * $discussionModel->setFieldCount($id, ['CountComments' => new Increment(1)]);
 * ```
 */
class Increment extends SetLiteral {
    private $amount;

    /**
     * Increment constructor.
     *
     * @param int $amount
     */
    public function __construct(int $amount) {
        $this->amount = $amount;
    }

    /**
     * @return int
     */
    public function getAmount(): int {
        return $this->amount;
    }

    /**
     * {@inheritDoc}
     */
    public function toSql(\Gdn_SQLDriver $sql, string $escapedFieldName): string {
        if ($this->amount === 0) {
            return '';
        } elseif ($this->amount > 0) {
            $expr = "+$this->amount";
        } else {
            $expr = $this->amount;
        }

        return "$escapedFieldName $expr";
    }
}
