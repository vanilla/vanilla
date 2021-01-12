/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { useMeasure } from "@vanilla/react-utils";
import classNames from "classnames";
import React, { useMemo, useRef } from "react";
import { style } from "@library/styles/styleShim";
import { useBannerContext } from "@library/banner/BannerContext";
import { ILayoutProps, useLayout, withLayout } from "@library/layout/LayoutContext";
import { logError } from "@vanilla/utils";
import Panel from "./components/Panel";
import PanelOverflow from "./components/PanelOverflow";
import PanelArea from "./components/PanelArea";
import PanelAreaHorizontalPadding from "./components/PanelAreaHorizontalPadding";
import PanelWidgetHorizontalPadding from "./components/PanelWidgetHorizontalPadding";

export interface IPanelLayoutProps extends ILayoutProps {
    className?: string;
    toggleMobileMenu?: (isOpen: boolean) => void;
    contentTag?: keyof JSX.IntrinsicElements;
    growMiddleBottom?: boolean;
    topPadding?: boolean;
    isFixed?: boolean;
    leftTop?: React.ReactNode;
    leftBottom?: React.ReactNode;
    middleTop?: React.ReactNode;
    middleBottom?: React.ReactNode;
    rightTop?: React.ReactNode;
    rightBottom?: React.ReactNode;
    breadcrumbs?: React.ReactNode;
    renderLeftPanelBackground?: boolean;
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
 * | LeftTop     | RightTop
 * | LeftBottom  | MiddleTop
 * |             | MiddleBottom
 * |             | RightBottom
 *
 * @layout Mobile
 *
 * HamburgerMenu / Panel - LeftBottom
 *
 * | Breadcrumbs  |
 * | LeftTop      |
 * | RightTop     |
 * | MiddleTop    |
 * | MiddleBottom |
 * | RightBottom  |
 */
function PanelLayout(props: IPanelLayoutProps) {
    const {
        className,
        contentTag = "div",
        growMiddleBottom = false,
        topPadding = true,
        isFixed = true,
        mediaQueries,
        ...childComponents
    } = props;

    // Handle window resizes

    // Measure the panel itself.
    const { offsetClass, topOffset } = useScrollOffset();
    const { bannerRect } = useBannerContext();
    const {
        classes = props.classes,
        currentDevice,
        isCompact,
        isFullWidth,
        rightPanelCondition = () => {
            return false;
        },
    } = useLayout();

    if (!classes) {
        logError(`Classes not loaded for panel layout of type: ${props.type}, classes given: `, classes);
    }

    const panelRef = useRef<HTMLDivElement | null>(null);
    const sidePanelMeasure = useMeasure(panelRef);
    const measuredPanelTop = sidePanelMeasure.top;
    const sidePanelDistanceFromTop = useMemo(() => {
        return measuredPanelTop + window.scrollY; // Every time this changes, adjust for the scroll height.
    }, [measuredPanelTop]);

    const overflowOffset = sidePanelDistanceFromTop - topOffset - (bannerRect?.height ?? 0);

    const panelOffsetClass = useMemo(() => style({ top: overflowOffset, label: "stickyOffset" }), [overflowOffset]);

    // Calculate some rendering variables.

    const shouldRenderLeftPanel: boolean = !isCompact && (!!childComponents.leftTop || !!childComponents.leftBottom);
    const shouldRenderRightPanel: boolean = isFullWidth || rightPanelCondition(currentDevice, shouldRenderLeftPanel);
    const shouldRenderBreadcrumbs: boolean = !!childComponents.breadcrumbs;

    // Determine the classes we want to display.
    const panelClasses = classNames(
        classes.root,
        { noLeftPanel: !shouldRenderLeftPanel },
        { noRightPanel: !shouldRenderRightPanel },
        { noBreadcrumbs: !shouldRenderBreadcrumbs },
        className,
        { hasTopPadding: topPadding },
        growMiddleBottom ? inheritHeightClass() : "",
    );

    // If applicable, set semantic tag, like "article"
    const ContentTag = contentTag;
    return (
        <div className={panelClasses}>
            {shouldRenderBreadcrumbs && (
                <div className={classNames(classes.container, classes.breadcrumbsContainer)}>
                    {shouldRenderLeftPanel && <Panel className={classNames(classes.leftColumn)} ariaHidden={true} />}
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
                    {!isCompact && shouldRenderLeftPanel && (
                        <Panel
                            className={classNames(classes.leftColumn, offsetClass, panelOffsetClass, {
                                [classes.isSticky]: isFixed,
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
                    )}

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
                            <PanelArea className={classNames({ [inheritHeightClass()]: props.growMiddleBottom })}>
                                {childComponents.middleBottom}
                            </PanelArea>
                            {!shouldRenderRightPanel && childComponents.rightBottom !== undefined && (
                                <PanelArea>{childComponents.rightBottom}</PanelArea>
                            )}
                        </Panel>
                    </ContentTag>

                    {shouldRenderRightPanel && (
                        <Panel
                            className={classNames(classes.rightColumn, offsetClass, panelOffsetClass, {
                                [classes.isSticky]: isFixed,
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
                    )}
                </div>
            </main>
        </div>
    );
}

export default withLayout(PanelLayout);
