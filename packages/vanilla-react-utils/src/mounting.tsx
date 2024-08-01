/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import ReactDOM from "react-dom";
import { ReactElement } from "react";
import { globalValueRef } from "@vanilla/utils";

export interface IComponentMountOptions {
    overwrite?: boolean;
    clearContents?: boolean;
    widgetResolver?: IWidgetResolver;
    bypassPortalManager?: boolean;
    unmountBeforeRender?: boolean;
}

export interface IWidgetOptions {
    $type?: string; // the component to get, optional because coming from API
    children?: IWidgetOptions[]; // widgets to mount as children
    [x: string]: any; // can take additional properties
}

/**
 * Defines an interface for a function that will turn widget options into props that can be used to render a component.
 */
export interface IWidgetResolver {
    (options: IWidgetOptions): {
        [key: string]: any;
    };
}

interface IPortal {
    target: HTMLElement;
    component: React.ReactElement;
}

let hasRendered = false;
const portals = globalValueRef<IPortal[]>("portals", []);

const PORTAL_MANAGER_ID = "vanillaPortalManager";
type PortalContextType = React.FC<{ children?: React.ReactNode }>;
let portalContextRef = globalValueRef<PortalContextType>("PortalContext", (props) => {
    return <React.Fragment>{props.children}</React.Fragment>;
});

export function applySharedPortalContext(context: PortalContextType) {
    portalContextRef.set(context);
    if (hasRendered) {
        // Re-render the portals. We never want to be the first to initialize rendering though.
        renderPortals();
    }
}

/**
 * Component for managing all mounted components from a single wrapped context.
 *
 * This allows a shared context provider to be applied to parts of the site.
 */
function PortalManager() {
    const PortalContext = portalContextRef.current();
    return (
        <div>
            <PortalContext>
                {portals.current().map((portal, i) => {
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

export function renderPortals(callback?: () => void) {
    hasRendered = true;
    // Ensure we have our modal container.
    let container = document.getElementById(PORTAL_MANAGER_ID);
    if (!container) {
        container = document.createElement("div");
        container.id = PORTAL_MANAGER_ID;
        const profiler = document.querySelector("#profiler");
        if (profiler) {
            document.body.insertBefore(container, profiler);
        } else {
            document.body.appendChild(container);
        }
    }

    ReactDOM.render(<PortalManager />, container, callback);
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
    options?: IComponentMountOptions & { bypassPortalManager?: boolean },
) {
    if (options?.bypassPortalManager) {
        const PortalContext = portalContextRef.current();
        const doRender = () => {
            ReactDOM.render(<PortalContext>{component}</PortalContext>, target, callback);
        };
        if (options?.unmountBeforeRender) {
            ReactDOM.unmountComponentAtNode(target);
        }
        setTimeout(doRender, 0);
        return;
    }

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
    portals.current().push({ target: mountPoint, component });

    renderPortals(() => {
        if (cleanupContainer) {
            target.remove();
            if (cleanupContainer.firstElementChild) {
                cleanupContainer.parentElement!.insertBefore(cleanupContainer.firstElementChild, cleanupContainer);
                cleanupContainer.remove();
                target.remove();
            }
        }
        callback && callback();
    });
}

export interface IMountable {
    target: HTMLElement;
    component: React.ReactElement;
    overwrite?: boolean;
}

export function mountReactMultiple(components: IMountable[], callback?: () => void, options?: IComponentMountOptions) {
    if (!components.length) {
        callback && callback();
        return;
    }

    const elementIndexesToMove: Array<{ parent: Element; initialParentChildCount: number; target: Element }> = [];

    components.forEach((mountable) => {
        const { component, target } = mountable;
        let mountPoint = target;
        if (options?.clearContents) {
            target.innerHTML = "";
        }

        if (options?.overwrite || mountable.overwrite) {
            /**
             * Default mounting behaviour
             *
             * <Parent>
             *     <Target> // Events are bound here
             *         <ReactElement />
             *     <Target />
             * </Parent>
             *
             * What we want with overwrite is:
             *
             * <Parent> // Events are bound here
             *    <ReactElement />
             * </Parent>
             */

            mountPoint = mountable.target.parentElement!;
            elementIndexesToMove.push({
                parent: mountPoint,
                initialParentChildCount: mountPoint.children.length,
                target,
            });
        }
        portals.current().push({ target: mountPoint, component });
    });

    renderPortals(() => {
        // Loop through the elements by parent.

        elementIndexesToMove.forEach((movable) => {
            // Relocate the nodes to their proper places on the page.
            // Without this, widgets may not appear in their intended locations.
            const nodeToMove = movable.parent.children.item(movable.initialParentChildCount);
            if (!nodeToMove) {
                return;
            }

            if (movable.target.parentElement !== movable.parent) {
                console.warn("Movable parent does not container target", {
                    parent: movable.parent.outerHTML,
                    target: movable.target.outerHTML,
                });
                return;
            }

            movable.parent.insertBefore(nodeToMove, movable.target);
            movable.target.remove();
        });

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
        return new Promise<void>((resolve) => mountReact(element, container!, () => resolve()));
    }
}
