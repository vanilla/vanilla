/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

type IBreakpointVar = {
    breakpointUILabel: string;
} & Record<string, any>;

export function breakpointVariables(breakpoints: Record<string, IBreakpointVar>) {
    return { breakpoints, breakpointUIEnabled: false };
}
