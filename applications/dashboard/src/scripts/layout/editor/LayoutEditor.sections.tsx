/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useLayoutEditor } from "@dashboard/layout/editor/LayoutEditor";
import { layoutEditorClasses } from "@dashboard/layout/editor/LayoutEditor.classes";
import { LayoutEditorAddSection } from "@dashboard/layout/editor/LayoutEditorAddSection";
import { LayoutEditorAddWidget } from "@dashboard/layout/editor/LayoutEditorAddWidget";
import { LayoutEditorPath } from "@dashboard/layout/editor/LayoutEditorContents";
import { LayoutEditorSectionToolbar } from "@dashboard/layout/editor/LayoutEditorSectionToolbar";
import { LayoutEditorSelectionMode } from "@dashboard/layout/editor/LayoutEditorSelection";
import { LayoutSectionInfos } from "@dashboard/layout/editor/LayoutSectionInfos";
import {
    IHydratedEditableWidgetProps,
    ILayoutEditorPath,
    type IHydratedEditableSectionProps,
    type ILayoutEditorSectionPath,
    type ILayoutEditorWidgetPath,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { useDroppable } from "@dnd-kit/core";
import { cx } from "@emotion/css";
import PanelWidget from "@library/layout/components/PanelWidget";
import { SectionEvenColumns } from "@library/layout/SectionEvenColumns";
import { SectionFullWidth } from "@library/layout/SectionFullWidth";
import { SectionOneColumn } from "@library/layout/SectionOneColumn";
import SectionThreeColumns from "@library/layout/ThreeColumnSection";
import SectionTwoColumns from "@library/layout/TwoColumnSection";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ClearThemeOverrideContext } from "@library/theming/ThemeOverrideContext";
import { t } from "@vanilla/i18n";
import { useFocusOnActivate, useMeasure, useStatefulRef } from "@vanilla/react-utils";
import React, { useDebugValue, useRef } from "react";

type EditorSectionProps<T extends React.ComponentType> = React.ComponentProps<T> & IHydratedEditableSectionProps;

export function EditorSectionFullWidth(props: EditorSectionProps<typeof SectionFullWidth>) {
    const originalProps = props;
    props = useSectionPropsWithAddButtons(props);

    const { $editorPath, $componentName, $hydrate, ...restProps } = props;
    const { editorSelection, editorContents } = useLayoutEditor();
    const classes = layoutEditorClasses.useAsHook();
    const ref = useStatefulRef<HTMLDivElement | null>(null);
    const { isActive, isSelected, activeClasses } = useSectionFocusRef(props.$editorPath, ref);

    const onClick = useSectionClickHandler(props.$editorPath);
    const isFirstSection = props.$editorPath.sectionIndex === 0;
    const isFullWidth = editorContents.isSectionFullWidth(props.$editorPath);
    const isPreviousFullWidth = editorContents.isSectionFullWidth({
        ...props.$editorPath,
        sectionIndex: props.$editorPath.sectionIndex - 1,
    });

    const hasStaticDecorationBefore = isFullWidth && (isFirstSection || isPreviousFullWidth);
    return (
        <>
            {hasStaticDecorationBefore && <SectionDecorationStatic {...props} />}
            <SectionFullWidth
                {...restProps}
                role={"button"}
                aria-label={`Full width section at position ${props.$editorPath.sectionIndex}`}
                data-layout-editor-focusable
                onClick={onClick}
                className={cx(classes.section(1), "isFullWidth", activeClasses, classes.fullWidth)}
                tabIndex={isSelected ? 0 : -1}
                ref={ref}
                data-layout-selectable={true}
                childrenBefore={
                    <SectionDecorationAbsolute
                        sectionRef={ref}
                        {...originalProps}
                        skipBefore={hasStaticDecorationBefore}
                    />
                }
            />
        </>
    );
}

export function EditorSectionOneColumn(props: EditorSectionProps<typeof SectionOneColumn>) {
    const originalProps = props;
    props = useSectionPropsWithAddButtons(props);
    const { $editorPath, $componentName, $hydrate, ...restProps } = props;

    const classes = layoutEditorClasses.useAsHook();
    const ref = useStatefulRef<HTMLDivElement | null>(null);
    const { isActive, isSelected, activeClasses } = useSectionFocusRef(props.$editorPath, ref);

    const onClick = useSectionClickHandler(props.$editorPath);
    return (
        <SectionOneColumn
            {...restProps}
            role={"button"}
            aria-label={`One column section at position ${props.$editorPath.sectionIndex}`}
            data-layout-editor-focusable
            onClick={onClick}
            className={cx(classes.section(), activeClasses)}
            tabIndex={isSelected ? 0 : -1}
            data-layout-selectable={true}
            contentRef={ref}
            childrenBefore={<SectionDecorationAbsolute sectionRef={ref} {...originalProps} />}
        />
    );
}

