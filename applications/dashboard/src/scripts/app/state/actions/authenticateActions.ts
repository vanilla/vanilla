/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

// NOTE: each constant must have an associated function
export const AUTHENTICATE_AUTHENTICATORS_GET = "AUTHENTICATE_AUTHENTICATORS_GET";
export const AUTHENTICATE_AUTHENTICATORS_SET = "AUTHENTICATE_AUTHENTICATORS_SET";

export const authenticatorsGet = () => ({
    type: AUTHENTICATE_AUTHENTICATORS_GET,
});

export const authenticatorsSet = response => ({
    type: AUTHENTICATE_AUTHENTICATORS_SET,
    payload: response.data,
});
