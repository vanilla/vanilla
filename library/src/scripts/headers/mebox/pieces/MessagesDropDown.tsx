/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "../../../dom/appUtils";
import { uniqueIDFromPrefix } from "../../../utility/idUtils";
import DropDown from "../../../flyouts/DropDown";
import MessagesContents from "library/src/scripts/headers/mebox/pieces/MessagesContents";
import MessagesCount from "library/src/scripts/headers/mebox/pieces/MessagesCount";
import classNames from "classnames";
import * as React from "react";
import { vanillaHeaderClasses } from "../../vanillaHeaderStyles";

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
        const classesHeader = vanillaHeaderClasses();
        return (
            <DropDown
                id={this.id}
                name={t("Messages")}
                buttonClassName={classNames("vanillaHeader-messages", this.props.buttonClassName)}
                renderLeft={true}
                contentsClassName={classNames(this.props.contentsClassName, classesHeader.dropDownContents)}
                toggleButtonClassName="vanillaHeader-button"
                buttonContents={<MessagesCount open={this.state.open} className={this.props.toggleContentClassName} />}
                onVisibilityChange={this.setOpen}
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