export function EditorSectionTwoColumns(props: EditorSectionProps<typeof SectionTwoColumns>) {
    const originalProps = props;
    props = useSectionPropsWithAddButtons(props);

    const { $editorPath, $componentName, $hydrate, ...restProps } = props;
    const classes = layoutEditorClasses.useAsHook();
    const ref = useStatefulRef<HTMLDivElement | null>(null);
    const { isActive, isSelected, activeClasses } = useSectionFocusRef(props.$editorPath, ref);

    const onClick = useSectionClickHandler(props.$editorPath);

    return (
        <SectionTwoColumns
            {...restProps}
            role={"button"}
            aria-label={`Two column section at position ${props.$editorPath.sectionIndex}`}
            data-layout-editor-focusable
            onClick={onClick}
            className={cx(classes.section(), activeClasses)}
            tabIndex={isSelected ? 0 : -1}
            data-layout-selectable={true}
            contentRef={ref}
            childrenBefore={<SectionDecorationAbsolute sectionRef={ref} {...originalProps} />}
        />
    );
}

export function EditorSectionThreeColumns(props: EditorSectionProps<typeof SectionThreeColumns>) {
    const originalProps = props;
    props = useSectionPropsWithAddButtons(props);
    const { $editorPath, $componentName, $hydrate, ...restProps } = props;
    const classes = layoutEditorClasses.useAsHook();
    const ref = useStatefulRef<HTMLDivElement | null>(null);
    const { isSelected, activeClasses } = useSectionFocusRef(props.$editorPath, ref);
    const onClick = useSectionClickHandler(props.$editorPath);

    return (
        <SectionThreeColumns
            {...restProps}
            role={"button"}
            aria-label={`Three column section`}
            data-layout-editor-focusable
            onClick={onClick}
            className={cx(classes.section(), activeClasses)}
            tabIndex={isSelected ? 0 : -1}
            data-layout-selectable={true}
            contentRef={ref}
            childrenBefore={<SectionDecorationAbsolute sectionRef={ref} {...originalProps} />}
        />
    );
}

export function EditorSectionEvenColumns(props: EditorSectionProps<typeof SectionEvenColumns>) {
    const originalProps = props;
    props = useSectionPropsWithAddButtons(props);
    const { $editorPath, $componentName, $hydrate, ...restProps } = props;
    const classes = layoutEditorClasses.useAsHook();
    const ref = useStatefulRef<HTMLDivElement | null>(null);
    const { isActive, isSelected, activeClasses } = useSectionFocusRef(props.$editorPath, ref);
    const onClick = useSectionClickHandler(props.$editorPath);

    return (
        <SectionEvenColumns
            {...props}
            role={"button"}
            aria-label={`Three column section`}
            data-layout-editor-focusable
            onClick={onClick}
            className={cx(classes.section(), activeClasses)}
            tabIndex={isSelected ? 0 : -1}
            data-layout-selectable={true}
            ref={ref}
            childrenBefore={<SectionDecorationAbsolute sectionRef={ref} {...originalProps} />}
        />
    );
}

function useSectionClickHandler(path: ILayoutEditorPath) {
    const { editorSelection } = useLayoutEditor();

    function onClick(e: React.MouseEvent) {
        e.preventDefault();
        e.stopPropagation();
        editorSelection.moveSelectionTo(path, LayoutEditorSelectionMode.SECTION);
    }

    return onClick;
}

/**
 *
 * @param path
 * @param ref
 * @returns A CSS class name to apply.
 */
function useSectionFocusRef(path: ILayoutEditorPath, ref: React.RefObject<HTMLElement>) {
    const { editorSelection } = useLayoutEditor();

    // TODO: Voiceover doesn't properly read these because they aren't their own focusable element.
    // We will likely need to place an actual button inside the contents to receive focus when
    // we are selecting an item.

    // To reproduce the issue
    // - Enable voiceover
    // - Focus the section add button.
    // - Hit the down arrow to focus the section.
    // - Voiceover leaves the focus on the section button

    // Conundrum
    // - The add button is inside of the section.
    // - Voiceover prefers the button to the section.
    // - Voiceover ignores aria-hidden.

    const isCorrectPath = LayoutEditorPath.areSectionPathsEqual(editorSelection.getPath(), path);
    const isSelected = isCorrectPath && editorSelection.getMode() === LayoutEditorSelectionMode.SECTION;
    useFocusOnActivate(ref, isSelected);
    const isActive =
        isCorrectPath &&
        [LayoutEditorSelectionMode.WIDGET, LayoutEditorSelectionMode.SECTION].includes(editorSelection.getMode());
    useDebugValue({
        isActive,
        isCorrectPath,
        isSecureContext,
    });
    return {
        activeClasses: cx({ isActive }),
        isActive,
        isSelected,
    };
}

