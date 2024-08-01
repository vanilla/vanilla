/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import MessagesContents from "@library/headers/mebox/pieces/MessagesContents";
import MessagesCount from "@library/headers/mebox/pieces/MessagesCount";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { getMeta } from "@library/utility/appUtils";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import React from "react";
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
export default class MessagesDropDown extends React.Component<IProps, IState> {
    private id = uniqueIDFromPrefix("messagesDropDown");

    public state: IState = {
        open: false,
    };

    /**
     * Get the React component to added to the page.
     *
     * @returns A DropDown component, configured to display notifications.
     */
    public render() {
        const conversations = getMeta("context.conversationsEnabled", false);
        const classesHeader = titleBarClasses();

        if (!conversations) return null;

        return (
            <DropDown
                contentID={this.id + "-content"}
                handleID={this.id + "-handle"}
                name={sprintf("Messages: %s", this.props.count)}
                renderLeft={!getMeta("ui.isDirectionRTL", false)}
                buttonClassName={classesHeader.button}
                contentsClassName={classesHeader.dropDownContents}
                buttonContents={<MessagesCount open={this.state.open} compact={false} />}
                onVisibilityChange={this.setOpen}
                flyoutType={FlyoutType.FRAME}
                onHover={MessagesContents.preload}
            >
                <MessagesContents countClass={this.props.countClass} />
            </DropDown>
        );
    }

    /**
     * Assign the open (visible) state of this component.
     *
     * @param open Is this menu open and visible?
     */
    private setOpen = (open) => {
        this.setState({
            open,
        });
    };
}
