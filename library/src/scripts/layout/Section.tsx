/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css, cx } from "@emotion/css";
import { Container } from "@library/layout/components/Container";
import PanelArea from "@library/layout/components/PanelArea";
import { useSection } from "@library/layout/LayoutContext";
import { PageBoxDepthContextProvider } from "@library/layout/PageBox.context";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { SectionBehaviourContext } from "@library/layout/SectionBehaviourContext";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { useWidgetSectionClasses } from "@library/layout/WidgetLayout.context";
import { globalVariables } from "@library/styles/globalStyleVars";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { useMeasure } from "@vanilla/react-utils";
import { logError } from "@vanilla/utils";
import classNames from "classnames";
import React, { ElementType, useContext, useMemo, useRef } from "react";
import Panel from "./components/Panel";
import PanelAreaHorizontalPadding from "./components/PanelAreaHorizontalPadding";
import PanelOverflow from "./components/PanelOverflow";
import PanelWidgetHorizontalPadding from "./components/PanelWidgetHorizontalPadding";

export interface ISectionProps extends React.HTMLAttributes<HTMLElement> {
    className?: string;
    toggleMobileMenu?: (isOpen: boolean) => void;
    contentTag?: ElementType;
    growMiddleBottom?: boolean;
    topPadding?: boolean;
    leftTop?: React.ReactNode;
    leftBottom?: React.ReactNode;
    mainTop?: React.ReactNode;
    mainBottom?: React.ReactNode;
    children?: React.ReactNode;
    middleTop?: React.ReactNode;
    middleBottom?: React.ReactNode;
    rightTop?: React.ReactNode;
    rightBottom?: React.ReactNode;
    breadcrumbs?: React.ReactNode;
    renderLeftPanelBackground?: boolean;
    childrenBefore?: React.ReactNode;
    childrenAfter?: React.ReactNode;
    contentRef?: React.RefObject<HTMLDivElement>;
    displayLeftColumn?: boolean;
    displayRightColumn?: boolean;
}

/**
 * A responsive configurable Panel Layout.
 *
 * This works by declaring certain sections and having the layout place them for you.
 * See the example for usage. Just provide the sections you want to work with and the layout
 * will attempt to place them all in the best possible way.
 *
 * @layout Desktop
 * | Breadcrumbs |              |             |
 * | LeftTop     | MiddleTop    | RightTop    |
 * | LeftBottom  | MiddleBottom | RightBottom |
 *
 * @layout Tablet
 * | Breadcrumbs |
 * | LeftTop     | MiddleTop
 * | LeftBottom  | RightTop
 * |             | MiddleBottom
 * |             | RightBottom
 *
 * @layout Mobile
 *
 * HamburgerMenu / Panel - LeftBottom
 *
 * | Breadcrumbs  |
 * | MiddleTop    |
 * | LeftTop      |
 * | RightTop     |
 * | MiddleBottom |
 * | LeftBottom   |
 * | RightBottom  |
 */
