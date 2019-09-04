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
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import classNames from "classnames";
import Frame from "@library/layout/frame/Frame";
import FrameHeaderWithAction from "@library/layout/frame/FrameHeaderWithAction";
import LinkAsButton from "@library/routing/LinkAsButton";
import { ButtonTypes, buttonUtilityClasses } from "@library/forms/buttonStyles";
import { ComposeIcon } from "@library/icons/titleBar";
import FrameBody from "@library/layout/frame/FrameBody";
import FrameFooter from "@library/layout/frame/FrameFooter";
import MeBoxDropDownItemList from "@library/headers/mebox/pieces/MeBoxDropDownItemList";
import { IMeBoxMessageItem, MeBoxItemType } from "@library/headers/mebox/pieces/MeBoxDropDownItem";

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

import imageFile from "../../../../applications/dashboard/design/images/defaulticon.png";

const messagesData = [
    {
        authors: [
            {
                userID: 25,
                name: "Joe With Spaces",
                photoUrl: imageFile,
                dateLastActive: "2018-07-23T16:32:50+00:00",
            },
            {
                userID: 2,
                name: "admin",
                photoUrl: imageFile,
                dateLastActive: "2019-08-21T12:43:58+00:00",
            },
        ],
        countMessages: 1,
        message: "You've been invited to join SHHHHHHHHHH!.",
        photo: imageFile,
        to: "https://dev.vanilla.localhost/en-hutch/messages/87",
        recordID: 87,
        timestamp: "2018-08-10T20:08:16+00:00",
        type: MeBoxItemType.MESSAGE,
        unread: false,
    },
    {
        authors: [
            {
                userID: 25,
                name: "Joe With Spaces",
                photoUrl: imageFile,
                dateLastActive: "2018-07-23T16:32:50+00:00",
            },
            {
                userID: 2,
                name: "admin",
                photoUrl: imageFile,
                dateLastActive: "2019-08-21T12:43:58+00:00",
            },
        ],
        countMessages: 1,
        message: "You've been invited to join Top Secret.",
        photo: imageFile,
        to: "https://dev.vanilla.localhost/en-hutch/messages/86",
        recordID: 86,
        timestamp: "2018-07-25T18:58:23+00:00",
        type: MeBoxItemType.MESSAGE,
        unread: false,
    },
    {
        authors: [
            {
                userID: 25,
                name: "Joe With Spaces",
                photoUrl: imageFile,
                dateLastActive: "2018-07-23T16:32:50+00:00",
            },
            {
                userID: 2,
                name: "admin",
                photoUrl: imageFile,
                dateLastActive: "2019-08-21T12:43:58+00:00",
            },
        ],
        countMessages: 1,
        message: "You've been invited to join SHHHHHHHHHH!.",
        photo: imageFile,
        to: "https://dev.vanilla.localhost/en-hutch/messages/85",
        recordID: 85,
        timestamp: "2018-07-25T18:51:10+00:00",
        type: MeBoxItemType.MESSAGE,
        unread: false,
    },
    {
        authors: [
            {
                userID: 3,
                name: "joe",
                photoUrl: imageFile,
                dateLastActive: "2019-01-04T15:15:07+00:00",
            },
            {
                userID: 25,
                name: "Joe With Spaces",
                photoUrl: imageFile,
                dateLastActive: "2018-07-23T16:32:50+00:00",
            },
            {
                userID: 2,
                name: "admin",
                photoUrl: imageFile,
                dateLastActive: "2019-08-21T12:43:58+00:00",
            },
        ],
        countMessages: 1,
        message: "You've been invited to join Top Secret.",
        photo: imageFile,
        to: "https://dev.vanilla.localhost/en-hutch/messages/84",
        recordID: 84,
        timestamp: "2018-07-25T18:51:03+00:00",
        type: MeBoxItemType.MESSAGE,
        unread: false,
    },
    {
        authors: [
            {
                userID: 25,
                name: "Joe With Spaces",
                photoUrl: imageFile,
                dateLastActive: "2018-07-23T16:32:50+00:00",
            },
            {
                userID: 2,
                name: "admin",
                photoUrl: imageFile,
                dateLastActive: "2019-08-21T12:43:58+00:00",
            },
        ],
        countMessages: 1,
        message: "You've been invited to join Top Secret.",
        photo: imageFile,
        to: "https://dev.vanilla.localhost/en-hutch/messages/83",
        recordID: 83,
        timestamp: "2018-07-25T18:50:46+00:00",
        type: MeBoxItemType.MESSAGE,
        unread: false,
    },
    {
        authors: [
            {
                userID: 25,
                name: "Joe With Spaces",
                photoUrl: imageFile,
                dateLastActive: "2018-07-23T16:32:50+00:00",
            },
            {
                userID: 2,
                name: "admin",
                photoUrl: imageFile,
                dateLastActive: "2019-08-21T12:43:58+00:00",
            },
        ],
        countMessages: 1,
        message: "You've been invited to join SHHHHHHHHHH!.",
        photo: imageFile,
        to: "https://dev.vanilla.localhost/en-hutch/messages/82",
        recordID: 82,
        timestamp: "2018-07-25T18:50:38+00:00",
        type: MeBoxItemType.MESSAGE,
        unread: false,
    },
    {
        authors: [
            {
                userID: 25,
                name: "Joe With Spaces",
                photoUrl: imageFile,
                dateLastActive: "2018-07-23T16:32:50+00:00",
            },
            {
                userID: 2,
                name: "admin",
                photoUrl: imageFile,
                dateLastActive: "2019-08-21T12:43:58+00:00",
            },
        ],
        countMessages: 1,
        message: "You've been invited to join SHHHHHHHHHH!.",
        photo: imageFile,
        to: "https://dev.vanilla.localhost/en-hutch/messages/81",
        recordID: 81,
        timestamp: "2018-07-25T18:48:46+00:00",
        type: MeBoxItemType.MESSAGE,
        unread: false,
    },
    {
        authors: [
            {
                userID: 25,
                name: "Joe With Spaces",
                photoUrl: imageFile,
                dateLastActive: "2018-07-23T16:32:50+00:00",
            },
            {
                userID: 2,
                name: "admin",
                photoUrl: imageFile,
                dateLastActive: "2019-08-21T12:43:58+00:00",
            },
        ],
        countMessages: 1,
        message: "You've been invited to join SHHHHHHHHHH!.",
        photo: imageFile,
        to: "https://dev.vanilla.localhost/en-hutch/messages/80",
        recordID: 80,
        timestamp: "2018-07-25T18:45:04+00:00",
        type: MeBoxItemType.MESSAGE,
        unread: false,
    },
    {
        authors: [
            {
                userID: 25,
                name: "Joe With Spaces",
                photoUrl: imageFile,
                dateLastActive: "2018-07-23T16:32:50+00:00",
            },
            {
                userID: 2,
                name: "admin",
                photoUrl: imageFile,
                dateLastActive: "2019-08-21T12:43:58+00:00",
            },
        ],
        countMessages: 1,
        message: "You've been invited to join Top Secret.",
        photo: imageFile,
        to: "https://dev.vanilla.localhost/en-hutch/messages/79",
        recordID: 79,
        timestamp: "2018-07-25T18:44:57+00:00",
        type: MeBoxItemType.MESSAGE,
        unread: false,
    },
    {
        authors: [
            {
                userID: 25,
                name: "Joe With Spaces",
                photoUrl: imageFile,
                dateLastActive: "2018-07-23T16:32:50+00:00",
            },
            {
                userID: 2,
                name: "admin",
                photoUrl: imageFile,
                dateLastActive: "2019-08-21T12:43:58+00:00",
            },
        ],
        countMessages: 1,
        message: "You've been invited to join Top Secret.",
        photo: imageFile,
        to: "https://dev.vanilla.localhost/en-hutch/messages/78",
        recordID: 78,
        timestamp: "2018-07-25T18:44:16+00:00",
        type: MeBoxItemType.MESSAGE,
        unread: false,
    },
];

