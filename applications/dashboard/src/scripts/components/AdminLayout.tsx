import React, { ReactNode, useMemo } from "react";

import Container from "@library/layout/components/Container";
import { adminLayoutClasses } from "@dashboard/components/AdminLayout.classes";
import { cx } from "@emotion/css";
import AdminHeader from "@dashboard/components/AdminHeader";
import SectionThreeColumns from "@library/layout/ThreeColumnSection";
import AdminTitleBar from "@dashboard/components/AdminTitleBar";
import { userContentClasses } from "@library/content/UserContent.styles";

type IProps = {
    /** The content which should be rendered in the hamburger menu on mobile */
    adminBarHamburgerContent?: ReactNode;
    /** ID of the active section */
    activeSectionID?: string;
    /** Usually used for the nav */
    leftPanel: ReactNode;
    /** Usually used for help text */
    rightPanel?: ReactNode;
    /** The main content of the page, spans the right panel space if no right panel is specified */
    content: ReactNode;
    /** Classes applied to the content wrapper */
    contentClassNames?: string;
    /** Any other nodes that need to render within the container */
    children?: ReactNode;
} & (
    | {
          customTitleBar?: false;
          /** The admin page title */
          title: string;
          /** Classes applied to the title bar container */
          containerClassName?: string;
          titleBarContainerClassName?: string;
          /** Classes applied to the title bar container */
          titleAndActionsContainerClassName?: string;
          /** Any notes that should appear within the title bar, usually action buttons */
          titleBarActions?: React.ReactNode;
          /** Classes applied to the title bar actions */
          actionsWrapperClassName?: string;
          /** Any nodes that should appear below the title */
          description?: ReactNode;
          /** Any extra nodes appearing near title for highlight reasons(e.g. APPLIED etc) */
          titleLabel?: ReactNode;
      }
    | {
          /** Replaces the default <AdminTitleBar /> */
          customTitleBar: ReactNode;
          title?: never;
          containerClassName?: never;
          titleBarContainerClassName?: never;
          titleAndActionsContainerClassName?: never;
          titleBarActions?: never;
          actionsWrapperClassName?: never;
          description?: never;
          titleLabel?: never;
      }
);

export default function AdminLayout(props: IProps) {
    const classes = adminLayoutClasses();
    const {
        leftPanel,
        rightPanel,
        content,
        customTitleBar,
        title,
        titleAndActionsContainerClassName,
        titleBarActions,
        contentClassNames,
        description,
        titleLabel,
    } = props;

    const topTitleBar = useMemo(
        () =>
            customTitleBar ? (
                customTitleBar
            ) : (
                <AdminTitleBar
                    useTwoColumnContainer={!props.rightPanel}
                    title={title ?? ""}
                    description={description}
                    containerClassName={props.titleBarContainerClassName}
                    titleAndActionsContainerClassName={titleAndActionsContainerClassName}
                    actions={titleBarActions}
                    titleLabel={titleLabel}
                />
            ),
        [classes, customTitleBar, title, titleAndActionsContainerClassName, titleBarActions],
    );

    return (
        <>
            <AdminHeader hamburgerContent={props.adminBarHamburgerContent} activeSectionID={props.activeSectionID} />
            <div className={classes.container}>
                <Container fullGutter className={rightPanel ? classes.adjustedContainerPadding : undefined}>
                    {rightPanel && (
                        <SectionThreeColumns
                            className={classes.threePanel}
                            leftTop={<aside className={classes.leftPanel}>{leftPanel}</aside>}
                            middleTop={topTitleBar}
                            middleBottom={<div className={contentClassNames}>{content}</div>}
                            rightBottom={
                                <aside className={cx(userContentClasses().root, classes.helpText)}>{rightPanel}</aside>
                            }
                            topPadding={false}
                        />
                    )}
                    {!rightPanel && (
                        <div className={classes.layout}>
                            {!!props.leftPanel && (
                                <aside className={cx(classes.leftPanel, classes.twoColLeftPanel)}>
                                    {props.leftPanel}
                                </aside>
                            )}
                            <div className={cx(classes.rightPanel, !props.leftPanel ? classes.noLeftPanel : undefined)}>
                                {topTitleBar}
                                {!!props.content && (
                                    <div
                                        className={cx(
                                            classes.content,
                                            props.contentClassNames,
                                            !props.leftPanel ? classes.contentNoLeftPanel : undefined,
                                        )}
                                    >
                                        {props.content}
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                    {props.children}
                </Container>
            </div>
        </>
    );
}
