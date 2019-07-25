/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { titleBarClasses } from "@library/headers/titleBarStyles";
import { t } from "@library/utility/appUtils";
import MessagesCount from "@library/headers/mebox/pieces/MessagesCount";
import MessagesContents from "@library/headers/mebox/pieces/MessagesContents";
import { uniqueIDFromPrefix } from "@library/utility/idUtils";
import DropDown from "@library/flyouts/DropDown";
import classNames from "classnames";

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
        const classesHeader = titleBarClasses();
        return (
            <DropDown
                id={this.id}
                name={t("Messages")}
                renderLeft={true}
                buttonClassName={classesHeader.button}
                contentsClassName={classesHeader.dropDownContents}
                buttonContents={<MessagesCount open={this.state.open} compact={false} />}
                onVisibilityChange={this.setOpen}
                selfPadded={true}
            >
                <MessagesContents countClass={this.props.countClass} />
            </DropDown>
        );
    }

    /**
     * Assign the open (visibile) state of this component.
     *
     * @param open Is this menu open and visible?
     */
    private setOpen = open => {
        this.setState({
            open,
        });
    };
}
