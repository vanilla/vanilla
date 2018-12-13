/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import { Devices, IDeviceProps } from "../DeviceChecker";
import classNames from "classnames";
import CompoundComponent from "./CompoundComponent";

interface IPanelLayoutProps extends IDeviceProps {
    children: React.ReactNode;
    className?: string;
    toggleMobileMenu?: (isOpen: boolean) => void;
    contentTag?: string;
    growMiddleBottom?: boolean;
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
 *
 *
 * @example
 *  <PanelLayout>
 *      <PanelLayout.BreadCrumbs>
 *          <p>Put any component here</p>
 *      </PanelLayout.BreadCrumbs>
 *      <PanelLayout.LeftTop>
 *          <p>Put any component here</p>
 *      </PanelLayout.LeftTop>
 *      <PanelLayout.LeftBottom>
 *          <p>Put any component here</p>
 *      </PanelLayout.LeftBottom>
 *      <PanelLayout.MiddleBottom>
 *          <p>Put any component here</p>
 *      </PanelLayout.MiddleBottom>
 * </PanelLayout>
 */
export default class PanelLayout extends CompoundComponent<IPanelLayoutProps> {
    public static LeftTop = LeftTop;
    public static LeftBottom = LeftBottom;
    public static MiddleTop = MiddleTop;
    public static MiddleBottom = MiddleBottom;
    public static RightTop = RightTop;
    public static RightBottom = RightBottom;
    public static Breadcrumbs = Breadcrumbs;

    public static defaultProps = {
        contentTag: "div",
        growMiddleBottom: false,
    };

