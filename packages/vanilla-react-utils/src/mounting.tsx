/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ReactDOM from "react-dom";
import { forceRenderStyles } from "typestyle";
import { ReactElement } from "react";

export interface IComponentMountOptions {
    overwrite?: boolean;
    clearContents?: boolean;
}

interface IPortal {
    target: HTMLElement;
    component: React.ReactElement;
}

const portals: IPortal[] = [];

const PORTAL_MANAGER_ID = "vanillaPortalManager";
type PortalContextType = React.FC<{ children?: React.ReactNode }>;
let PortalContext: PortalContextType = props => {
    return <React.Fragment>{props.children}</React.Fragment>;
};

export function applySharedPortalContext(context: PortalContextType) {
    PortalContext = context;
    renderPortals();
}

/**
 * Component for managing all mounted components from a single wrapped context.
 *
 * This allows a shared context provider to be applied to parts of the site.
 */
function PortalManager() {
    return (
        <div>
            <PortalContext>
                {portals.map((portal, i) => {
                    return (
                        <React.Fragment key={i}>
                            {ReactDOM.createPortal(portal.component, portal.target)}
                        </React.Fragment>
                    );
                })}
            </PortalContext>
        </div>
    );
}

function renderPortals(callback?: () => void) {
    // Ensure we have our modal container.
    let container = document.getElementById(PORTAL_MANAGER_ID);
    if (!container) {
        container = document.createElement("div");
        container.id = PORTAL_MANAGER_ID;
        document.body.appendChild(container);
    }

    ReactDOM.render(<PortalManager />, container, callback);
}

// Make a mount root in base of the document.

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
    if (options?.clearContents) {
        target.innerHTML = "";
    }

    if (options && options.overwrite) {
        const container = document.createElement("span");
        cleanupContainer = container;
        target.parentElement!.insertBefore(container, target);
        mountPoint = container;
    }
    portals.push({ target: mountPoint, component });

    renderPortals(() => {
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
 * @param asRealPortal Whether or not we should render this as a real portal, or one managed by the portal manager.
 */
export function mountPortal(element: ReactElement<any>, containerID: string, asRealPortal: boolean = false) {
    // Ensure we have our modal container.
    let container = document.getElementById(containerID);
    if (!container) {
        container = document.createElement("div");
        container.id = containerID;
        document.body.appendChild(container);
    }

    if (asRealPortal) {
        return ReactDOM.createPortal(element, container);
    } else {
        return new Promise(resolve => mountReact(element, container!, () => resolve()));
    }
}
