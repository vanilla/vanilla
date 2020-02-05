/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReactDOM from "react-dom";
import { forceRenderStyles } from "typestyle";
import { ReactElement } from "react";

export interface IComponentMountOptions {
    overwrite?: boolean;
}

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
    options?: IComponentMountOptions,
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

/**
 * Mount a modal with ReactDOM. This is only needed at the top level context.
 *
 * If you are already in a react context, just use `<Modal />`.
 * Note: Using this will clear any other modals mounted with this component.
 *
 * @param element The <Modal /> element to render.
 * @param containerID The container to render the modal into. Defaults to modal container.
 * @param asPortal Whether or not we should render as a portal or a render.
 */
export function mountPortal(
    element: ReactElement<any>,
    containerID: string,
    asPortal: boolean = false,
    overwrite: boolean = true,
) {
    // Ensure we have our modal container.
    let container = document.getElementById(containerID);
    if (!container) {
        container = document.createElement("div");
        container.id = containerID;
        document.body.appendChild(container);
    } else if (overwrite) {
        ReactDOM.unmountComponentAtNode(container);
    }

    if (asPortal) {
        return ReactDOM.createPortal(element, container);
    } else {
        return new Promise(resolve => {
            ReactDOM.render(element, container, () => resolve());
        });
    }
}
