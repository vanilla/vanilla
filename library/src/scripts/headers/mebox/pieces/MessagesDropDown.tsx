/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import MessagesContents from "@library/headers/mebox/pieces/MessagesContents";
import MessagesCount from "@library/headers/mebox/pieces/MessagesCount";
import { useTitleBarParamVarOverrides } from "@library/headers/TitleBar.ParamContext";
import { titleBarClasses } from "@library/headers/TitleBar.classes";
import { getMeta } from "@library/utility/appUtils";
import { uniqueIDFromPrefix, useUniqueID } from "@library/utility/idUtils";
import React, { useState } from "react";
import { sprintf } from "sprintf-js";

interface IProps {
    buttonClassName?: string;
    className?: string;
    contentsClassName?: string;
    toggleContentClassName?: string;
    countClass?: string;
    count: number;
}

interface IState {
    open: boolean;
}

/**
 * Implements Messages Drop down for header
 */
export default function MessagesDropDown(props: IProps) {
    const conversations = getMeta("context.conversationsEnabled", false);
    const varOverrides = useTitleBarParamVarOverrides();
    const classesHeader = titleBarClasses.useAsHook(varOverrides);
    const [open, setOpen] = useState(false);
    const id = useUniqueID();

    if (!conversations) {
        return <></>;
    }

    return (
        <DropDown
            contentID={id + "-content"}
            handleID={id + "-handle"}
            name={sprintf("Messages: %s", props.count)}
            renderLeft={!getMeta("ui.isDirectionRTL", false)}
            buttonClassName={classesHeader.button}
            contentsClassName={classesHeader.dropDownContents}
            buttonContents={<MessagesCount open={open} compact={false} />}
            onVisibilityChange={setOpen}
            flyoutType={FlyoutType.FRAME}
            onHover={MessagesContents.preload}
        >
            <MessagesContents countClass={props.countClass} />
        </DropDown>
    );
}
