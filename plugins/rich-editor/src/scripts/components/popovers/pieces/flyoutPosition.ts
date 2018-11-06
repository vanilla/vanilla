/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/*
 * Utility function to get positioning of flyout based on props.
 */
export function flyoutPosition(renderAbove: boolean, renderLeft: boolean): any {
    const bottom = renderAbove ? "100%" : undefined;
    const top = !renderAbove ? "100%" : undefined;
    const left = !renderLeft ? "0" : undefined;
    const right = renderLeft ? "0" : undefined;
    return { top, right, bottom, left };
}
