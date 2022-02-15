/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IUserFragment } from "@library/@types/api/users";
import { IHomeWidgetContainerOptions } from "@library/homeWidget/HomeWidgetContainer.styles";
import { HomeWidgetContainer } from "@library/homeWidget/HomeWidgetContainer";
import { UserSpotlight } from "@library/userSpotlight/UserSpotlight";
import { IUserSpotlightOptions } from "@library/userSpotlight/UserSpotlight.variables";
import { DeepPartial } from "redux";

interface IProps {
    title?: string;
    description?: string;
    userTextAlignment?: "left" | "right";
    containerOptions?: IHomeWidgetContainerOptions;
    userInfo: IUserFragment;
}

export function UserSpotlightWidget(props: IProps) {
    const { title, description, containerOptions, userInfo, userTextAlignment = "left" } = props;
    const options = { ...containerOptions, userTextAlignment } as DeepPartial<IUserSpotlightOptions>;

    return (
        <HomeWidgetContainer options={containerOptions}>
            <UserSpotlight title={title} description={description} options={options} userInfo={userInfo} />
        </HomeWidgetContainer>
    );
}
