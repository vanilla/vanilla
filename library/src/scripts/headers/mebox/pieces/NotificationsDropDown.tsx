/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import NotificationsContents from "@library/headers/mebox/pieces/NotificationsContents";
import type NotificationsContentsImpl from "@library/headers/mebox/pieces/NotificationsContentsImpl";
import NotificationsCount from "@library/headers/mebox/pieces/NotificationsCount";
import { useTitleBarParamVarOverrides } from "@library/headers/TitleBar.ParamContext";
import { titleBarClasses } from "@library/headers/TitleBar.classes";
import { getMeta } from "@library/utility/appUtils";
import { uniqueIDFromPrefix, useUniqueID } from "@library/utility/idUtils";
import React, { useState } from "react";
import { sprintf } from "sprintf-js";

interface IProps extends React.ComponentProps<typeof NotificationsContentsImpl> {
    countUnread: number;
    userSlug: string;
}

/**
 * Implements Notifications menu for header
 */
export default function NotificationsDropDown(props: IProps) {
    const varOverrides = useTitleBarParamVarOverrides();
    const classesHeader = titleBarClasses.useAsHook(varOverrides);
    const [open, setOpen] = useState(false);
    const id = useUniqueID();

    const { userSlug } = props;

    return (
        <DropDown
            contentID={id + "-content"}
            handleID={id + "-handle"}
            name={sprintf("Notifications: %s", props.countUnread)}
            renderLeft={!getMeta("ui.isDirectionRTL", false)}
            buttonClassName={classesHeader.button}
            contentsClassName={classesHeader.dropDownContents}
            buttonContents={<NotificationsCount open={open} compact={false} />}
            onVisibilityChange={setOpen}
            flyoutType={FlyoutType.FRAME}
            onHover={NotificationsContents.preload}
        >
            <NotificationsContents userSlug={userSlug} />
        </DropDown>
    );
}
