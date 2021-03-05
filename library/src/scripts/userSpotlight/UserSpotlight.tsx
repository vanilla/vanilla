/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import SmartLink from "@library/routing/links/SmartLink";
import { LocationDescriptor } from "history";
import { DeepPartial } from "redux";
import { userSpotlightVariables, IUserSpotlightOptions } from "./UserSpotlight.variables";
import { userSpotlightClasses } from "./UserSpotlight.classes";
import Container from "@library/layout/components/Container";

export interface IUserSpotlightProps {
    title?: string;
    description?: string;
    userUrl: LocationDescriptor;
    userPhotoUrl?: string;
    userName?: string;
    userTitle?: string;
    options?: DeepPartial<IUserSpotlightOptions>;
}

export function UserSpotlight(props: IUserSpotlightProps) {
    const classes = userSpotlightClasses(props.options);
    const options = userSpotlightVariables(props.options).options;

    let content = (
        <div className={classes.root}>
            <div className={classes.avatarContainer}>
                <SmartLink to={props.userUrl} className={classes.avatarLink}>
                    <img src={props.userPhotoUrl} alt={props.userName} className={classes.avatar} />
                </SmartLink>
            </div>
            <div className={classes.textContainer}>
                <div className={classes.title}>{props.title}</div>
                <div className={classes.description}>{props.description}</div>
                <div className={classes.userText}>
                    <SmartLink to={props.userUrl} className={classes.userName}>
                        {props.userName}
                        {props.userTitle && <span>, </span>}
                    </SmartLink>
                    {props.userTitle && <span className={classes.userTitle}>{props.userTitle}</span>}
                </div>
            </div>
        </div>
    );

    if (!options.container.noGutter) {
        content = <Container fullGutter>{content}</Container>;
    }

    return <>{content}</>;
}
