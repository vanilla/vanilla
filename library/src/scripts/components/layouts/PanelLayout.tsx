/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import classNames from "classnames";
import { ScrollOffsetContext } from "@library/contexts/ScrollOffsetContext";
import { style } from "typestyle";
import { NestedCSSProperties } from "typestyle/lib/types";
import throttle from "lodash/throttle";
import { withDevice } from "@library/contexts/DeviceContext";
import { debugHelper } from "@library/styles/styleHelpers";

interface IProps extends IDeviceProps {
    className?: string;
    toggleMobileMenu?: (isOpen: boolean) => void;
    contentTag?: string;
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
 * |             | MiddelBottom
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
class PanelLayout extends React.Component<IProps> {
    public static contextType = ScrollOffsetContext;
    public context!: React.ContextType<typeof ScrollOffsetContext>;

    public static defaultProps = {
        contentTag: "div",
        growMiddleBottom: false,
        topPadding: true,
        isFixed: true,
    };

    private leftPanelRef = React.createRef<HTMLElement>();
    private rightPanelRef = React.createRef<HTMLElement>();

    public render() {
        const { topPadding, className, growMiddleBottom, device, isFixed, ...childComponents } = this.props;

        // Calculate some rendering variables.
        const isMobile = device === Devices.MOBILE;
        const isTablet = device === Devices.TABLET;
        const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop
        const shouldRenderLeftPanel: boolean = !isMobile && (!!childComponents.leftTop || !!childComponents.leftBottom);
        const shouldRenderRightPanel: boolean =
            (isFullWidth || (isTablet && !shouldRenderLeftPanel)) &&
            !!(childComponents.rightTop || childComponents.rightBottom);

        // Determine the classes we want to display.
        const panelClasses = classNames(
            "panelLayout",
            { noLeftPanel: !shouldRenderLeftPanel },
            { noRightPanel: !shouldRenderRightPanel },
            { noBreadcrumbs: !childComponents.breadcrumbs },
            className,
            { inheritHeight: growMiddleBottom },
            { hasTopPadding: topPadding },
        );

        const fixedPanelClasses = this.calcFixedPanelClasses();

        // If applicable, set semantic tag, like "article"
        const ContentTag = `${this.props.contentTag}`;

        return (
            <div className={panelClasses}>
                {childComponents.breadcrumbs && (
                    <div className="panelLayout-container">
                        {shouldRenderLeftPanel && (
                            <Panel className={classNames("panelLayout-left")} ariaHidden={true} />
                        )}
                        <Panel
                            className={classNames("panelLayout-content", "panel-breadcrumbs", {
                                hasAdjacentPanel: true,
                            })}
                        >
                            <PanelArea className={classNames("panelArea-breadcrumbs", "hasAdjacentPanel")}>
                                {childComponents.breadcrumbs}
                            </PanelArea>
                            <Panel className={classNames("panelLayout-right")} ariaHidden={true} />
                        </Panel>
                    </div>
                )}

                <main className={classNames("panelLayout-main", { inheritHeight: this.props.growMiddleBottom })}>
                    <div
                        className={classNames("panelLayout-container", { inheritHeight: this.props.growMiddleBottom })}
                    >
                        {!isMobile &&
                            shouldRenderLeftPanel && (
                                <>
                                    {isFixed && (
                                        <Panel
                                            className={classNames("panelLayout-left")}
                                            tag="aside"
                                            innerRef={this.leftPanelRef}
                                        />
                                    )}
                                    <Panel
                                        className={classNames(
                                            "panelLayout-left",
                                            { isFixed },
                                            fixedPanelClasses.left,
                                            this.context.offsetClass,
                                        )}
                                        tag="aside"
                                    >
                                        {childComponents.leftTop && (
                                            <PanelArea className="panelArea-leftTop">
                                                {childComponents.leftTop}
                                            </PanelArea>
                                        )}
                                        {childComponents.leftBottom && (
                                            <PanelArea className="panelArea-leftBottom">
                                                {childComponents.leftBottom}
                                            </PanelArea>
                                        )}
                                    </Panel>
                                </>
                            )}

                        <ContentTag
                            className={classNames("panelLayout-content", {
                                hasAdjacentPanel: shouldRenderLeftPanel,
                            })}
                        >
                            <Panel
                                className={classNames("panelLayout-middle", {
                                    hasAdjacentPanel: shouldRenderRightPanel,
                                    inheritHeight: this.props.growMiddleBottom,
                                })}
                            >
                                {childComponents.middleTop && (
                                    <PanelArea className="panelAndNav-middleTop">{childComponents.middleTop}</PanelArea>
                                )}
                                {!shouldRenderLeftPanel &&
                                    childComponents.leftTop && (
                                        <PanelArea className="panelAndNav-mobileMiddle" tag="aside">
                                            {childComponents.leftTop}
                                        </PanelArea>
                                    )}
                                {!shouldRenderRightPanel &&
                                    childComponents.rightTop && (
                                        <PanelArea className="panelAndNav-tabletMiddle" tag="aside">
                                            {childComponents.rightTop}
                                        </PanelArea>
                                    )}
                                <PanelArea
                                    className={classNames("panelAndNav-middleBottom", {
                                        inheritHeight: this.props.growMiddleBottom,
                                    })}
                                >
                                    {childComponents.middleBottom}
                                </PanelArea>
                                {!shouldRenderRightPanel &&
                                    childComponents.rightBottom && (
                                        <PanelArea className="panelAndNav-tabletBottom" tag="aside">
                                            {childComponents.rightBottom}
                                        </PanelArea>
                                    )}
                            </Panel>
                            {shouldRenderRightPanel && (
                                <>
                                    {isFixed && <Panel className="panelLayout-right" innerRef={this.rightPanelRef} />}
                                    <Panel
                                        className={classNames(
                                            "panelLayout-right",
                                            { isFixed },
                                            fixedPanelClasses.right,
                                            this.context.offsetClass,
                                        )}
                                    >
                                        {childComponents.rightTop && (
                                            <PanelArea className="panelArea-rightTop" tag="aside">
                                                {childComponents.rightTop}
                                            </PanelArea>
                                        )}
                                        {childComponents.rightBottom && (
                                            <PanelArea className="panelArea-rightBottom" tag="aside">
                                                {childComponents.rightBottom}
                                            </PanelArea>
                                        )}
                                    </Panel>
                                </>
                            )}
                        </ContentTag>
                    </div>
                </main>
            </div>
        );
    }

