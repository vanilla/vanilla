/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { panelAreaClasses } from "@library/layout/panelAreaStyles";
import { panelWidgetClasses } from "@library/layout/panelWidgetStyles";
import { useScrollOffset } from "@library/layout/ScrollOffsetContext";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { useMeasure } from "@vanilla/react-utils";
import classNames from "classnames";
import React, { useMemo, useRef } from "react";
import { style } from "typestyle";
import { panelBackgroundVariables } from "@library/layout/panelBackgroundStyles";
import { useBannerContext } from "@library/banner/BannerContext";
import { useLayout } from "@library/layout/LayoutContext";

interface IProps {
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
export default function PanelLayout(props: IProps) {
    const { topPadding, className, growMiddleBottom, isFixed, ...childComponents } = props;

    // Handle window resizes

    // Measure the panel itself.
    const { offsetClass, topOffset } = useScrollOffset();
    const { bannerRect } = useBannerContext();
    const { currentDevice, isCompact, Devices, layoutClasses } = useLayout();
    const panelRef = useRef<HTMLDivElement | null>(null);
    const sidePanelMeasure = useMeasure(panelRef);
    const measuredPanelTop = sidePanelMeasure.top;
    const sidePanelDistanceFromTop = useMemo(() => {
        return measuredPanelTop + window.scrollY; // Every time this changes, adjust for the scroll height.
    }, [measuredPanelTop]);

    const overflowOffset = sidePanelDistanceFromTop - topOffset - (bannerRect?.height ?? 0);

    const panelOffsetClass = useMemo(() => style({ top: overflowOffset, $debugName: "stickyOffset" }), [
        overflowOffset,
    ]);

    // Calculate some rendering variables.
    const isTablet = currentDevice === Devices.TABLET;
    const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(currentDevice); // This compoment doesn't care about the no bleed, it's the same as desktop
    const shouldRenderLeftPanel: boolean = !isCompact && (!!childComponents.leftTop || !!childComponents.leftBottom);
    const shouldRenderRightPanel: boolean = isFullWidth || (isTablet && !shouldRenderLeftPanel);

    // Determine the classes we want to display.
    const panelClasses = classNames(
        layoutClasses.root,
        { noLeftPanel: !shouldRenderLeftPanel },
        { noRightPanel: !shouldRenderRightPanel },
        { noBreadcrumbs: !childComponents.breadcrumbs },
        className,
        { hasTopPadding: topPadding },
        growMiddleBottom ? inheritHeightClass() : "",
    );

    // If applicable, set semantic tag, like "article"
    const ContentTag = props.contentTag as "div";

    return (
        <div className={panelClasses}>
            {childComponents.breadcrumbs && (
                <div className={classNames(layoutClasses.container, layoutClasses.breadcrumbsContainer)}>
                    {shouldRenderLeftPanel && (
                        <Panel className={classNames(layoutClasses.leftColumn)} ariaHidden={true} />
                    )}
                    <PanelAreaHorizontalPadding
                        className={classNames(layoutClasses.middleColumnMaxWidth, {
                            hasAdjacentPanel: shouldRenderLeftPanel,
                        })}
                    >
                        <PanelWidgetHorizontalPadding>{childComponents.breadcrumbs}</PanelWidgetHorizontalPadding>
                    </PanelAreaHorizontalPadding>
                </div>
            )}

            <main className={classNames(layoutClasses.main, props.growMiddleBottom ? inheritHeightClass() : "")}>
                <div
                    ref={panelRef}
                    className={classNames(layoutClasses.container, props.growMiddleBottom ? inheritHeightClass() : "")}
                >
                    {!isCompact && shouldRenderLeftPanel && (
                        <Panel
                            className={classNames(layoutClasses.leftColumn, offsetClass, panelOffsetClass, {
                                [layoutClasses.isSticky]: isFixed,
                            })}
                        >
                            <PanelOverflow
                                offset={overflowOffset}
                                isLeft={true}
                                renderLeftPanelBackground={props.renderLeftPanelBackground}
                            >
                                {childComponents.leftTop && <PanelArea>{childComponents.leftTop}</PanelArea>}
                                {childComponents.leftBottom && <PanelArea>{childComponents.leftBottom}</PanelArea>}
                            </PanelOverflow>
                        </Panel>
                    )}

                    <ContentTag
                        className={classNames(layoutClasses.content, layoutClasses.middleColumnMaxWidth, {
                            hasAdjacentPanel: shouldRenderLeftPanel || shouldRenderRightPanel,
                            hasTwoAdjacentPanels: shouldRenderLeftPanel && shouldRenderRightPanel,
                        })}
                    >
                        <Panel
                            className={classNames(
                                layoutClasses.middleColumn,
                                props.growMiddleBottom ? inheritHeightClass() : "",
                            )}
                        >
                            {childComponents.middleTop && <PanelArea>{childComponents.middleTop}</PanelArea>}
                            {!shouldRenderLeftPanel && childComponents.leftTop && (
                                <PanelArea>{childComponents.leftTop}</PanelArea>
                            )}
                            {!shouldRenderRightPanel && childComponents.rightTop && (
                                <PanelArea>{childComponents.rightTop}</PanelArea>
                            )}
                            <PanelArea className={classNames(props.growMiddleBottom ? inheritHeightClass() : "")}>
                                {childComponents.middleBottom}
                            </PanelArea>
                            {!shouldRenderRightPanel && childComponents.rightBottom && (
                                <PanelArea>{childComponents.rightBottom}</PanelArea>
                            )}
                        </Panel>
                    </ContentTag>
                    {shouldRenderRightPanel && (
                        <Panel
                            className={classNames(layoutClasses.rightColumn, offsetClass, panelOffsetClass, {
                                [layoutClasses.isSticky]: isFixed,
                            })}
                        >
                            <PanelOverflow offset={overflowOffset}>
                                {childComponents.rightTop && <PanelArea>{childComponents.rightTop}</PanelArea>}
                                {childComponents.rightBottom && <PanelArea>{childComponents.rightBottom}</PanelArea>}
                            </PanelOverflow>
                        </Panel>
                    )}
                </div>
            </main>
        </div>
    );
}

PanelLayout.defaultProps = {
    contentTag: "div",
    growMiddleBottom: false,
    topPadding: true,
    isFixed: true,
};

// Simple container components.
interface IContainerProps {
    className?: string;
    children?: React.ReactNode;
    tag?: keyof JSX.IntrinsicElements;
    ariaHidden?: boolean;
    innerRef?: React.RefObject<HTMLDivElement>;
}

export function Panel(props: IContainerProps) {
    const Tag = (props.tag as "div") || "div";
    const { layoutClasses } = useLayout();
    return (
        <Tag
            className={classNames(layoutClasses.panel, props.className)}
            aria-hidden={props.ariaHidden}
            ref={props.innerRef}
        >
            {props.children}
        </Tag>
    );
}

export function PanelOverflow(
    props: IContainerProps & { offset: number; isLeft?: boolean; renderLeftPanelBackground?: boolean },
) {
    const { layoutClasses } = useLayout();
    const panelVars = panelBackgroundVariables();
    const color =
        panelVars.config.render && !!props.isLeft && props.renderLeftPanelBackground
            ? panelVars.colors.backgroundColor
            : undefined;
    return (
        <div className={layoutClasses.areaOverlay}>
            <div className={layoutClasses.areaOverlayBefore(color, "left")}></div>
            <div ref={props.innerRef} className={classNames(props.className, layoutClasses.overflowFull(props.offset))}>
                {props.children}
            </div>
            <div className={layoutClasses.areaOverlayAfter(color, "right")}></div>
        </div>
    );
}

export function PanelArea(props: IContainerProps) {
    const Tag = (props.tag as "div") || "div";
    const { layoutClasses } = useLayout();
    return (
        <Tag ref={props.innerRef} className={classNames(layoutClasses.root, props.className)}>
            {props.children}
        </Tag>
    );
}

export function PanelAreaHorizontalPadding(props: IContainerProps) {
    const Tag = props.tag || "div";
    const { layoutClasses } = useLayout();
    return (
        <Tag className={classNames(layoutClasses.root, props.className, "hasNoVerticalPadding")}>{props.children}</Tag>
    );
}

export function PanelWidget(props: IContainerProps) {
    const classes = panelWidgetClasses();
    return <div className={classNames(classes.root, props.className)}>{props.children}</div>;
}

export function PanelWidgetVerticalPadding(props: IContainerProps) {
    const classes = panelWidgetClasses();
    return <div className={classNames(classes.root, "hasNoHorizontalPadding", props.className)}>{props.children}</div>;
}

export function PanelWidgetHorizontalPadding(props: IContainerProps) {
    const classes = panelWidgetClasses();
    return <div className={classNames(classes.root, "hasNoVerticalPadding", props.className)}>{props.children}</div>;
}
