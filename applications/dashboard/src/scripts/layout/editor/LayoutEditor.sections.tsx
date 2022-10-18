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
import { IHydratedEditableWidgetProps, ILayoutEditorPath } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { cx } from "@emotion/css";
import { SectionFullWidth } from "@library/layout/SectionFullWidth";
import { SectionOneColumn } from "@library/layout/SectionOneColumn";
import SectionThreeColumns from "@library/layout/ThreeColumnSection";
import SectionTwoColumns from "@library/layout/TwoColumnSection";
import { useFocusOnActivate } from "@vanilla/react-utils";
import React, { useDebugValue, useRef } from "react";

type EditorSectionProps<T extends React.ComponentType> = React.ComponentProps<T> & IHydratedEditableWidgetProps;

export function EditorSectionFullWidth(props: EditorSectionProps<typeof SectionFullWidth>) {
    props = useSectionPropsWithAddButtons(props);

    const { $editorPath, $componentName, $hydrate, ...restProps } = props;
    const { editorSelection, editorContents } = useLayoutEditor();
    const classes = layoutEditorClasses();
    const ref = useRef<HTMLDivElement | null>(null);
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
                contentRef={ref}
                data-layout-selectable={true}
                childrenBefore={<SectionDecorationAbsolute {...props} skipBefore={hasStaticDecorationBefore} />}
            />
        </>
    );
}

export function EditorSectionOneColumn(props: EditorSectionProps<typeof SectionOneColumn>) {
    props = useSectionPropsWithAddButtons(props);
    const { $editorPath, $componentName, $hydrate, ...restProps } = props;

    const classes = layoutEditorClasses();
    const ref = useRef<HTMLDivElement | null>(null);
    const { isActive, isSelected, activeClasses } = useSectionFocusRef(props.$editorPath, ref);

    const onClick = useSectionClickHandler(props.$editorPath);

    return (
        <>
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
                childrenBefore={<SectionDecorationAbsolute {...props} />}
            />
        </>
    );
}

export function EditorSectionTwoColumns(props: EditorSectionProps<typeof SectionTwoColumns>) {
    props = useSectionPropsWithAddButtons(props);

    const { $editorPath, $componentName, $hydrate, ...restProps } = props;
    const classes = layoutEditorClasses();
    const ref = useRef<HTMLDivElement | null>(null);
    const { isActive, isSelected, activeClasses } = useSectionFocusRef(props.$editorPath, ref);

    const onClick = useSectionClickHandler(props.$editorPath);

    return (
        <>
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
                childrenBefore={<SectionDecorationAbsolute {...props} />}
            />
        </>
    );
}

export function EditorSectionThreeColumns(props: EditorSectionProps<typeof SectionThreeColumns>) {
    props = useSectionPropsWithAddButtons(props);
    const { $editorPath, $componentName, $hydrate, ...restProps } = props;
    const classes = layoutEditorClasses();
    const ref = useRef<HTMLDivElement | null>(null);
    const { isActive, isSelected, activeClasses } = useSectionFocusRef(props.$editorPath, ref);
    const onClick = useSectionClickHandler(props.$editorPath);

    return (
        <>
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
                childrenBefore={<SectionDecorationAbsolute {...props} />}
            />
        </>
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

function SectionDecorationStatic(props: IHydratedEditableWidgetProps) {
    const { editorSelection } = useLayoutEditor();
    const isSelected =
        LayoutEditorPath.areSectionPathsEqual(props.$editorPath, editorSelection.getPath()) &&
        [LayoutEditorSelectionMode.SECTION_ADD].includes(editorSelection.getMode());
    const classes = layoutEditorClasses();
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
    props: IHydratedEditableWidgetProps & { skipBefore?: boolean; skipAfter?: boolean },
) {
    const path = props.$editorPath;
    const { editorSelection, editorContents } = useLayoutEditor();
    const classes = layoutEditorClasses();

    const selectedIndex = editorSelection.getPath()?.sectionIndex ?? 0;

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

    const sectionHasAssets = LayoutSectionInfos[props.$hydrate].regionNames.some((region) => {
        return (
            props[region] &&
            props[region].some(
                (child) => child?.props && child.props.$hydrate && child.props.$hydrate.includes("asset"),
            )
        );
    });

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
                <LayoutEditorSectionToolbar
                    path={props.$editorPath}
                    offset={offset}
                    allowColumnInvert={allowColumnInvert}
                    hasAsset={sectionHasAssets}
                />
            )}
        </>
    );
}

function useSectionPropsWithAddButtons<T extends IHydratedEditableWidgetProps>(props: T): T {
    const { editorSelection, editorContents } = useLayoutEditor();
    const sectionInfo = editorContents.getSectionInfo(props.$editorPath);
    if (!sectionInfo) {
        return props;
    }
    const newProps = { ...props };
    const isRegionSelected =
        LayoutEditorPath.areSectionPathsEqual(props.$editorPath, editorSelection.getPath()) &&
        [LayoutEditorSelectionMode.SECTION, LayoutEditorSelectionMode.WIDGET].includes(editorSelection.getMode());

    for (const regionName of sectionInfo.regionNames) {
        if (typeof regionName !== "string") {
            continue;
        }
        const regionChildren = React.Children.toArray(props[regionName] ?? ([] as any));
        if (regionChildren.length === 0 || (!sectionInfo.oneWidgetPerRegion && isRegionSelected)) {
            regionChildren.push(
                <LayoutEditorAddWidget
                    key={"add-button"}
                    path={{
                        ...props.$editorPath,
                        sectionRegion: regionName,
                        sectionRegionIndex: regionChildren.length,
                    }}
                />,
            );
            newProps[regionName] = regionChildren as any;
        }
    }
    return newProps;
}
