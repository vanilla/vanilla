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
import { LayoutWidget } from "@library/layout/LayoutWidget";
import { BorderType } from "@library/styles/styleHelpersBorders";
interface IProps {
    title?: string;
    subtitle?: string;
    description?: string;
    userTextAlignment?: "left" | "right";
    containerOptions?: IHomeWidgetContainerOptions;
    userInfo: IUserFragment;
    apiParams?: {
        userID?: number;
    };
}

export function UserSpotlightWidget(props: IProps) {
    const { title, subtitle, description, containerOptions, userInfo, userTextAlignment = "left" } = props;
    const options = {
        ...props.containerOptions,
        userTextAlignment,
    } as DeepPartial<IUserSpotlightOptions>;

    return (
        <LayoutWidget>
            <UserSpotlight
                title={title}
                subtitle={subtitle}
                description={description}
                options={options}
                userInfo={userInfo}
            />
        </LayoutWidget>
    );
}

export default UserSpotlightWidget;
