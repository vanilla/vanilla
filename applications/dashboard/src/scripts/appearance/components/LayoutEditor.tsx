/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactElement, useContext, useMemo, useRef, useState } from "react";
import { layoutEditorClasses } from "@dashboard/appearance/components/LayoutEditor.classes";
import { ButtonTypes } from "@library/forms/buttonTypes";
import classNames from "classnames";
import Button from "@library/forms/Button";
import { Icon } from "@vanilla/icons";
import { EmbedButton } from "@library/embeddedContent/components/EmbedButton";
import { EmbedMenu } from "@rich-editor/editor/pieces/EmbedMenu";
import Container, { ContainerContextReset } from "@library/layout/components/Container";
import { useLayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.hooks";
import { hydrateLayoutFromCatalog } from "@dashboard/appearance/utils";
import { IComponentFetcher, IDynamicComponent, Layout } from "@library/features/Layout/Layout";
import { DeviceProvider } from "@library/layout/DeviceContext";
import Loader from "@library/loaders/Loader";
import { IRegisteredComponent } from "@library/utility/componentRegistry";
import { FauxWidget, fetchOverviewComponent } from "@dashboard/appearance/components/LayoutOverview";
import TwoColumnSection from "@library/layout/TwoColumnSection";
import { useFocusWatcher } from "@vanilla/react-utils";
import { SectionOneColumn } from "@library/layout/SectionOneColumn";
import { SectionFullWidth } from "@library/layout/SectionFullWidth";
import ThreeColumnSection from "@library/layout/ThreeColumnSection";
import { Loadable } from "@library/@types/api/core";
import { LayoutEditSchema } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { TabHandler } from "@vanilla/dom-utils";
import { LinkContext, LINK_CONTEXT_DEFAULTS } from "@library/routing/links/LinkContextProvider";
import { layoutEditorContextProvider } from "@dashboard/appearance/components/LayoutEditorContextProvider";
import { RecordID, uuidv4 } from "@vanilla/utils";
import { LayoutSectionsModal } from "@dashboard/appearance/components/LayoutSectionsModal";
import { t } from "@vanilla/i18n";

type EditorSectionProps<T extends React.ComponentType> = React.ComponentProps<T> & {
    depth: string;
};

function EditorSectionFullWidth(props: EditorSectionProps<typeof SectionFullWidth>) {
    const classes = layoutEditorClasses();
    const ref = useRef<HTMLDivElement | null>(null);
    const [isFocused, setIsFocused] = useState(false);
    useFocusWatcher(ref, setIsFocused);
    const nodeIndex = parseInt(props.depth!.match(/\d/g)![0]);

    const children = React.Children.toArray(props.children);
    if (children.length === 0) {
        children.push(<WidgetAddButton key={"children"} />);
    }

    return (
        <>
            <SectionFullWidth
                className={classNames(TabHandler.NO_TABBING, classes.fullWidth, isFocused && "focus-visible")}
                {...{ ...props, children: children }}
                tabIndex={isFocused ? 0 : -1}
                contentRef={ref}
                data-layout-selectable={true}
                childrenAfter={<>{isFocused && <LayoutToolbar nodeIndex={nodeIndex} />}</>}
            />
        </>
    );
}

function EditorSectionOneColumn(props: EditorSectionProps<typeof SectionOneColumn>) {
    const classes = layoutEditorClasses();
    const ref = useRef<HTMLDivElement | null>(null);
    const [isFocused, setIsFocused] = useState(false);
    useFocusWatcher(ref, setIsFocused);
    const nodeIndex = parseInt(props.depth!.match(/\d/g)![0]);

    const children = React.Children.toArray(props.children);
    if (children.length === 0) {
        children.push(<WidgetAddButton key={"children"} />);
    }

    return (
        <>
            <SectionOneColumn
                className={classNames(classes.sectionOverwrite, classes.oneColumn, isFocused && "focus-visible")}
                {...{ ...props, children: children }}
                tabIndex={isFocused ? 0 : -1}
                data-layout-selectable={true}
                contentRef={ref}
                childrenAfter={<>{isFocused && <LayoutToolbar nodeIndex={nodeIndex} />}</>}
            />
        </>
    );
}

function EditorSectionTwoColumns(props: EditorSectionProps<typeof TwoColumnSection>) {
    const classes = layoutEditorClasses();
    const ref = useRef<HTMLDivElement | null>(null);
    const [isFocused, setIsFocused] = useState(false);
    useFocusWatcher(ref, setIsFocused);
    const nodeIndex = parseInt(props.depth!.match(/\d/g)![0]);

    const mainBottom = React.Children.toArray(props.mainBottom);
    if (mainBottom.length === 0) {
        mainBottom.push(<WidgetAddButton key={"mainBottom"} />);
    }

    const rightBottom = React.Children.toArray(props.rightBottom);
    if (rightBottom.length === 0) {
        rightBottom.push(<WidgetAddButton key={"rightBottom"} />);
    }

    return (
        <>
            <TwoColumnSection
                className={classNames(classes.sectionOverwrite, isFocused && "focus-visible")}
                {...props}
                tabIndex={isFocused ? 0 : -1}
                data-layout-selectable={true}
                contentRef={ref}
                mainBottom={mainBottom}
                rightBottom={rightBottom}
                childrenAfter={<>{isFocused && <LayoutToolbar nodeIndex={nodeIndex} />}</>}
            />
        </>
    );
}

function EditorSectionThreeColumns(props: EditorSectionProps<typeof ThreeColumnSection>) {
    const classes = layoutEditorClasses();
    const ref = useRef<HTMLDivElement | null>(null);
    const [isFocused, setIsFocused] = useState(false);
    useFocusWatcher(ref, setIsFocused);
    const nodeIndex = parseInt(props.depth!.match(/\d/g)![0]);

    const leftBottom = React.Children.toArray(props.leftBottom);
    if (leftBottom.length === 0) {
        leftBottom.push(<WidgetAddButton key={"leftBottom"} />);
    }

    const middleBottom = React.Children.toArray(props.middleBottom);
    if (middleBottom.length === 0) {
        middleBottom.push(<WidgetAddButton key={"middleBottom"} />);
    }

    const rightBottom = React.Children.toArray(props.rightBottom);
    if (rightBottom.length === 0) {
        rightBottom.push(<WidgetAddButton key={"rightBottom"} />);
    }

    return (
        <>
            <ThreeColumnSection
                className={classNames(classes.sectionOverwrite, isFocused && "focus-visible")}
                {...props}
                tabIndex={isFocused ? 0 : -1}
                data-layout-selectable={true}
                contentRef={ref}
                middleBottom={middleBottom}
                rightBottom={rightBottom}
                leftBottom={leftBottom}
                childrenAfter={<>{isFocused && <LayoutToolbar nodeIndex={nodeIndex} />}</>}
            />
        </>
    );
}

const _editorOverviewComponents: Record<string, IRegisteredComponent> = {};

export function registerWidgetEditorOverviews(widgets: Record<string, React.ComponentType<any>>) {
    for (const [widgetName, widget] of Object.entries(widgets)) {
        _editorOverviewComponents[widgetName.toLowerCase()] = {
            Component: widget,
        };
    }
}

registerWidgetEditorOverviews({
    SectionTwoColumns: EditorSectionTwoColumns,
    SectionThreeColumns: EditorSectionThreeColumns,
    SectionOneColumn: EditorSectionOneColumn,
    SectionFullWidth: EditorSectionFullWidth,
});

export const fetchEditorOverviewComponent: IComponentFetcher = (componentName) => {
    return _editorOverviewComponents[componentName.toLowerCase()] ?? fetchOverviewComponent(componentName);
};

export const editorDecorator = (index: number) => {
    return <SectionAddButton nodeIndex={index} key={`SectionAddButton-${uuidv4()}-${index}`} />;
};

interface ILayoutEditorOverviewProps {
    layoutLoadable: Loadable<LayoutEditSchema>;
}

export function LayoutEditorOverview(props: ILayoutEditorOverviewProps) {
    const { layoutLoadable } = props;
    const catalog = useLayoutCatalog(layoutLoadable?.data?.layoutViewType ?? null);
    const containerRef = useRef<HTMLDivElement | null>(null);

    const hydratedLayout = useMemo(
        () => layoutLoadable.data && catalog && hydrateLayoutFromCatalog(layoutLoadable.data, catalog),
        [layoutLoadable, catalog],
    );

    /**
     * Keyboard handler for arrow up and arrow down.
     */
    function onKeyDown(e: React.KeyboardEvent) {
        // Check if the editor does not contain target element and allow standard keydown events to the target
        if (!e.currentTarget.contains(e.target as Element)) {
            return;
        }

        const container = containerRef.current;
        if (!container) {
            return;
        }

        if (e.currentTarget === containerRef.current) {
            // Tabbing on ourself we don't do anything.
            return;
        }
        // Get items.
        const selectables = Array.from(container!.querySelectorAll<HTMLElement>("*[data-layout-selectable]"));
        let focused = container!.querySelector<HTMLElement>("*[data-layout-selectable]:focus-within");

        // Get the id and index of the focused element.
        const index = focused ? selectables.indexOf(focused) : -1;

        // Get the siblings of the focused item.
        const prev = index > 0 ? selectables[index - 1] : selectables[0];
        const next = index + 1 < selectables.length ? selectables[index + 1] : selectables[0];
        const first = selectables[0];
        const last = selectables[selectables.length - 1];
        const isFirst = focused === first;
        const isLast = focused === last;

        // Handle keys.
        switch (e.key) {
            case "ArrowDown":
                if (!isLast && next) {
                    e.preventDefault();
                    e.stopPropagation();
                    next.focus();
                }
                break;
            case "ArrowUp":
                if (!isFirst && prev) {
                    e.preventDefault();
                    e.stopPropagation();
                    prev.focus();
                }
                break;
        }
    }

    return hydratedLayout ? (
        <div
            tabIndex={0}
            onKeyDown={onKeyDown}
            aria-label="TODO LABEL ME"
            aria-details="INSTRUCTION ON HOW TO KEYBOARD NAVIGATE"
        >
            <LinkContext.Provider value={{ ...LINK_CONTEXT_DEFAULTS, areLinksDisabled: true }}>
                <DeviceProvider>
                    <ContainerContextReset>
                        <Layout
                            layoutRef={containerRef}
                            layout={hydratedLayout.layout}
                            fallbackWidget={FauxWidget}
                            editorDecorator={editorDecorator}
                            componentFetcher={fetchEditorOverviewComponent}
                            // onKeyDown={onKeyDown}
                        />
                    </ContainerContextReset>
                </DeviceProvider>
            </LinkContext.Provider>
        </div>
    ) : (
        <Loader />
    );
}

export function SectionAddButton(this: any, props: { nodeIndex: number }) {
    const classes = layoutEditorClasses();
    // TODO fix hardcoded.
    const catalog = useLayoutCatalog("home");
    const { addSection } = useContext(layoutEditorContextProvider);
    const ref = useRef<HTMLElement>(null);
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isFocused, setIsFocused] = useState(false);
    useFocusWatcher(ref, setIsFocused);

    return (
        <>
            <Container fullGutter>
                <Button
                    tabIndex={isFocused ? 0 : -1}
                    data-layout-selectable={true}
                    buttonType={ButtonTypes.CUSTOM}
                    onClick={() => {
                        setIsModalOpen(true);
                    }}
                    className={classNames(classes.addSection, "addSection")}
                >
                    <div className={classNames(classes.buttonLine)}>
                        <div className={classNames(classes.buttonCircle, "buttonCircle")}>
                            <Icon icon={"data-add"} />
                        </div>
                    </div>
                </Button>
            </Container>
            <LayoutSectionsModal
                title={t("Choose the Type of Section")}
                exitHandler={() => setIsModalOpen(false)}
                sections={catalog?.sections ?? {}}
                onAddSection={(sectionID) => {
                    addSection(props.nodeIndex, sectionID);
                }}
                isVisible={isModalOpen}
            />
        </>
    );
}

