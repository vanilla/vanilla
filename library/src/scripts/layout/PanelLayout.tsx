/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { Devices, IDeviceProps, withDevice } from "@library/layout/DeviceContext";
import { ScrollOffsetContext } from "@library/layout/ScrollOffsetContext";
import { inheritHeightClass } from "@library/styles/styleHelpers";
import { panelLayoutClasses } from "@library/layout/panelLayoutStyles";
import { panelAreaClasses } from "@library/layout/panelAreaStyles";
import { panelWidgetClasses } from "@library/layout/panelWidgetStyles";

interface IProps extends IDeviceProps {
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
class PanelLayout extends React.Component<IProps> {
    public static contextType = ScrollOffsetContext;
    public context!: React.ContextType<typeof ScrollOffsetContext>;

    public static defaultProps: Partial<IProps> = {
        contentTag: "div",
        growMiddleBottom: false,
        topPadding: true,
        isFixed: true,
    };

    public render() {
        const { topPadding, className, growMiddleBottom, device, isFixed, ...childComponents } = this.props;

        // Calculate some rendering variables.
        const isMobile = device === Devices.MOBILE || device === Devices.XS;
        const isTablet = device === Devices.TABLET;
        const isFullWidth = [Devices.DESKTOP, Devices.NO_BLEED].includes(device); // This compoment doesn't care about the no bleed, it's the same as desktop
        const shouldRenderLeftPanel: boolean = !isMobile && (!!childComponents.leftTop || !!childComponents.leftBottom);
        const shouldRenderRightPanel: boolean = isFullWidth || (isTablet && !shouldRenderLeftPanel);
        const classes = panelLayoutClasses();

        // Determine the classes we want to display.
        const panelClasses = classNames(
            classes.root,
            { noLeftPanel: !shouldRenderLeftPanel },
            { noRightPanel: !shouldRenderRightPanel },
            { noBreadcrumbs: !childComponents.breadcrumbs },
            className,
            { hasTopPadding: topPadding },
            growMiddleBottom ? inheritHeightClass() : "",
        );

        // If applicable, set semantic tag, like "article"
        const ContentTag = this.props.contentTag as "div";

        return (
            <div className={panelClasses}>
                {childComponents.breadcrumbs && (
                    <div className={classNames(classes.container, classes.breadcrumbsContainer)}>
                        {shouldRenderLeftPanel && (
                            <Panel className={classNames(classes.leftColumn)} ariaHidden={true} />
                        )}
                        <PanelAreaHorizontalPadding
                            className={classNames(classes.middleColumnMaxWidth, {
                                hasAdjacentPanel: shouldRenderLeftPanel,
                            })}
                        >
                            <PanelWidgetHorizontalPadding>{childComponents.breadcrumbs}</PanelWidgetHorizontalPadding>
                        </PanelAreaHorizontalPadding>
                    </div>
                )}

                <main className={classNames(classes.main, this.props.growMiddleBottom ? inheritHeightClass() : "")}>
                    <div
                        className={classNames(
                            classes.container,
                            this.props.growMiddleBottom ? inheritHeightClass() : "",
                        )}
                    >
                        {!isMobile && shouldRenderLeftPanel && (
                            <Panel
                                className={classNames(classes.leftColumn, this.context.offsetClass, {
                                    [classes.isSticky]: isFixed,
                                })}
                                tag="aside"
                            >
                                {childComponents.leftTop && <PanelArea>{childComponents.leftTop}</PanelArea>}
                                {childComponents.leftBottom && <PanelArea>{childComponents.leftBottom}</PanelArea>}
                            </Panel>
                        )}

                        <ContentTag
                            className={classNames(classes.content, classes.middleColumnMaxWidth, {
                                hasAdjacentPanel: shouldRenderLeftPanel || shouldRenderRightPanel,
                                hasTwoAdjacentPanels: shouldRenderLeftPanel && shouldRenderRightPanel,
                            })}
                        >
                            <Panel
                                className={classNames(
                                    classes.middleColumn,
                                    this.props.growMiddleBottom ? inheritHeightClass() : "",
                                )}
                            >
                                {childComponents.middleTop && <PanelArea>{childComponents.middleTop}</PanelArea>}
                                {!shouldRenderLeftPanel && childComponents.leftTop && (
                                    <PanelArea tag="aside">{childComponents.leftTop}</PanelArea>
                                )}
                                {!shouldRenderRightPanel && childComponents.rightTop && (
                                    <PanelArea tag="aside">{childComponents.rightTop}</PanelArea>
                                )}
                                <PanelArea
                                    className={classNames(this.props.growMiddleBottom ? inheritHeightClass() : "")}
                                >
                                    {childComponents.middleBottom}
                                </PanelArea>
                                {!shouldRenderRightPanel && childComponents.rightBottom && (
                                    <PanelArea tag="aside">{childComponents.rightBottom}</PanelArea>
                                )}
                            </Panel>
                        </ContentTag>
                        {shouldRenderRightPanel && (
                            <Panel
                                className={classNames(classes.rightColumn, this.context.offsetClass, {
                                    [classes.isSticky]: isFixed,
                                })}
                            >
                                {childComponents.rightTop && (
                                    <PanelArea tag="aside">{childComponents.rightTop}</PanelArea>
                                )}
                                {childComponents.rightBottom && (
                                    <PanelArea tag="aside">{childComponents.rightBottom}</PanelArea>
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
    tag?: keyof JSX.IntrinsicElements;
    ariaHidden?: boolean;
    innerRef?: React.RefObject<HTMLDivElement>;
}

export function Panel(props: IContainerProps) {
    const Tag = (props.tag as "div") || "div";
    const classes = panelLayoutClasses();
    return (
        <Tag className={classNames(classes.panel, props.className)} aria-hidden={props.ariaHidden} ref={props.innerRef}>
            {props.children}
        </Tag>
    );
}

export function PanelArea(props: IContainerProps) {
    const Tag = props.tag || "div";
    const classes = panelAreaClasses();
    return <Tag className={classNames(classes.root, props.className)}>{props.children}</Tag>;
}

export function PanelAreaHorizontalPadding(props: IContainerProps) {
    const Tag = props.tag || "div";
    const classes = panelAreaClasses();
    return <Tag className={classNames(classes.root, props.className, "hasNoVerticalPadding")}>{props.children}</Tag>;
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

export default withDevice(PanelLayout);