    /**
     * @inheritdoc
     */
    public componentDidMount() {
        window.addEventListener("resize", this.recalcSizes);

        // We always need a second layout pass to display calculated values.
        this.recalcSizes();
    }

    /**
     * Any time we gain/lose a left/right panel we need a second layout pass.
     * This way calculate values can be declared.
     */
    public componentDidUpdate(prevProps: IProps) {
        const hadRight = prevProps.rightTop || prevProps.rightBottom;
        const hasRight = this.props.rightTop || this.props.rightBottom;
        if (!hadRight && hasRight) {
            this.recalcSizes();
        }

        const hadLeft = prevProps.leftTop || prevProps.leftBottom;
        const hasLeft = this.props.leftTop || this.props.leftBottom;
        if (!hadLeft && hasLeft) {
            this.recalcSizes();
        }
    }

    /**
     * @inheritdoc
     */
    public componentWillUnmount() {
        window.removeEventListener("resize", this.recalcSizes);
    }

    /**
     * Recalculate the values on the only at the end of any 150ms interval.
     */
    private recalcSizes = throttle(() => {
        this.clearBoundingRectCache();
        this.forceUpdate();
    }, 150);

    private calcFixedPanelClasses(): { left: string; right: string } {
        const { isFixed } = this.props;
        const leftPanelEl = this.leftPanelRef.current;
        const rightPanelEl = this.rightPanelRef.current;
        const debug = debugHelper("panelFixed");

        // The classes default to visually hidden (but still visible to screen readers) until fully calced.
        // The content may already be rendered, but we need a second layout pass for computed values.
        let left = "sr-only";
        let right = "sr-only";

        if (!isFixed) {
            return { left, right };
        }

        const base: NestedCSSProperties = {
            position: "fixed",
            bottom: 0,
        };

        if (leftPanelEl) {
            const leftPanelRect = this.getCachedBoundingRect(leftPanelEl);
            left = style({
                ...base,
                top: leftPanelRect.top + "px",
                left: leftPanelRect.left + "px",
                ...debug.name("leftPanel"),
            });
        }

        if (rightPanelEl) {
            const rightPanelRect = this.getCachedBoundingRect(rightPanelEl);
            right = style({
                ...base,
                top: rightPanelRect.top + "px",
                left: rightPanelRect.left + "px",
                ...debug.name("rightPanel"),
            });
        }

        return { left, right };
    }

    /** A cached of calculated client rects. */
    private boundingRectCaches: WeakMap<HTMLElement, ClientRect> = new WeakMap();

    /**
     * Get a calculated client rect using our local cache.
     */
    private getCachedBoundingRect(element: HTMLElement): ClientRect {
        const cachedRect = this.boundingRectCaches.get(element);
        if (cachedRect) {
            return cachedRect;
        }

        const boundingRect = element.getBoundingClientRect();
        this.boundingRectCaches.set(element, boundingRect);
        return boundingRect;
    }

    /**
     * Clear the cached client rects. Be sure to call this after any resize.
     */
    private clearBoundingRectCache() {
        this.boundingRectCaches = new WeakMap();
    }
}

// Simple container components.
interface IContainerProps {
    className?: string;
    children?: React.ReactNode;
    tag?: string;
    ariaHidden?: boolean;
    innerRef?: React.RefObject<HTMLElement>;
}

export function Panel(props: IContainerProps) {
    const Tag = `${props.tag ? props.tag : "div"}`;
    return (
        <Tag
            className={classNames("panelLayout-panel", props.className)}
            aria-hidden={props.ariaHidden}
            ref={props.innerRef}
        >
            {props.children}
        </Tag>
    );
}

export function PanelArea(props: IContainerProps) {
    const Tag = `${props.tag ? props.tag : "div"}`;
    return <Tag className={classNames("panelArea", props.className)}>{props.children}</Tag>;
}

export function PanelWidget(props: IContainerProps) {
    return <div className={classNames("panelWidget", props.className)}>{props.children}</div>;
}

export function PanelWidgetVerticalPadding(props: IContainerProps) {
    return <div className={classNames("panelWidget", "hasNoHorizontalPadding", props.className)}>{props.children}</div>;
}

export function PanelWidgetHorizontalPadding(props: IContainerProps) {
    return <div className={classNames("panelWidget", "hasNoVerticalPadding", props.className)}>{props.children}</div>;
}

export default withDevice(PanelLayout);