export default function Section(props: ISectionProps) {
    const {
        className,
        contentTag = "div",
        growMiddleBottom = false,
        topPadding = true,
        leftTop,
        leftBottom,
        middleTop,
        middleBottom,
        mainTop,
        mainBottom,
        rightTop,
        rightBottom,
        breadcrumbs,
        contentRef,
        children,
        childrenBefore,
        childrenAfter,
        displayRightColumn = true,
        displayLeftColumn = true,
        ...elementProps
    } = props;

    const { isSticky } = useContext(SectionBehaviourContext);

    // Measure the panel itself.
    const { offsetClass, rawScrollOffset } = useScrollOffset();
    const { type, classes, currentDevice, isCompact, isFullWidth } = useSection();

    if (!classes) {
        logError(`Classes not loaded for panel layout of type: ${type}, classes given: `, classes);
    }

    const childComponents = {
        leftTop: leftTop,
        leftBottom: leftBottom,
        mainBottom: mainBottom,
        mainTop: mainTop,
        middleTop: middleTop,
        middleBottom: middleBottom,
        rightTop: rightTop,
        rightBottom: rightBottom,
        breadcrumbs: breadcrumbs,
        children: children,
    };

    const panelRef = useRef<HTMLDivElement | null>(null);
    const panelMeasure = useMeasure(panelRef, true);
    const panelTop = panelMeasure.top;

    const globalVars = globalVariables();
    const realScrollOffset = rawScrollOffset ?? 0;
    const minimumOffset = globalVars.spacer.panelComponent;

    const overflowOffset = useMemo(() => {
        // Make sure we have some mininum amount of spacing.
        const withAdditionalOffset = realScrollOffset + minimumOffset;
        // If we started in the document closer to the top of the page use that instead though.
        const withComponentMin = Math.min(panelTop, withAdditionalOffset);
        return withComponentMin;
    }, [
        realScrollOffset,
        // This is only needed to catch the initial page load with a scroll offset
        // If you make this just raw panelTop be careful to do some performance profiling first.
        panelTop > 0,
    ]);

    const panelOffsetClass = useMemo(() => css({ top: overflowOffset }), [overflowOffset]);

    // Calculate some rendering variables.

    const shouldRenderLeftPanel: boolean =
        !isCompact && (!!childComponents.leftTop || !!childComponents.leftBottom) && displayLeftColumn;
    const shouldRenderRightPanel: boolean =
        isFullWidth && (!!childComponents.rightTop || !!childComponents.rightBottom) && displayRightColumn;
    const shouldRenderBreadcrumbs: boolean = !!childComponents.breadcrumbs;

    const widgetClasses = useWidgetSectionClasses();

    // Determine the classes we want to display.
    const panelClasses = cx(
        classes.root,
        { noLeftPanel: !shouldRenderLeftPanel },
        { noRightPanel: !shouldRenderRightPanel },
        { noBreadcrumbs: !shouldRenderBreadcrumbs },
        className,
        { hasTopPadding: topPadding },
        growMiddleBottom ? inheritHeightClass() : "",
        widgetClasses.widgetClass,
    );

    // If applicable, set semantic tag, like "article"
    const ContentTag = contentTag;
    return (
        <div className={panelClasses} ref={contentRef} {...elementProps}>
            {childrenBefore}
            <Container>
                {shouldRenderBreadcrumbs && (
                    <div className={classNames(classes.container, classes.breadcrumbsContainer)}>
                        {shouldRenderLeftPanel && (
                            <Panel className={classNames(classes.leftColumn)} ariaHidden={true} />
                        )}
                        <PanelAreaHorizontalPadding
                            className={classNames(classes.mainColumnMaxWidth, {
                                hasAdjacentPanel: shouldRenderLeftPanel,
                            })}
                        >
                            <PanelWidgetHorizontalPadding>{childComponents.breadcrumbs}</PanelWidgetHorizontalPadding>
                        </PanelAreaHorizontalPadding>
                    </div>
                )}
                <main className={classNames(classes.main, props.growMiddleBottom ? inheritHeightClass() : "")}>
                    <div
                        ref={panelRef}
                        className={classNames(classes.container, props.growMiddleBottom ? inheritHeightClass() : "")}
                    >
                        {shouldRenderLeftPanel && (
                            <PageBoxDepthContextProvider depth={4}>
                                <WidgetLayout
                                    widgetClass={classes.secondaryPanelWidget}
                                    headingBlockClass={classes.secondaryPanelHeadingBlock}
                                >
                                    <Panel
                                        className={classNames(classes.leftColumn, {
                                            [classes.isSticky]: isSticky,
                                            [panelOffsetClass]: isSticky,
                                            [offsetClass]: isSticky,
                                        })}
                                    >
                                        <PanelOverflow
                                            offset={overflowOffset}
                                            isLeft={true}
                                            renderLeftPanelBackground={props.renderLeftPanelBackground}
                                        >
                                            {childComponents.leftTop !== undefined && (
                                                <PanelArea>{childComponents.leftTop}</PanelArea>
                                            )}
                                            {childComponents.leftBottom !== undefined && (
                                                <PanelArea>{childComponents.leftBottom}</PanelArea>
                                            )}
                                        </PanelOverflow>
                                    </Panel>
                                </WidgetLayout>
                            </PageBoxDepthContextProvider>
                        )}

                        <WidgetLayout
                            widgetClass={classes.mainPanelWidget}
                            headingBlockClass={classes.mainPanelHeadingBlock}
                        >
                            <ContentTag
                                className={classNames(classes.content, classes.mainColumnMaxWidth, {
                                    hasAdjacentPanel: shouldRenderLeftPanel || shouldRenderRightPanel,
                                    hasTwoAdjacentPanels: shouldRenderLeftPanel && shouldRenderRightPanel,
                                })}
                            >
                                <Panel
                                    className={classNames(
                                        classes.mainColumn,
                                        props.growMiddleBottom ? inheritHeightClass() : "",
                                    )}
                                >
                                    {childComponents.middleTop !== undefined && (
                                        <PanelArea>{childComponents.middleTop}</PanelArea>
                                    )}
                                    {!shouldRenderLeftPanel && childComponents.leftTop !== undefined && (
                                        <PanelArea>{childComponents.leftTop}</PanelArea>
                                    )}
                                    {!shouldRenderRightPanel && childComponents.rightTop !== undefined && (
                                        <PanelArea>{childComponents.rightTop}</PanelArea>
                                    )}
                                    <PanelArea className={inheritHeightClass()}>
                                        {childComponents.middleBottom}
                                    </PanelArea>
                                    {!shouldRenderLeftPanel && childComponents.leftBottom !== undefined && (
                                        <PanelArea>{childComponents.leftBottom}</PanelArea>
                                    )}
                                    {!shouldRenderRightPanel && childComponents.rightBottom !== undefined && (
                                        <PanelArea>{childComponents.rightBottom}</PanelArea>
                                    )}
                                </Panel>
                            </ContentTag>
                        </WidgetLayout>

                        {shouldRenderRightPanel && (
                            <PageBoxDepthContextProvider depth={4}>
                                <WidgetLayout
                                    widgetClass={classes.secondaryPanelWidget}
                                    headingBlockClass={classes.secondaryPanelHeadingBlock}
                                >
                                    <Panel
                                        className={classNames(classes.rightColumn, {
                                            [classes.isSticky]: isSticky,
                                            [panelOffsetClass]: isSticky,
                                            [offsetClass]: isSticky,
                                        })}
                                    >
                                        <PanelOverflow offset={overflowOffset}>
                                            {childComponents.rightTop !== undefined && (
                                                <PanelArea>{childComponents.rightTop}</PanelArea>
                                            )}
                                            {childComponents.rightBottom !== undefined && (
                                                <PanelArea>{childComponents.rightBottom}</PanelArea>
                                            )}
                                        </PanelOverflow>
                                    </Panel>
                                </WidgetLayout>
                            </PageBoxDepthContextProvider>
                        )}
                    </div>
                </main>
            </Container>
            {childrenAfter}
        </div>
    );
}
