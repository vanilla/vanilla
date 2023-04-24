/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IUserFragment } from "@library/@types/api/users";
import { t } from "@library/utility/appUtils";
import FlexSpacer from "@library/layout/FlexSpacer";
import SmartLink from "@library/routing/links/SmartLink";
import { meBoxMessageClasses } from "@library/headers/mebox/pieces/meBoxMessageStyles";
import { metasClasses } from "@library/metas/Metas.styles";
import Translate from "@library/content/Translate";
import classNames from "classnames";
import { NoUserPhotoIcon } from "@library/icons/titleBar";
import { userPhotoClasses } from "@library/headers/mebox/pieces/userPhotoStyles";
import DateTime from "@library/content/DateTime";
import StatusLight from "@library/statusLight/StatusLight";

export enum MeBoxItemType {
    NOTIFICATION = "notification",
    MESSAGE = "message",
}

// Common to both notifications and messages dropdowns
export interface IMeBoxItem {
    className?: string;
    message: string;
    messageHtml?: string;
    photo: string | null;
    photoAlt: string;
    activityName?: string;
    recordID: number;
    timestamp: string;
    to: string;
    unread?: boolean;
}

export interface IMeBoxMessageItem extends IMeBoxItem {
    authors: IUserFragment[];
    countMessages: number;
    type: MeBoxItemType.MESSAGE;
}

export interface IMeBoxNotificationItem extends IMeBoxItem {
    type: MeBoxItemType.NOTIFICATION;
}

type IProps = IMeBoxMessageItem | IMeBoxNotificationItem;

/**
 * Implements Drop down message (for notifications or messages)
 */
export default class MeBoxDropDownItem extends React.Component<IProps> {
    public render() {
        const { unread, message, messageHtml, timestamp, to, photoAlt, photo } = this.props;
        const classesMeBoxMessage = meBoxMessageClasses();
        const classesMetas = metasClasses();

        let authorList: JSX.Element[];

        if (this.props.type === MeBoxItemType.MESSAGE) {
            // Message
            const { authors } = this.props;
            const authorCount = this.props.authors.length;
            if (authors && authorCount > 0) {
                authorList = authors.map((user, index) => {
                    if (user) {
                        return (
                            <React.Fragment key={`meBoxAuthor-${index}`}>
                                <strong>{user.name}</strong>
                                {`${index < authorCount - 1 ? `, ` : ""}`}
                            </React.Fragment>
                        );
                    } else {
                        return <React.Fragment key={`meBoxAuthor-${index}`} />;
                    }
                });
            }
        }

        return (
            <li className={classNames("meBoxMessage", this.props.className, classesMeBoxMessage.root)}>
                <SmartLink to={to} className={classNames("meBoxMessage-link", classesMeBoxMessage.link)} tabIndex={0}>
                    <div className={classNames(classesMeBoxMessage.imageContainer, userPhotoClasses().root)}>
                        {photo ? (
                            <img className={classesMeBoxMessage.image} src={photo} alt={photoAlt} loading="lazy" />
                        ) : (
                            <NoUserPhotoIcon className={classesMeBoxMessage.image} photoAlt={photoAlt} />
                        )}
                    </div>
                    <div className={classNames("meBoxMessage-contents", classesMeBoxMessage.contents)}>
                        {!!authorList! && (
                            <div className={classNames("meBoxMessage-message", classesMeBoxMessage.message)}>
                                {authorList!}
                            </div>
                        )}
                        {messageHtml ? (
                            <div
                                className={classNames("meBoxMessage-message", classesMeBoxMessage.message)}
                                dangerouslySetInnerHTML={{ __html: message }}
                            />
                        ) : (
                            <div className={classNames("meBoxMessage-message", classesMeBoxMessage.message)}>
                                {message}
                            </div>
                        )}

                        <div className={classNames("meBoxMessage-metas", classesMetas.root, "isFlexed")}>
                            {timestamp && <DateTime timestamp={timestamp} className={classesMetas.meta} />}
                            {this.props.type === MeBoxItemType.MESSAGE && (
                                <span className={classesMetas.meta}>
                                    {this.props.countMessages === 1 && <Translate source="<0/> message" c0={1} />}
                                    {this.props.countMessages > 1 && (
                                        <Translate source="<0/> messages" c0={this.props.countMessages} />
                                    )}
                                </span>
                            )}
                        </div>
                    </div>

                    {unread ? (
                        <StatusLight
                            title={t("Unread")}
                            className={classNames(classesMeBoxMessage.status, "isUnread")}
                        />
                    ) : (
                        <FlexSpacer className={classesMeBoxMessage.status} />
                    )}
                </SmartLink>
            </li>
        );
    }
}
