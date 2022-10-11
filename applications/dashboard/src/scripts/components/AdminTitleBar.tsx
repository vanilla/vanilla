/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { ReactNode } from "react";
import { adminTitleBarClasses } from "@dashboard/components/AdminTitleBar.classes";
import { cx } from "@emotion/css";
import TruncatedText from "@library/content/TruncatedText";
import { LoadingRectangle } from "@library/loaders/LoadingRectangle";
import { userContentClasses } from "@library/content/UserContent.styles";
import { useStackingContext } from "@vanilla/react-utils";

export interface IProps {
    title: string;
    containerClassName?: string;
    titleAndActionsContainerClassName?: string;
    actions?: React.ReactNode;
    actionsWrapperClassName?: string;
    description?: ReactNode;
    titleLabel?: ReactNode;
    useTwoColumnContainer?: boolean;
}

export default function AdminTitleBar(props: IProps) {
    const { zIndex } = useStackingContext();

    const classes = adminTitleBarClasses({ zIndex });

    return (
        <div className={classes.root}>
            <div className={cx(classes.container(props.useTwoColumnContainer), props.containerClassName)}>
                <div className={cx(classes.titleAndActionsContainer, props.titleAndActionsContainerClassName)}>
                    <div className={classes.titleAndDescriptionContainer}>
                        <h2 className={classes.titleWrap} title={props.title}>
                            <TruncatedText lines={1} className={classes.title}>
                                {props.title ?? <LoadingRectangle height={32} width={300} />}
                            </TruncatedText>
                            {props.titleLabel ?? undefined}
                        </h2>
                        {props.description && (
                            <div className={cx(classes.descriptionWrapper)}>
                                <div className={userContentClasses().root}>
                                    <div className={cx(classes.description)}>{props.description}</div>
                                </div>
                            </div>
                        )}
                    </div>
                    {props.actions && (
                        <div className={cx(classes.actionsWrapper, props.actionsWrapperClassName)}>{props.actions}</div>
                    )}
                </div>
            </div>
        </div>
    );
}
