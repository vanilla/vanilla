/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { FragmentEditorCommunication } from "@dashboard/appearance/fragmentEditor/FragmentEditorCommunication";
import { FragmentEditorParser } from "@dashboard/appearance/fragmentEditor/FragmentEditorParser";
import { EditorRolePreviewProvider } from "@dashboard/roles/EditorRolePreviewContext";
import { css, cx } from "@emotion/css";
import { ErrorBoundary } from "@library/errorPages/ErrorBoundary";
import { bodyStyleMixin } from "@library/layout/bodyStyles";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import Message from "@library/messages/Message";
import { LinkContext } from "@library/routing/links/LinkContextProvider";
import { useQueryParam } from "@library/routing/routingUtils";
import { useThemeCache } from "@library/styles/themeCache";
import {
    EditorThemePreviewContext,
    EditorThemePreviewOverrides,
    EditorThemePreviewProvider,
} from "@library/theming/EditorThemePreviewContext";
import {
    getRegisteredFragments,
    injectInjectables,
    type IFragmentPreviewData,
} from "@library/utility/fragmentsRegistry";
import { createLoadableComponent, useGlobalClass } from "@vanilla/react-utils";
import { Component, createElement, useEffect, useMemo, useState } from "react";
import { MemoryRouter } from "react-router";

export default function FragmentEditorPreviewPage() {
    const fragmentType = useQueryParam<string | null>("fragmentType", null);

    const FragmentPreviewWrapper = useMemo(() => {
        if (!fragmentType) {
            return DefaultPreviewWrapper;
        }

        const wrapperFn = getRegisteredFragments()[fragmentType]?.previewWrapper;

        if (!wrapperFn) {
            return DefaultPreviewWrapper;
        }

        return createLoadableComponent({
            loadFunction: wrapperFn,
            fallback: () => null,
        });
    }, [fragmentType]);

    const [javascript, setJavascript] = useState("");
    const [css, setCss] = useState("");
    const [previewData, setPreviewData] = useState<IFragmentPreviewData | null>(null);
    const [alignment, setAlignment] = useState<FragmentEditorCommunication.PreviewAlignment>("none");
    const [previewThemeID, setPreviewThemeID] = useState<string | null>(null);
    const [previewRoleIDs, setPreviewRoleIDs] = useState<number[] | null>(null);

    const [DynamicComponent, setDynamicComponent] = useState<React.ComponentType<any> | null>(null);

    const Communication = useMemo(() => {
        return new FragmentEditorCommunication(window, window.parent ?? window.opener);
    }, []);

    const [error, setError] = useState<Error | null>(null);
    const [renderCount, setRenderCount] = useState(0);

    let dynamicElement =
        DynamicComponent &&
        createElement(DynamicComponent, {
            ...previewData?.data,
        });

    if (dynamicElement) {
        dynamicElement = (
            <FragmentPreviewWrapper previewData={previewData?.data} previewProps={previewData?.previewProps}>
                {dynamicElement}
            </FragmentPreviewWrapper>
        );

        if (previewRoleIDs != null) {
            dynamicElement = (
                <EditorRolePreviewProvider selectedRoleIDs={previewRoleIDs}>{dynamicElement}</EditorRolePreviewProvider>
            );
        }
    }

    useEffect(() => {
        const unload = Communication.onMessage(async (message) => {
            if (message.type === "contentUpdate") {
                if (message.javascript) {
                    setJavascript(message.javascript);

                    try {
                        // eslint-disable-next-line no-var
                        var Component = await FragmentEditorParser.parseJs(message.javascript);
                        setError(null);
                    } catch (e) {
                        setError(e);
                        return;
                    }

                    // Some components move focus on unmount, we don't want that to happen.
                    disableFocusing();

                    setDynamicComponent(() => Component);
                    setRenderCount((count) => count + 1);
                }

                if (message.css) {
                    setCss(message.css);
                }

                if (message.previewData) {
                    setPreviewData(message.previewData);
                }
            }

            if (message.type === "previewSettings") {
                if (message.alignment) {
                    setAlignment(message.alignment);
                }

                if (message.previewThemeID) {
                    setPreviewThemeID(message.previewThemeID);
                }

                if (message.previewRoleIDs) {
                    setPreviewRoleIDs(message.previewRoleIDs);
                }
            }

            if (message.type === "rerender") {
                setRenderCount((count) => count + 1);
            }
        });

        // Send over initial ack
        Communication.sendMessage({
            type: "previewLoadedAck",
        });

        return unload;
    }, [Communication]);

    useEffect(() => {
        restoreFocusing();
    }, [Component]);

    useEffect(() => {
        injectInjectables();
    }, []);

    const classes = previewClasses();

    return (
        <>
            <EditorThemePreviewProvider previewedThemeID={previewThemeID}>
                <EditorThemePreviewOverrides>
                    <FragmentPreviewRoot className={alignment === "center" ? classes.centeredRoot : undefined}>
                        <MemoryRouter>
                            <LinkContext.Provider
                                value={{
                                    linkContexts: [""],
                                    isDynamicNavigation: () => {
                                        return true;
                                    },
                                    pushSmartLocation: () => {},
                                    makeHref: () => {
                                        return "";
                                    },
                                    areLinksDisabled: true,
                                }}
                            >
                                {css && <style dangerouslySetInnerHTML={{ __html: css }} />}
                                {!dynamicElement && (
                                    <div>
                                        <LoadingRectangle width={"100%"} height={200} />
                                    </div>
                                )}
                                {error && <Message error={error} />}

                                <ErrorBoundary key={renderCount}>{DynamicComponent && dynamicElement}</ErrorBoundary>
                            </LinkContext.Provider>
                        </MemoryRouter>
                    </FragmentPreviewRoot>
                </EditorThemePreviewOverrides>
            </EditorThemePreviewProvider>
        </>
    );
}

function FragmentPreviewRoot(props: { children?: React.ReactNode; className?: string }) {
    const classes = previewClasses.useAsHook();

    useGlobalClass(classes.body);

    return <div className={cx(classes.root, props.className)}>{props.children}</div>;
}

function DefaultPreviewWrapper(props: { previewData?: any; previewProps?: any; children: React.ReactNode }) {
    return <>{props.children}</>;
}

const previewClasses = useThemeCache(() => {
    return {
        body: css({
            ...bodyStyleMixin(),

            "& .fullBackground": {
                display: "none",
            },
        }),
        root: css({
            minHeight: "100vh",
            ...bodyStyleMixin(),
            padding: 16,
        }),
        centeredRoot: css({
            display: "flex",
            justifyContent: "center",
            alignItems: "center",
            height: "100%",
        }),
    };
});

/**
 * Note: This is a workaround for components that have focus on mount/unmount behaviour.
 *
 * The user's focus is likely in the text editor, not in the preview and we don't want to the preview pane to ever steal focus from the text editor.
 *
 * We remove the focus method from the element prototype so that when a component tries to call focus on itself, it does nothing.
 *
 * Then we restore it after the component is mounted.
 */

let originalElementFocus: typeof HTMLElement.prototype.focus = HTMLElement.prototype.focus;
function disableFocusing() {
    HTMLElement.prototype.focus = () => {
        // Do nothing
    };
}

function restoreFocusing() {
    HTMLElement.prototype.focus = originalElementFocus;
}