/**
 * Implements Messages Drop down for header
 */
export default class StoryExampleMessagesDropDown extends React.Component<IProps, IState> {
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
        const buttonUtils = buttonUtilityClasses();
        return (
            <DropDown
                id={this.id}
                name={t("Messages")}
                renderLeft={true}
                buttonClassName={classesHeader.button}
                contentsClassName={classesHeader.dropDownContents}
                buttonContents={<MessagesCount open={this.state.open} compact={false} />}
                onVisibilityChange={this.setOpen}
                flyoutType={FlyoutType.FRAME}
            >
                <Frame
                    className={this.props.className}
                    canGrow={true}
                    header={
                        <FrameHeaderWithAction title={"Messages"}>
                            <LinkAsButton
                                title={t("New Message")}
                                to={"#"}
                                baseClass={ButtonTypes.ICON}
                                className={classNames(buttonUtils.pushRight)}
                            >
                                <ComposeIcon />
                            </LinkAsButton>
                        </FrameHeaderWithAction>
                    }
                    body={
                        <FrameBody className={classNames("isSelfPadded")}>
                            <MeBoxDropDownItemList
                                emptyMessage={t("You do not have any messages yet.")}
                                className="headerDropDown-messages"
                                type={MeBoxItemType.MESSAGE}
                                data={messagesData as IMeBoxMessageItem[]}
                            />
                        </FrameBody>
                    }
                    footer={
                        <FrameFooter>
                            <LinkAsButton
                                className={classNames(buttonUtils.pushLeft)}
                                to={"/messages/inbox"}
                                baseClass={ButtonTypes.TEXT_PRIMARY}
                            >
                                {t("All Messages")}
                            </LinkAsButton>
                        </FrameFooter>
                    }
                />
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
