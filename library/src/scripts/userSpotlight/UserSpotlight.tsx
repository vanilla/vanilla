/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useRef } from "react";
import { LocationDescriptor } from "history";
import { DeepPartial } from "redux";
import { userSpotlightVariables, IUserSpotlightOptions } from "./UserSpotlight.variables";
import { userSpotlightClasses } from "./UserSpotlight.classes";
import Container from "@library/layout/components/Container";
import ProfileLink from "@library/navigation/ProfileLink";
import { IUserFragment } from "@library/@types/api/users";
import { UserPhoto } from "@library/headers/mebox/pieces/UserPhoto";
import { useMeasure } from "@vanilla/react-utils/src";
import { forumLayoutVariables } from "@dashboard/compatibilityStyles/forumLayoutStyles";
import { useWidgetLayoutClasses } from "@library/layout/WidgetLayout.context";

export interface IUserSpotlightProps {
    title?: string;
    description?: string;
    options?: DeepPartial<IUserSpotlightOptions>;
    userInfo: IUserFragment;
}

export function UserSpotlight(props: IUserSpotlightProps) {
    const rootRef = useRef<HTMLDivElement | null>(null);
    const rootMeasure = useMeasure(rootRef);
    const shouldWrap = rootMeasure.width > 0 && rootMeasure.width < forumLayoutVariables().panel.paddedWidth;
    const variables = userSpotlightVariables();
    const classes = userSpotlightClasses({ options: props.options, shouldWrap });
    const widgetClasses = useWidgetLayoutClasses();

    return (
        <div className={widgetClasses.widgetClass}>
            <Container fullGutter>
                <div ref={rootRef} className={classes.root}>
                    <div className={classes.avatarContainer}>
                        <ProfileLink userFragment={props.userInfo} className={classes.avatarLink}>
                            <UserPhoto
                                userInfo={props.userInfo}
                                size={variables.avatar.size}
                                className={classes.avatar}
                            />
                        </ProfileLink>
                    </div>
                    <div className={classes.textContainer}>
                        <div className={classes.title}>{props.title}</div>
                        <div className={classes.description}>{props.description}</div>
                        <div className={classes.userText}>
                            <ProfileLink userFragment={props.userInfo} className={classes.userName}>
                                {props.userInfo.name}
                                {props.userInfo.title && <span>, </span>}
                            </ProfileLink>
                            {props.userInfo.title && <span className={classes.userTitle}>{props.userInfo.title}</span>}
                        </div>
                    </div>
                </div>
            </Container>
        </div>
    );
}
