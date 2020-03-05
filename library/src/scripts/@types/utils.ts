/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Mark mark all the properies from K in T as optional.
 *
 * @example
 * interface One { one: string }
 * interface Three { one: string, two: string }
 *
 * type Two = Optionalize<Three, One>;
 *
 * // The type of Two will be
 * interface Two { one?: string, two: string }
 */
export type Optionalize<T extends K, K> = Omit<T, keyof K> & Partial<K>;
