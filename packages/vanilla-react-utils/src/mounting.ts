/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReactDOM from "react-dom";
import { forceRenderStyles } from "typestyle";

/**
 * Mount a root component of a React tree.
 *
 * - ReactDOM render.
 * - Typestyle render.
 *
 * If the overwrite option is passed this component will replace the components you passed as target.
 *
 * Default Mode:
 * <div><TARGET /></div> -> <div><TARGET><REACT></TARGET><div>
 *
 * Overwrite Mode:
 * <div><TARGET /></div> -> <div><REACT/></div>
 */
export function mountReact(
    component: React.ReactElement,
    target: HTMLElement,
    callback?: () => void,
    options?: { overwrite: boolean },
) {
    let mountPoint = target;
    let cleanupContainer: HTMLElement | undefined;
    if (options && options.overwrite) {
        const container = document.createElement("span");
        cleanupContainer = container;
        target.parentElement!.insertBefore(container, target);
        mountPoint = container;
    }
    ReactDOM.render(component, mountPoint, () => {
        if (cleanupContainer) {
            target.remove();
            if (cleanupContainer.firstElementChild) {
                cleanupContainer.parentElement!.insertBefore(cleanupContainer.firstElementChild, cleanupContainer);
                cleanupContainer.remove();
                target.remove();
            }
        }
        forceRenderStyles();
        callback && callback();
    });
}
