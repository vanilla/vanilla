/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import type {
    IHydratedLayoutFragmentImpl,
    IHydratedLayoutFragmentImpls,
} from "@library/features/Layout/LayoutRenderer.types";
import Message from "@library/messages/Message";
import { useThemeCacheID } from "@library/styles/themeCache";
import { getThemeVariables } from "@library/theming/getThemeVariables";
import { useThemeOverrideContext } from "@library/theming/ThemeOverrideContext";
import { siteUrl } from "@library/utility/appUtils";
import { injectInjectables } from "@library/utility/fragmentsRegistry";
import { logError, stableObjectHash } from "@vanilla/utils";
import React, { createContext, Fragment, useContext, useEffect, useLayoutEffect, useMemo } from "react";
import ReactDOM from "react-dom";

type FragmentType = string;
type FragmentUUID = string;

export const FragmentImplContext = createContext<{
    impls: Record<FragmentType, React.ComponentType<any>>;
    systemResetFragmentTypes: string[];
}>({
    impls: {},
    systemResetFragmentTypes: [],
});

interface IFragmentImplProviderPops {
    $fragmentImpls: IHydratedLayoutFragmentImpls;
    children?: React.ReactNode;
}

export function FragmentImplContextProvider(props: IFragmentImplProviderPops) {
    const result = useMemo(() => {
        const impls: Record<FragmentType, React.ComponentType<any>> = {};
        const systemResetFragmentTypes: string[] = [];
        for (const [fragmentType, hydrateImpl] of Object.entries(props.$fragmentImpls)) {
            if (hydrateImpl.fragmentUUID === "styleguide") {
                continue;
            }
            if (hydrateImpl.fragmentUUID === "system") {
                systemResetFragmentTypes.push(fragmentType);
                continue;
            }

            impls[fragmentType] = createLazyFragmentComponent(fragmentType, hydrateImpl);
        }

        return { impls, systemResetFragmentTypes };
    }, [stableObjectHash(props.$fragmentImpls)]);

    return <FragmentImplContext.Provider value={result}>{props.children}</FragmentImplContext.Provider>;
}

export function useFragmentImpl<ComponentProps>(fragmentType: FragmentType): React.ComponentType<ComponentProps> | null;
export function useFragmentImpl<ComponentProps>(
    fragmentType: FragmentType,
    defaultImpl: React.ComponentType<ComponentProps>,
): React.ComponentType<ComponentProps>;
export function useFragmentImpl<ComponentProps>(
    fragmentType: FragmentType,
    defaultImpl?: React.ComponentType<ComponentProps>,
): React.ComponentType<ComponentProps> | null {
    useEffect(() => {
        injectInjectables();
    }, []);
    const { impls } = useContext(FragmentImplContext);
    const FragmentImpl = impls[fragmentType];

    return FragmentImpl ?? defaultImpl ?? null;
}

export function useThemeFragmentImpl<ComponentProps>(
    fragmentType: FragmentType,
): React.ComponentType<ComponentProps> | null;
export function useThemeFragmentImpl<ComponentProps>(
    fragmentType: FragmentType,
    defaultImpl: React.ComponentType<ComponentProps>,
): React.ComponentType<ComponentProps>;
export function useThemeFragmentImpl<ComponentProps>(
    fragmentType: FragmentType,
    defaultImpl?: React.ComponentType<ComponentProps>,
): React.ComponentType<ComponentProps> | null {
    const ContextFragmentImpl = useFragmentImpl<any>(fragmentType);
    const { systemResetFragmentTypes } = useContext(FragmentImplContext);

    const { cacheID: themeCacheID } = useThemeCacheID();
    const themeOverrideContext = useThemeOverrideContext();
    const ThemeFragmentImpl: React.ComponentType<any> | null = useMemo(() => {
        const globalFragmentImpls: Record<string, Partial<IHydratedLayoutFragmentImpl>> = themeOverrideContext
            .overridesVariables?.globalFragmentImpls ??
        getThemeVariables()?.globalFragmentImpls ??
        {};

        const globalFragmentImpl = globalFragmentImpls[fragmentType];

        if (!globalFragmentImpl || !globalFragmentImpl.fragmentUUID || globalFragmentImpl.fragmentUUID === "system") {
            // No implemenation.
            return null;
        }

        // We have some theme value set. We may be in the theme editor in which case we don't actually have the full definition, just a partial.
        // Let's stub out the definition.
        // It's a bit less inneficient to do this, but it's a rare case.
        const definition: IHydratedLayoutFragmentImpl = {
            fragmentUUID: globalFragmentImpl.fragmentUUID,
            jsUrl: globalFragmentImpl.jsUrl ?? siteUrl(`/api/v2/fragments/${globalFragmentImpl.fragmentUUID}/js`),
            cssUrl: globalFragmentImpl.cssUrl ?? siteUrl(`/api/v2/fragments/${globalFragmentImpl.fragmentUUID}/css`),
            css: globalFragmentImpl.css,
        };

        return createLazyFragmentComponent(fragmentType, definition);
    }, [fragmentType, themeOverrideContext.themeID, themeCacheID]);

    // Look for the default fragment impl being set to system.
    // The styleguide might be set to a value, but we want to override it with the system one.
    const isSystemOverride = systemResetFragmentTypes.includes(fragmentType);
    if (isSystemOverride) {
        return defaultImpl ?? null;
    }

    return ContextFragmentImpl ?? ThemeFragmentImpl ?? defaultImpl ?? null;
}

