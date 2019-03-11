/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Devices, IDeviceProps } from "@library/components/DeviceChecker";
import { withDevice } from "@library/contexts/DeviceContext";
import { ScrollOffsetContext } from "@library/contexts/ScrollOffsetContext";
import { inheritHeightClass, sticky } from "@library/styles/styleHelpers";
import { vanillaHeaderVariables } from "@library/styles/vanillaHeaderStyles";
import classNames from "classnames";
import { calc, px, percent } from "csx";
import * as React from "react";
import { style } from "typestyle";

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

    public render() {
        const { topPadding, className, growMiddleBottom, device, isFixed, ...childComponents } = this.props;

        // Calculate some rendering variables.
        const isMobile = device === Devices.MOBILE;
        const isTablet = device === Devices.TABLET;
        const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop
        const shouldRenderLeftPanel: boolean = !isMobile && (!!childComponents.leftTop || !!childComponents.leftBottom);
        const shouldRenderRightPanel: boolean =
            (isFullWidth || (isTablet && !shouldRenderLeftPanel)) &&
            !!(childComponents.rightTop || childComponents.rightBottom) &&
            (!!this.props.rightTop || !!this.props.rightTop);

        // Determine the classes we want to display.
        const panelClasses = classNames(
            "panelLayout",
            { noLeftPanel: !shouldRenderLeftPanel },
            { noRightPanel: !shouldRenderRightPanel },
            { noBreadcrumbs: !childComponents.breadcrumbs },
            className,
            { hasTopPadding: topPadding },
            growMiddleBottom ? inheritHeightClass() : "",
        );
        const headerVars = vanillaHeaderVariables();

        const fixedPanelClass = style(sticky(), {
            $debugName: "fixedPanelClasses",
            top: headerVars.sizing.height * 2,
            // maxHeight: calc(`100vh - ${px(headerVars.sizing.height * 2)}`),
            height: percent(100),
            overflow: "auto",
        });

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
                                hasAdjacentPanel: shouldRenderLeftPanel || shouldRenderRightPanel,
                                hasTwoAdjacentPanels: shouldRenderLeftPanel && shouldRenderRightPanel,
                            })}
                        >
                            <PanelArea>{childComponents.breadcrumbs}</PanelArea>
                        </Panel>
                        {shouldRenderRightPanel && (
                            <Panel className={classNames("panelLayout-right")} ariaHidden={true} />
                        )}
                    </div>
                )}

                <main
                    className={classNames("panelLayout-main", this.props.growMiddleBottom ? inheritHeightClass() : "")}
                >
                    <div
                        className={classNames(
                            "panelLayout-container",
                            this.props.growMiddleBottom ? inheritHeightClass() : "",
                        )}
                    >
                        {!isMobile &&
                            shouldRenderLeftPanel && (
                                <Panel
                                    className={classNames(
                                        "panelLayout-left",
                                        { [fixedPanelClass]: isFixed },
                                        this.context.offsetClass,
                                    )}
                                    tag="aside"
                                >
                                    {childComponents.leftTop && (
                                        <PanelArea className="panelArea-leftTop">{childComponents.leftTop}</PanelArea>
                                    )}
                                    {childComponents.leftBottom && (
                                        <PanelArea className="panelArea-leftBottom">
                                            {childComponents.leftBottom}
                                        </PanelArea>
                                    )}
                                </Panel>
                            )}

                        <ContentTag
                            className={classNames("panelLayout-content", {
                                hasAdjacentPanel: shouldRenderLeftPanel || shouldRenderRightPanel,
                                hasTwoAdjacentPanels: shouldRenderLeftPanel && shouldRenderRightPanel,
                            })}
                        >
                            <Panel
                                className={classNames(
                                    "panelLayout-middle",
                                    this.props.growMiddleBottom ? inheritHeightClass() : "",
                                )}
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
                                    className={classNames(
                                        "panelAndNav-middleBottom",
                                        this.props.growMiddleBottom ? inheritHeightClass() : "",
                                    )}
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
                        </ContentTag>
                        {shouldRenderRightPanel && (
                            <Panel
                                className={classNames(
                                    "panelLayout-right",
                                    { [fixedPanelClass]: isFixed },
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
                        )}
                    </div>
                </main>
            </div>
        );
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
