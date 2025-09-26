/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import {
    fetchEditorOverviewComponent,
    useEditorSchemaDefaultsEnhancer,
} from "@dashboard/layout/editor/LayoutEditor.overviews";
import { LayoutEditorAssetUtils } from "@dashboard/layout/editor/LayoutEditorAssetUtils";
import { LayoutEditorContents, LayoutEditorPath } from "@dashboard/layout/editor/LayoutEditorContents";
import {
    LayoutEditorDirection,
    LayoutEditorSelection,
    LayoutEditorSelectionMode,
    LayoutEditorSelectionState,
} from "@dashboard/layout/editor/LayoutEditorSelection";
import { LayoutEditorWidgetWrapper } from "@dashboard/layout/editor/LayoutEditorWidgetContext";
import { LayoutEditorWidgetMeta } from "@dashboard/layout/editor/LayoutEditorWidgetMeta";
import LayoutSectionsThumbnails from "@dashboard/layout/editor/thumbnails/LayoutSectionsThumbnails";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import {
    IHydratedEditableWidgetProps,
    ILayoutCatalog,
    ILayoutDraft,
    LayoutSectionID,
    LayoutViewType,
    type ILayoutEditorWidgetPath,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { FauxWidget } from "@dashboard/layout/overview/LayoutOverview";
import { LayoutOverviewSkeleton } from "@dashboard/layout/overview/LayoutOverviewSkeleton";
import { useEditorRolePreviewContext } from "@dashboard/roles/EditorRolePreviewContext";
import {
    DndContext,
    DragOverlay,
    KeyboardSensor,
    MouseSensor,
    pointerWithin,
    rectIntersection,
    TouchSensor,
    useSensor,
    useSensors,
} from "@dnd-kit/core";
import { snapCenterToCursor } from "@dnd-kit/modifiers";
import { LayoutLookupContext, LayoutRenderer } from "@library/features/Layout/LayoutRenderer";
import Container, { ContainerContextReset } from "@library/layout/components/Container";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { StickyContextProvider, useStickyContext } from "@library/modal/StickyContext";
import { LINK_CONTEXT_DEFAULTS, LinkContext } from "@library/routing/links/LinkContextProvider";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { visibility } from "@library/styles/styleHelpersVisibility";
import { useUniqueID } from "@library/utility/idUtils";
import { Icon } from "@vanilla/icons";
import { useFocusWatcher } from "@vanilla/react-utils";
import React, { useContext, useEffect, useMemo, useRef, useState } from "react";

const EditorContext = React.createContext<{
    layoutViewType: LayoutViewType;
    editorContents: LayoutEditorContents;
    editorSelection: LayoutEditorSelection;
    draggingWidgetPath: ILayoutEditorWidgetPath | null;
    setDraggingWidgetPath: (path: ILayoutEditorWidgetPath | null) => void;
    debugDroppables?: boolean;
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
    const stickyPortalRef = useRef<HTMLDivElement | null>(null);

    const { editorContents, editorSelection, draggingWidgetPath, setDraggingWidgetPath } = useLayoutEditor();
    const [initialSectionID, setInitialSectionID] = useState<LayoutSectionID | null>(null);

    const sensors = useSensors(
        useSensor(MouseSensor, {
            activationConstraint: {
                delay: 100,
                tolerance: 5,
            },
        }),
        useSensor(TouchSensor, {
            activationConstraint: {
                delay: 100,
                tolerance: 5,
            },
        }),
        useSensor(KeyboardSensor),
    );

    useEffect(() => {
        // We need to pre-hydrate a section with required assets
        if (!editorContents.validate().isValid && Object.keys(catalog.assets).length > 0) {
            if (catalog.layoutViewType === "categoryList" || catalog.layoutViewType === "nestedCategoryList") {
                editorContents.insertSection(0, LayoutEditorAssetUtils.categoryListSection());
            }

            if (catalog.layoutViewType === "discussionCategoryPage") {
                editorContents.insertSection(0, LayoutEditorAssetUtils.categoryAndDiscussionListSection());
            }

            if (catalog.layoutViewType === "discussionList") {
                editorContents.insertSection(0, LayoutEditorAssetUtils.discussionListSection());
            }

            editorSelection.moveSelectionTo(LayoutEditorPath.section(0), LayoutEditorSelectionMode.SECTION);
        }
    }, [catalog.assets, catalog.layoutViewType]);

    useFocusWatcher(ref, (hasFocus, elementFocused) => {
        const focusIsInLayoutEditorModal = elementFocused?.closest("[data-layout-editor-focus-container]");
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
        if (draggingWidgetPath !== null) {
            // we are using drag shortcuts.
            return;
        }
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
                const path = editorSelection.getPath();
                if (
                    editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET &&
                    path !== null &&
                    !LayoutEditorPath.isSpecialWidgetPath(path)
                ) {
                    e.preventDefault();
                    e.stopPropagation();
                    editorSelection.moveSelectionTo(
                        LayoutEditorPath.section(path.sectionIndex),
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

    const propEnhancer = useEditorSchemaDefaultsEnhancer(catalog);
    const classes = layoutEditorClasses.useAsHook();
    const editorRolePreviewContext = useEditorRolePreviewContext();

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

    const hydratedContent = editorContents.hydrate({
        roleIDs:
            editorRolePreviewContext.selectedRoleIDs.length > 0 ? editorRolePreviewContext.selectedRoleIDs : undefined,
    });

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
                        <ContainerContextReset>
                            <StickyContextProvider portalLocation={stickyPortalRef.current}>
                                <LayoutLookupContext.Provider
                                    value={{
                                        fallbackWidget: FauxWidget,
                                        componentFetcher: fetchEditorOverviewComponent,
                                        componentWrapper: LayoutEditorWidgetWrapper,
                                        propEnhancer,
                                    }}
                                >
                                    <LayoutRenderer
                                        allowInternalProps
                                        layout={[
                                            {
                                                $reactComponent: "TitleBar",
                                                $reactProps: {
                                                    ...hydratedContent.titleBar,
                                                    $editorPath: "TitleBar",
                                                },
                                                $fragmentImpls: hydratedContent.titleBar.$fragmentImpls as any,
                                            },
                                        ]}
                                    />
                                    <DndContext
                                        collisionDetection={customCollisionDetectionAlgorithm}
                                        onDragStart={(e) => {
                                            const widgetPath = e.active.data.current?.$editorPath;
                                            if (widgetPath) {
                                                setDraggingWidgetPath(widgetPath);
                                                editorSelection.moveSelectionTo(
                                                    widgetPath,
                                                    LayoutEditorSelectionMode.WIDGET,
                                                );
                                            }
                                        }}
                                        onDragEnd={(e) => {
                                            setDraggingWidgetPath(null);
                                            const sourcePath = e.active.data.current?.$editorPath;
                                            const destinationPath = e.over?.data?.current?.$editorPath;
                                            if (sourcePath && destinationPath) {
                                                editorContents.moveWidget(sourcePath, destinationPath);
                                                editorSelection.moveSelectionTo(
                                                    destinationPath,
                                                    LayoutEditorSelectionMode.WIDGET,
                                                );
                                            }
                                        }}
                                        onDragAbort={() => {
                                            setDraggingWidgetPath(null);
                                        }}
                                        onDragCancel={() => {
                                            setDraggingWidgetPath(null);
                                        }}
                                        sensors={sensors}
                                    >
                                        <LayoutRenderer<IHydratedEditableWidgetProps>
                                            allowInternalProps
                                            layout={hydratedContent.layout}
                                        />
                                        <CustomDragOverlay />
                                    </DndContext>
                                </LayoutLookupContext.Provider>
                            </StickyContextProvider>
                        </ContainerContextReset>
                    </DeviceProvider>
                </LinkContext.Provider>
                <div ref={stickyPortalRef}></div>
            </div>
        </>
    );
}

function CustomDragOverlay() {
    const { draggingWidgetPath } = useLayoutEditor();
    const stickyContext = useStickyContext();

    return stickyContext.mountStickyPortal(
        <DragOverlay
            style={{ height: 60, width: "auto" }}
            // Drop animations look super janky because our droppables are not the same size as the widgets.
            dropAnimation={null}
            modifiers={[snapCenterToCursor]}
        >
            {draggingWidgetPath && (
                <div
                    style={{
                        background: "#fff",
                        border: singleBorder(),
                        ...shadowHelper().toolbar(),
                        padding: "4px 12px",
                        display: "flex",
                        alignItems: "center",
                        justifyContent: "center",
                        gap: 12,
                        borderRadius: 6,
                    }}
                >
                    <LayoutEditorWidgetMeta widgetPath={draggingWidgetPath} />
                    <Icon icon={"move-drag"} />
                </div>
            )}
        </DragOverlay>,
    );
}

function customCollisionDetectionAlgorithm(args) {
    // Bail out if keyboard activated
    if (!args.pointerCoordinates) {
        return rectIntersection(args);
    }
    // First, let's see if there are any collisions with the pointer
    const pointerCollisions = pointerWithin(args);

    return pointerCollisions;
}

function LayoutEditorContextProvider(props: React.PropsWithChildren<IProps>) {
    const { draft, onDraftChange, children } = props;
    const { layoutViewType } = draft;

    const catalog = useLayoutCatalog(layoutViewType);

    const [draggingWidgetPath, setDraggingWidgetPath] = useState<ILayoutEditorWidgetPath | null>(null);

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
                    ...contents.getEditSpec(),
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
                debugDroppables: false,
                layoutViewType: draft.layoutViewType,
                editorContents: contents,
                editorSelection: selection,
                draggingWidgetPath:
                    selection.getMode() === LayoutEditorSelectionMode.WIDGET ? draggingWidgetPath : null,
                setDraggingWidgetPath,
            }}
        >
            {children}
        </EditorContext.Provider>
    );
}