function SectionDecorationStatic(props: IHydratedEditableSectionProps) {
    const { editorSelection } = useLayoutEditor();
    const isSelected =
        LayoutEditorPath.areSectionPathsEqual(props.$editorPath, editorSelection.getPath()) &&
        [LayoutEditorSelectionMode.SECTION_ADD].includes(editorSelection.getMode());
    const classes = layoutEditorClasses.useAsHook();
    return (
        <>
            {isSelected && (
                <div className={classes.addSectionContextualStatic}>
                    <LayoutEditorAddSection path={props.$editorPath} />
                </div>
            )}
        </>
    );
}

function SectionDecorationAbsolute(
    props: IHydratedEditableSectionProps & {
        skipBefore?: boolean;
        skipAfter?: boolean;
        sectionRef: React.RefObject<HTMLElement | null>;
    },
) {
    const path = props.$editorPath;
    const { editorSelection, editorContents } = useLayoutEditor();
    const classes = layoutEditorClasses.useAsHook();

    const _selectionPath = editorSelection.getPath();
    const selectionPath = LayoutEditorPath.isSpecialWidgetPath(_selectionPath) ? null : _selectionPath;
    const selectedIndex = selectionPath?.sectionIndex ?? 0;

    const isOwnSectionSelected = path.sectionIndex === selectedIndex;

    const countSections = editorContents.getSectionCount();
    const isLastSection = path.sectionIndex === countSections - 1;

    const isSelfSectionSelected =
        editorSelection.getMode() === LayoutEditorSelectionMode.SECTION && isOwnSectionSelected;

    const isFullWidth = editorContents.isSectionFullWidth(props.$editorPath);
    const isPreviousFullWidth = editorContents.isSectionFullWidth({
        ...props.$editorPath,
        sectionIndex: props.$editorPath.sectionIndex - 1,
    });

    const allowColumnInvert = LayoutSectionInfos[props.$hydrate].allowColumnInvert;

    let offset = -20;
    if (isFullWidth) {
        offset = 0;
    } else if (isPreviousFullWidth) {
        offset = -16;
    }

    return (
        <>
            {!props.skipBefore && (
                <div className={classes.addSectionContextualBefore}>
                    <LayoutEditorAddSection path={path} />
                </div>
            )}
            {isLastSection && (
                <div className={classes.addSectionContextualAfter}>
                    <LayoutEditorAddSection path={{ ...path, sectionIndex: path.sectionIndex + 1 }} />
                </div>
            )}
            {!props.skipAfter && isSelfSectionSelected && (
                <ClearThemeOverrideContext>
                    <LayoutEditorSectionToolbar
                        path={props.$editorPath}
                        positionRelativeTo={props.sectionRef.current}
                        allowColumnInvert={allowColumnInvert}
                    />
                </ClearThemeOverrideContext>
            )}
        </>
    );
}

function SectionDroppable(props: { $editorPath: ILayoutEditorWidgetPath }) {
    const { debugDroppables } = useLayoutEditor();
    const droppable = useDroppable({
        id: LayoutEditorPath.droppableID(props.$editorPath),
        data: {
            isWidget: true,
            $editorPath: props.$editorPath,
        },
    });

    const { isOver, setNodeRef } = droppable;
    const globalVars = globalVariables.useAsHook();
    const style: React.CSSProperties = {
        width: "100%",
        transition: "all 0.2s ease",
        backgroundColor: ColorsUtils.colorOut(globalVars.states.hover.highlight),
        color: ColorsUtils.colorOut(globalVars.mainColors.primary),
        fontWeight: "bold",
        border: `3px dashed ${ColorsUtils.colorOut(globalVars.mainColors.primary)}`,
        borderRadius: 6,
        position: "relative",
        zIndex: 10,
        display: "flex",
        alignItems: "center",
        justifyContent: "center",
        ...(isOver || debugDroppables
            ? {
                  height: "80px",
                  opacity: 1,
              }
            : {
                  height: "1px",
                  marginTop: "var(--droppable-inverse-margin, -1px)",
                  opacity: 0,
                  border: "none",
              }),
    };

    return (
        <div
            className={cx("widget-droppable", !isOver && "collapsed")}
            id={LayoutEditorPath.droppableID(props.$editorPath)}
            style={style}
        >
            {isOver && <span>{t("Drop Widget")}</span>}
            <div
                ref={setNodeRef}
                style={{
                    position: "absolute",
                    top: -40,
                    bottom: -40,
                    left: 0,
                    right: 0,
                    width: "100%",
                    minHeight: "80px",
                    height: "calc(100% + 80px)",
                }}
            ></div>
        </div>
    );
}