const importsByFragmentUUID: Record<FragmentUUID, Promise<{ default: React.ComponentType<any> }>> = {};

export function createLazyFragmentComponent(
    fragmentType: string,
    fragmentImpl: IHydratedLayoutFragmentImpl,
): React.ComponentType<any> {
    const { fragmentUUID, jsUrl, cssUrl, css } = fragmentImpl;
    const LazyComponent = React.lazy(async () => {
        const existingImport = importsByFragmentUUID[fragmentUUID];
        if (existingImport) {
            return existingImport;
        }
        let newImport = (importsByFragmentUUID[fragmentUUID] = import(/* @vite-ignore */ jsUrl!));
        return newImport;
    });

    const implName = `${fragmentType}CustomImpl`;

    (LazyComponent as any).displayName = `Lazy<${implName}>`;
    const FragmentComponent = (props: any) => {
        const styleSuspender = useFragmentStylesheet(fragmentImpl);

        return (
            <ErrorBoundary errorComponent={BadComponentError}>
                {/* Render our stylesheet either as inline CSS (in fully rendered layout pages) or an link tag (in layout editor) */}
                {css &&
                    ReactDOM.createPortal(
                        <style
                            data-fragment-type={fragmentType}
                            data-fragment-uuid={fragmentImpl.fragmentUUID}
                            dangerouslySetInnerHTML={{ __html: css }}
                        />,
                        document.head,
                    )}
                <LazyComponent {...props} />
                {styleSuspender}
            </ErrorBoundary>
        );
    };

    FragmentComponent.displayName = implName;
    return FragmentComponent;
}

function BadComponentError(props: { error: Error }) {
    return <Message title={"Error Loading Fragment"} error={props.error} />;
}

/**
 * Special stylesheet management implementation that:
 *
 * - Ensures the same sheet isn't added multiple times.
 * - Adds a delay before removing a stylesheet to prevent flickering (in case it is added back).
 *
 * Notably there was a flickering if some parent component's key changed and stylesheet was removed and re-added.
 */
class StyleSheetManager {
    private added: Record<string, HTMLLinkElement> = {};
    private pendingRemoval: Record<string, NodeJS.Timeout> = {};

    public add(url: string): Promise<void> | null {
        if (this.pendingRemoval[url]) {
            clearTimeout(this.pendingRemoval[url]);
            delete this.pendingRemoval[url];
        }

        if (Object.keys(this.added).includes(url)) {
            return null;
        }

        // Put in added synchronously to avoid multiple calls to add for the same URL causing multiple link tags to be added.
        const link = document.createElement("link");
        this.added[url] = link;

        return new Promise((resolve) => {
            link.rel = "stylesheet";
            link.href = url;
            link.addEventListener("load", () => {
                // When the stylesheet loads, resolve the promise.
                resolve();
            });
            link.addEventListener("error", () => {
                // Handle error case for loading the stylesheet
                logError(`Failed to load stylesheet: ${url}`);
                // Resolve the promise to avoid hanging
                resolve();
            });
            setTimeout(() => {
                // Max amount of time to wait.
                resolve();
            }, 1000);
            document.head.appendChild(link);
        });
    }

    public remove(url: string) {
        if (Object.keys(this.added).includes(url)) {
            return;
        }

        const link = this.added[url];
        this.pendingRemoval[url] = setTimeout(() => {
            delete this.added[url];
            document.head.removeChild(link);
        }, 100);
    }
}

const stylesheetManager = new StyleSheetManager();

function useFragmentStylesheet(impl: IHydratedLayoutFragmentImpl): React.ReactNode {
    const [suspensePromise, setSuspensePromise] = React.useState<Promise<void> | null>(null);

    const StyleLoaderSuspender = useMemo(() => {
        if (impl.css) {
            return null;
        }
        if (!suspensePromise) {
            // Return null if no suspense promise is set
            return null;
        }

        return createSuspenseComponent(suspensePromise); // Return a lazy component that will suspend until the promise resolves
    }, [suspensePromise]);

    useLayoutEffect(() => {
        if (impl.css) {
            // Nothing to do.
            return;
        }

        setSuspensePromise(stylesheetManager.add(impl.cssUrl!));
        return () => {
            setSuspensePromise(null);
            stylesheetManager.remove(impl.cssUrl!);
        };
    }, [impl.cssUrl]);

    if (StyleLoaderSuspender) {
        return <StyleLoaderSuspender />;
    }
    return null;
}

function createSuspenseComponent(promise: Promise<void>): React.ComponentType<any> {
    // This function creates a lazy component that will suspend until the promise resolves
    return React.lazy(async () => {
        await promise;
        return {
            default: () => {
                return <></>;
            },
        };
    });
}
