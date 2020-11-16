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
    widgetResolver?: IWidgetResolver;
    bypassPortalManager?: boolean;
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
const portals: IPortal[] = [];

const PORTAL_MANAGER_ID = "vanillaPortalManager";
type PortalContextType = React.FC<{ children?: React.ReactNode }>;
let PortalContext: PortalContextType = (props) => {
    return <React.Fragment>{props.children}</React.Fragment>;
};

export function applySharedPortalContext(context: PortalContextType) {
    PortalContext = context;
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
    hasRendered = true;
    // Ensure we have our modal container.
    let container = document.getElementById(PORTAL_MANAGER_ID);
    if (!container) {
        container = document.createElement("div");
        container.id = PORTAL_MANAGER_ID;
        document.body.appendChild(container);
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
        const doRender = () => {
            ReactDOM.render(<PortalContext>{component}</PortalContext>, target, callback);
        };
        ReactDOM.unmountComponentAtNode(target);
        setImmediate(doRender);
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

export interface IMountable {
    target: HTMLElement;
    component: React.ReactElement;
}

export function mountReactMultiple(components: IMountable[], callback?: () => void, options?: IComponentMountOptions) {
    if (!components.length) {
        return;
    }

    let toCleanup: Array<{ target: HTMLElement; cleanup: HTMLElement }> = [];
    components.forEach(({ component, target }) => {
        let mountPoint = target;
        if (options?.clearContents) {
            target.innerHTML = "";
        }

        if (options && options.overwrite) {
            const container = document.createElement("span");
            toCleanup.push({
                target,
                cleanup: container,
            });
            target.parentElement!.insertBefore(container, target);
            mountPoint = container;
        }
        portals.push({ target: mountPoint, component });
    });

    renderPortals(() => {
        toCleanup.forEach(({ cleanup, target }) => {
            if (cleanup.firstElementChild) {
                cleanup.parentElement!.insertBefore(cleanup.firstElementChild, cleanup);
                cleanup.remove();
                target.remove();
            }
        });
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
        return new Promise((resolve) => mountReact(element, container!, () => resolve()));
    }
}