function useSectionPropsWithAddButtons<T extends IHydratedEditableSectionProps>(props: T): T {
    const { $editorPath } = props;
    const { debugDroppables, editorSelection, editorContents, draggingWidgetPath } = useLayoutEditor();
    const sectionInfo = editorContents.getSectionInfo(props.$editorPath);
    if (!sectionInfo) {
        return props;
    }

    const newProps = { ...props };
    const isRegionSelected =
        LayoutEditorPath.areSectionPathsEqual(props.$editorPath, editorSelection.getPath()) &&
        [LayoutEditorSelectionMode.SECTION, LayoutEditorSelectionMode.WIDGET].includes(editorSelection.getMode());

    // editorSelection
    const shouldAddDroppables = draggingWidgetPath !== null || debugDroppables;

    for (const regionName of sectionInfo.regionNames) {
        if (typeof regionName !== "string") {
            continue;
        }

        const existingChildren = props[regionName] ?? [];
        let newChildren: React.ReactNode[] = [];
        const _$selectedPath = editorSelection.getPath();
        const $selectedPath = LayoutEditorPath.isSpecialWidgetPath(_$selectedPath) ? null : _$selectedPath;

        if (shouldAddDroppables) {
            const firstWidgetPath = LayoutEditorPath.widget($editorPath.sectionIndex, regionName, 0);
            const isFirstWidgetSelected =
                editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET &&
                LayoutEditorPath.areWidgetPathsEqual(firstWidgetPath, $selectedPath);

            if (!isFirstWidgetSelected) {
                newChildren.push(
                    <PanelWidget raw={true} key={"droppable-0"}>
                        <SectionDroppable $editorPath={firstWidgetPath} />
                    </PanelWidget>,
                );
            }
        }
        React.Children.forEach(existingChildren, (child, i) => {
            // Push each child into the newChildren array ensuring they have a key

            if (React.isValidElement(child)) {
                if (child.key == null) {
                    newChildren.push(React.cloneElement(child, { key: child.key ?? i }));
                } else {
                    newChildren.push(child);
                }
            } else {
                newChildren.push(child);
            }

            if (shouldAddDroppables) {
                // Add droppable regions.
                const isPreviousWidgetSelected =
                    editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET &&
                    $selectedPath &&
                    $selectedPath.sectionRegionIndex != null &&
                    $selectedPath.sectionIndex === $editorPath.sectionIndex &&
                    $selectedPath.sectionRegion === regionName &&
                    $selectedPath.sectionRegionIndex <= i;

                const isNextWidgetSelected =
                    editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET &&
                    $selectedPath &&
                    $selectedPath.sectionRegionIndex != null &&
                    $selectedPath.sectionIndex === $editorPath.sectionIndex &&
                    $selectedPath.sectionRegion === regionName &&
                    $selectedPath.sectionRegionIndex === i + 1;

                const draggablePath = LayoutEditorPath.widget($editorPath.sectionIndex, regionName, i);
                const droppablePath = LayoutEditorPath.widget(
                    $editorPath.sectionIndex,
                    regionName,
                    isPreviousWidgetSelected ? i : i + 1,
                );

                const isSelected =
                    editorSelection.getMode() === LayoutEditorSelectionMode.WIDGET &&
                    LayoutEditorPath.areWidgetPathsEqual(draggablePath, $selectedPath);

                if (!isSelected && !isNextWidgetSelected) {
                    newChildren.push(
                        // notably this panle widget does not nothing except prevent autowrapping.
                        <PanelWidget raw={true} key={`droppable-${i + 1}`}>
                            <SectionDroppable $editorPath={droppablePath} />
                        </PanelWidget>,
                    );
                }
            }
        });

        if (existingChildren.length === 0 || isRegionSelected) {
            newChildren.push(
                <LayoutEditorAddWidget
                    key={"add-button"}
                    path={{
                        ...props.$editorPath,
                        sectionRegion: regionName,
                        sectionRegionIndex: existingChildren.length,
                    }}
                />,
            );
        }

        newProps[regionName] = newChildren;
    }
    return newProps;
}