export const WidgetAddButton = () => {
    const classes = layoutEditorClasses();
    const { addWidgetHandler } = useContext(layoutEditorContextProvider);
    return (
        <Button
            tabIndex={-1}
            buttonType={ButtonTypes.CUSTOM}
            onClick={addWidgetHandler}
            className={classNames(classes.addWidget, "addWidget")}
        >
            <div className={classNames(classes.buttonCircle, "buttonCircle")}>
                <Icon icon={"data-add"} />
            </div>
        </Button>
    );
};

export function LayoutToolbar(this: any, props: { nodeIndex: number; className?: string }) {
    const { deleteSection } = useContext(layoutEditorContextProvider);
    return (
        <EmbedMenu className={classNames(props.className)}>
            {/* <EmbedButton onClick={() => {}}>
                <Icon icon={"data-up"} />
            </EmbedButton>
            <EmbedButton onClick={() => {}}>
                <Icon icon={"data-down"} />
            </EmbedButton>
            <EmbedButton onClick={() => {}}>
                <Icon icon={"data-swap"} />
            </EmbedButton>
            <EmbedButton onClick={() => {}}>
                <Icon icon={"data-replace"} />
            </EmbedButton> */}
            <EmbedButton onClick={deleteSection.bind(this, props.nodeIndex)}>
                <Icon icon={"data-trash"} />
            </EmbedButton>
        </EmbedMenu>
    );
}
