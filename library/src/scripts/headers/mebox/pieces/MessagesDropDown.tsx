/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { getMeta, t } from "@library/utility/appUtils";
import MessagesCount from "@library/headers/mebox/pieces/MessagesCount";
import MessagesContents from "@library/headers/mebox/pieces/MessagesContents";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";

interface IProps {
    buttonClassName?: string;
    className?: string;
    contentsClassName?: string;
    toggleContentClassName?: string;
    countClass?: string;
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
                name={t("Messages")}
                renderLeft={true}
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