    public render() {
        const { device } = this.props;
        const children = this.getParsedChildren();

        // Calculate some rendering variables.
        const isMobile = device === Devices.MOBILE;
        const isTablet = device === Devices.TABLET;
        const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop
        const shouldRenderLeftPanel: boolean = !isMobile && !!(children.leftTop || children.leftBottom);
        const shouldRenderRightPanel: boolean =
            (isFullWidth || (isTablet && !shouldRenderLeftPanel)) && !!(children.rightTop || children.rightBottom);

        // Determine the classes we want to display.
        const panelClasses = classNames(
            "panelLayout",
            { noLeftPanel: !shouldRenderLeftPanel },
            { noRightPanel: !shouldRenderRightPanel },
            { noBreadcrumbs: !children.breadcrumbs },
            this.props.className,
            { inheritHeight: this.props.growMiddleBottom },
        );

        // If applicable, set semantic tag, like "article"
        const ContentTag = `${this.props.contentTag}`;

        return (
            <div className={panelClasses}>
                {children.breadcrumbs && (
                    <div className="panelLayout-container">
                        <Panel className={classNames("panelLayout-left")} ariaHidden={true} />
                        <Panel
                            className={classNames("panelLayout-content", "panel-breadcrumbs", {
                                hasAdjacentPanel: shouldRenderLeftPanel,
                            })}
                        >
                            <PanelArea className="panelArea-breadcrumbs">{children.breadcrumbs}</PanelArea>
                        </Panel>
                    </div>
                )}

                <main className={classNames("panelLayout-main", { inheritHeight: this.props.growMiddleBottom })}>
                    <div
                        className={classNames("panelLayout-container", { inheritHeight: this.props.growMiddleBottom })}
                    >
                        {!isMobile &&
                            shouldRenderLeftPanel && (
                                <Panel className={classNames("panelLayout-left")} tag="aside">
                                    {children.leftTop && (
                                        <PanelArea className="panelArea-leftTop">{children.leftTop}</PanelArea>
                                    )}
                                    {children.leftBottom && (
                                        <PanelArea className="panelArea-leftBottom">{children.leftBottom}</PanelArea>
                                    )}
                                </Panel>
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
                                {children.middleTop && (
                                    <PanelArea className="panelAndNav-middleTop">{children.middleTop}</PanelArea>
                                )}
                                {!shouldRenderLeftPanel &&
                                    children.leftTop && (
                                        <PanelArea className="panelAndNav-mobileMiddle" tag="aside">
                                            {children.leftTop}
                                        </PanelArea>
                                    )}
                                {!shouldRenderRightPanel &&
                                    children.rightTop && (
                                        <PanelArea className="panelAndNav-tabletMiddle" tag="aside">
                                            {children.rightTop}
                                        </PanelArea>
                                    )}
                                <PanelArea
                                    className={classNames("panelAndNav-middleBottom", {
                                        inheritHeight: this.props.growMiddleBottom,
                                    })}
                                >
                                    {children.middleBottom}
                                </PanelArea>
                                {!shouldRenderRightPanel &&
                                    children.rightBottom && (
                                        <PanelArea className="panelAndNav-tabletBottom" tag="aside">
                                            {children.rightBottom}
                                        </PanelArea>
                                    )}
                            </Panel>
                            {shouldRenderRightPanel && (
                                <Panel className="panelLayout-right">
                                    {children.rightTop && (
                                        <PanelArea className="panelArea-rightTop" tag="aside">
                                            {children.rightTop}
                                        </PanelArea>
                                    )}
                                    {children.rightBottom && (
                                        <PanelArea className="panelArea-rightBottom" tag="aside">
                                            {children.rightBottom}
                                        </PanelArea>
                                    )}
                                </Panel>
                            )}
                        </ContentTag>
                    </div>
                </main>
            </div>
        );
    }

    /**
     * Parse out a specific subset of children. This is fast enough,
     * but should not be called more than once per render.
     */
    private getParsedChildren() {
        let leftTop: React.ReactNode = null;
        let leftBottom: React.ReactNode = null;
        let middleTop: React.ReactNode = null;
        let middleBottom: React.ReactNode = null;
        let rightTop: React.ReactNode = null;
        let rightBottom: React.ReactNode = null;
        let breadcrumbs: React.ReactNode = null;

        React.Children.forEach(this.props.children, child => {
            switch (true) {
                case this.childIsOfType(child, PanelLayout.LeftTop):
                    leftTop = child;
                    break;
                case this.childIsOfType(child, PanelLayout.LeftBottom):
                    leftBottom = child;
                    break;
                case this.childIsOfType(child, PanelLayout.MiddleTop):
                    middleTop = child;
                    break;
                case this.childIsOfType(child, PanelLayout.MiddleBottom):
                    middleBottom = child;
                    break;
                case this.childIsOfType(child, PanelLayout.RightTop):
                    rightTop = child;
                    break;
                case this.childIsOfType(child, PanelLayout.RightBottom):
                    rightBottom = child;
                    break;
                case this.childIsOfType(child, PanelLayout.Breadcrumbs):
                    breadcrumbs = child;
                    break;
            }
        });

        return {
            leftTop,
            leftBottom,
            middleTop,
            middleBottom,
            rightTop,
            rightBottom,
            breadcrumbs,
        };
    }
}

// Simple container components.
interface IContainerProps {
    className?: string;
    children?: React.ReactNode;
    tag?: string;
    ariaHidden?: boolean;
}

export function Panel(props: IContainerProps) {
    const Tag = `${props.tag ? props.tag : "div"}`;
    return (
        <Tag className={classNames("panelLayout-panel", props.className)} aria-hidden={props.ariaHidden}>
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

// The components that make up the Layout itself.
interface IPanelItemProps {
    children?: React.ReactNode;
}

function LeftTop(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}
LeftTop.type = "LeftTop";

function LeftBottom(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}
LeftBottom.type = "LeftBottom";

function MiddleTop(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}
MiddleTop.type = "MiddleTop";

function MiddleBottom(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}
MiddleBottom.type = "MiddleBottom";

function RightTop(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}
RightTop.type = "RightTop";

function RightBottom(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}
RightBottom.type = "RightBottom";

function Breadcrumbs(props: IPanelItemProps) {
    return <React.Fragment>{props.children}</React.Fragment>;
}
Breadcrumbs.type = "Breadcrumbs";
