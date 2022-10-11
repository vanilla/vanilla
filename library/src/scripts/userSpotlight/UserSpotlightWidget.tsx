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
import { Widget } from "@library/layout/Widget";
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
        ...containerOptions,
        borderType: containerOptions?.borderType ?? BorderType.SHADOW,
        userTextAlignment,
    } as DeepPartial<IUserSpotlightOptions>;

    return (
        <Widget>
            <HomeWidgetContainer options={containerOptions}>
                <UserSpotlight
                    title={title}
                    subtitle={subtitle}
                    description={description}
                    options={options}
                    userInfo={userInfo}
                />
            </HomeWidgetContainer>
        </Widget>
    );
}

export default UserSpotlightWidget;
