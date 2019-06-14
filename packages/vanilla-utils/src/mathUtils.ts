/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Get various information about a fraction.
 *
 * @param numerator The fraction numerator.
 * @param denominator The fraction denominator.
 */
export function simplifyFraction(numerator: number, denominator: number) {
    const findGCD = (a, b) => {
        return b ? findGCD(b, a % b) : a;
    };
    const gcd = findGCD(numerator, denominator);

    numerator = numerator / gcd;
    denominator = denominator / gcd;

    return {
        numerator,
        denominator,
        shorthand: denominator + ":" + numerator,
    };
}
