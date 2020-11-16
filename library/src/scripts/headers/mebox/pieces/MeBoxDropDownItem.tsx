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
import { metasClasses } from "@library/styles/metasStyles";
import Translate from "@library/content/Translate";
import DateTime from "@library/content/DateTime";
import classNames from "classnames";
import { NoUserPhotoIcon } from "@library/icons/titleBar";
import { userPhotoClasses } from "@library/headers/mebox/pieces/userPhotoStyles";

export enum MeBoxItemType {
    NOTIFICATION = "notification",
    MESSAGE = "message",
}

// Common to both notifications and messages dropdowns
export interface IMeBoxItem {
    className?: string;
    message: string;
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
        const { unread, message, timestamp, to, photoAlt, photo } = this.props;
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
                            <img className={classesMeBoxMessage.image} src={photo} alt={photoAlt} />
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
                        {/* Current notifications API returns HTML-formatted messages. Should be updated to return something aside from raw HTML. */}
                        <div
                            className={classNames("meBoxMessage-message", classesMeBoxMessage.message)}
                            dangerouslySetInnerHTML={{ __html: message }}
                        />
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
                    {!unread && (
                        <FlexSpacer
                            className={classNames("meBoxMessage-status", "isRead", classesMeBoxMessage.status)}
                        />
                    )}
                    {unread && (
                        <div
                            title={t("Unread")}
                            className={classNames(
                                "u-flexSpacer",
                                "meBoxMessage-status",
                                "isUnread",
                                classesMeBoxMessage.status,
                            )}
                        >
                            <span className="sr-only">{t("Unread")}</span>
                        </div>
                    )}
                </SmartLink>
            </li>
        );
    }
}
