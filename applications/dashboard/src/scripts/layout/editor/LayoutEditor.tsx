/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { fetchEditorOverviewComponent } from "@dashboard/layout/editor/LayoutEditor.overviews";
import { LayoutEditorAssetUtils } from "@dashboard/layout/editor/LayoutEditorAssetUtils";
import { LayoutEditorContents, LayoutEditorPath } from "@dashboard/layout/editor/LayoutEditorContents";
import {
    LayoutEditorDirection,
    LayoutEditorSelection,
    LayoutEditorSelectionMode,
    LayoutEditorSelectionState,
} from "@dashboard/layout/editor/LayoutEditorSelection";
import { LayoutEditorWidgetWrapper } from "@dashboard/layout/editor/LayoutEditorWidgetContext";
import LayoutSectionsThumbnails from "@dashboard/layout/editor/thumbnails/LayoutSectionsThumbnails";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import {
    IHydratedEditableWidgetProps,
    ILayoutCatalog,
    ILayoutDraft,
    LayoutSectionID,
    LayoutViewType,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { FauxWidget } from "@dashboard/layout/overview/LayoutOverview";
import { LayoutOverviewSkeleton } from "@dashboard/layout/overview/LayoutOverviewSkeleton";
import { LayoutLookupContext, LayoutRenderer } from "@library/features/Layout/LayoutRenderer";
import Container, { ContainerContextReset, ContainerWidthContextProvider } from "@library/layout/components/Container";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { LinkContext, LINK_CONTEXT_DEFAULTS } from "@library/routing/links/LinkContextProvider";
import { visibility } from "@library/styles/styleHelpersVisibility";
import { useUniqueID } from "@library/utility/idUtils";
import { useFocusWatcher } from "@vanilla/react-utils";
import React, { useContext, useEffect, useMemo, useRef, useState } from "react";

const EditorContext = React.createContext<{
    layoutViewType: LayoutViewType;
    editorContents: LayoutEditorContents;
    editorSelection: LayoutEditorSelection;
}>({} as any);

export function useLayoutEditor() {
    return useContext(EditorContext);
}

interface IProps {
    catalog: ILayoutCatalog;
    onDraftChange: (draft: ILayoutDraft) => void;
    draft: ILayoutDraft;
}

export function LayoutEditor(props: IProps) {
    return (
        <LayoutEditorContextProvider {...props}>
            <LayoutEditorImpl {...props} />
        </LayoutEditorContextProvider>
    );
}

function LayoutEditorImpl(props: IProps) {
    const { catalog } = props;
    const ref = useRef<HTMLDivElement | null>(null);

    const { editorContents, editorSelection } = useLayoutEditor();
    const [initialSectionID, setInitialSectionID] = useState<LayoutSectionID | null>(null);

    useEffect(() => {
        //TODO:we should make this more dynamic for other view types as well when we have more required assets
        //right now only for discussion list, we need to pre-hydrate a section with discussionList asset
        if (
            !editorContents.validate().isValid &&
            Object.keys(catalog.assets).length > 0 &&
            catalog.layoutViewType === "discussionList"
        ) {
            editorContents.insertSection(0, LayoutEditorAssetUtils.discussionList());
            editorSelection.moveSelectionTo(LayoutEditorPath.section(0), LayoutEditorSelectionMode.SECTION);
        }
    }, [catalog.assets, catalog.layoutViewType]);

    useFocusWatcher(ref, (hasFocus, elementFocused) => {
        const focusIsInLayoutEditorModal = elementFocused?.closest("[data-layout-editor-modal]");
        if (elementFocused && !hasFocus && !focusIsInLayoutEditorModal) {
            editorSelection.stashState();
        } else if (hasFocus && elementFocused === ref.current) {
            editorSelection.restoreState();
        }
    });

    /**
     * Keyboard handler for arrow up and arrow down.
     */
    function onKeyDown(e: React.KeyboardEvent) {
        if (e.target === ref.current) {
            // Focus is currently on ourself.
            if (e.key === "Enter") {
                e.preventDefault();
                e.stopPropagation();
                editorSelection.setMode(LayoutEditorSelectionMode.SECTION);
            }

            return;
        }

        const isEventTargetRelevant =
            e.target instanceof HTMLElement && e.target.hasAttribute("data-layout-editor-focusable");
        if (!isEventTargetRelevant) {
            return;
        }
        // Handle keys.
        switch (e.key) {
            case "ArrowDown":
                e.preventDefault();
                e.stopPropagation();
                editorSelection.moveSelectionInDirection(LayoutEditorDirection.DOWN);
                break;
            case "ArrowUp":
                e.preventDefault();
                e.stopPropagation();
                editorSelection.moveSelectionInDirection(LayoutEditorDirection.UP);
                break;
            case "ArrowRight":
                e.preventDefault();
                e.stopPropagation();
                editorSelection.moveSelectionInDirection(LayoutEditorDirection.RIGHT);
                break;
            case "ArrowLeft":
                e.preventDefault();
                e.stopPropagation();
                editorSelection.moveSelectionInDirection(LayoutEditorDirection.LEFT);
                break;
            case "Enter":
                if (
                    editorSelection.getMode() === LayoutEditorSelectionMode.SECTION &&
                    editorSelection.getPath() !== null
                ) {
                    e.preventDefault();
                    e.stopPropagation();
                    editorSelection.moveSelectionTo(editorSelection.getPath(), LayoutEditorSelectionMode.WIDGET);
                }
                break;
            case "Escape":
                if (
                    editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET &&
                    editorSelection.getPath() !== null
                ) {
                    e.preventDefault();
                    e.stopPropagation();
                    editorSelection.moveSelectionTo(
                        LayoutEditorPath.section(editorSelection.getPath().sectionIndex),
                        LayoutEditorSelectionMode.SECTION,
                    );
                } else if (
                    editorSelection.getMode() === LayoutEditorSelectionMode.SECTION ||
                    editorSelection.getMode() === LayoutEditorSelectionMode.SECTION_ADD
                ) {
                    ref.current?.focus();
                    editorSelection.setMode(LayoutEditorSelectionMode.NONE);
                }
                break;
        }
    }

    const descriptionID = useUniqueID("description");

    if (editorContents.getSectionCount() === 0 && editorContents.validate().isValid) {
        // We are empty. Use the contents.
        const addInitialSection = (sectionID: LayoutSectionID) => {
            editorContents.insertSection(0, {
                $hydrate: sectionID,
            });
            editorSelection.moveSelectionTo(LayoutEditorPath.section(0), LayoutEditorSelectionMode.SECTION);
        };
        const classes = layoutEditorClasses();
        return (
            <form
                className={classes.initialSectionForm}
                onKeyPress={(e) => {
                    if (e.key === "Enter" || e.key === "Space") {
                        e.preventDefault();
                        e.stopPropagation();

                        if (initialSectionID) {
                            addInitialSection(initialSectionID);
                        }
                    }
                }}
            >
                <Container fullGutter narrow maxWidth={800}>
                    <LayoutSectionsThumbnails
                        label={"Start with a Section"}
                        onSectionClick={addInitialSection}
                        sections={catalog.sections}
                        onChange={setInitialSectionID}
                        value={initialSectionID ?? (Object.keys(catalog.sections)[0] as LayoutSectionID)}
                    />
                </Container>
            </form>
        );
    }

    const classes = layoutEditorClasses();

    return (
        <>
            <div className={visibility().visuallyHidden} id={descriptionID}>
                Enter and Escape keys switch modes between between the editor, sections, and widgets. In the section
                mode Arrow Up and Arrow Down navigate between between sections. In the widget mode the arrow keys
                navigate between widgets. Tab navigates into toolbars. Shift Tab navigates out of toolbars.
            </div>
            <div
                ref={ref}
                className={classes.root}
                tabIndex={editorSelection.getMode() === LayoutEditorSelectionMode.NONE ? 0 : -1}
                onKeyDown={onKeyDown}
                aria-label="Layout Editor"
                aria-describedby={descriptionID}
            >
                <LinkContext.Provider value={{ ...LINK_CONTEXT_DEFAULTS, areLinksDisabled: true }}>
                    <DeviceProvider>
                        <ContainerWidthContextProvider
                            // Kludge until we can render the client theme and the dashboard theme in different
                            // parts of the same page.
                            maxWidth={1264}
                        >
                            <ContainerContextReset>
                                <LayoutLookupContext.Provider
                                    value={{
                                        fallbackWidget: FauxWidget,
                                        componentFetcher: fetchEditorOverviewComponent,
                                        componentWrapper: LayoutEditorWidgetWrapper,
                                    }}
                                >
                                    <LayoutRenderer<IHydratedEditableWidgetProps>
                                        allowInternalProps
                                        layout={editorContents.hydrate().layout}
                                    />
                                </LayoutLookupContext.Provider>
                            </ContainerContextReset>
                        </ContainerWidthContextProvider>
                    </DeviceProvider>
                </LinkContext.Provider>
            </div>
        </>
    );
}

function LayoutEditorContextProvider(props: React.PropsWithChildren<IProps>) {
    const { draft, onDraftChange, children } = props;
    const { layoutViewType } = draft;

    const catalog = useLayoutCatalog(layoutViewType);

    // Effect to load the initial
    const [selectionState, setSelectionState] = useState<LayoutEditorSelectionState | null>(null);

    useEffect(() => {
        // Setup initial selection state
        setSelectionState(
            new LayoutEditorSelectionState(
                LayoutEditorPath.section(0),
                LayoutEditorSelectionMode.NONE,
                setSelectionState,
            ),
        );
    }, [setSelectionState]);

    const contents = useMemo(() => {
        if (!catalog) {
            return null;
        }

        return new LayoutEditorContents(
            draft,
            catalog,

            // Pass in the setContents so that we update the state on modifications.
            (contents) =>
                onDraftChange({
                    ...draft,
                    layout: contents.getLayout(),
                }),
        );
    }, [catalog, draft, onDraftChange]);

    const selection = useMemo(() => {
        if (contents === null || selectionState === null) {
            return null;
        }

        return selectionState.withContents(contents);
    }, [selectionState, contents]);

    if (contents === null || selection === null) {
        return <LayoutOverviewSkeleton />;
    }

    return (
        <EditorContext.Provider
            value={{
                layoutViewType: draft.layoutViewType,
                editorContents: contents,
                editorSelection: selection,
            }}
        >
            {children}
        </EditorContext.Provider>
    );
}
