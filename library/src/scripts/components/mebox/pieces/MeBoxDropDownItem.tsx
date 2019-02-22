/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { IUserFragment } from "@library/@types/api/users";
import { noUserPhoto } from "@library/components/icons/header";
import FlexSpacer from "@library/components/FlexSpacer";
import { t } from "@library/application";
import Translate from "@library/components/translation/Translate";
import DateTime from "@library/components/DateTime";
import SmartLink from "@library/components/navigation/SmartLink";
import { meBoxMessageClasses } from "@library/styles/meBoxMessageStyles";

export enum MeBoxItemType {
    NOTIFICATION = "notification",
    MESSAGE = "message",
}

// Common to both notifications and messages dropdowns
export interface IMeBoxItem {
    className?: string;
    message: string;
    photo: string | null;
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
        const { unread, message, timestamp, to } = this.props;
        const classesMeBoxMessage = meBoxMessageClasses();

        let authors: JSX.Element[];

        if (this.props.type === MeBoxItemType.MESSAGE) {
            // Message
            const authorCount = this.props.authors.length;
            if ("authors" in this.props && authorCount > 0) {
                authors = this.props.authors!.map((user, index) => {
                    return (
                        <React.Fragment key={`meBoxAuthor-${index}`}>
                            <strong>{user.name}</strong>
                            {`${index < authorCount - 1 ? `, ` : ""}`}
                        </React.Fragment>
                    );
                });
            }
        }

        return (
            <li className={classNames("meBoxMessage", this.props.className, classesMeBoxMessage.root)}>
                <SmartLink to={to} className={classNames("meBoxMessage-link", classesMeBoxMessage.link)} tabIndex={0}>
                    <div className={classNames("meBoxMessage-imageContainer", classesMeBoxMessage.imageContainer)}>
                        {this.props.photo ? (
                            <img
                                className={classNames("meBoxMessage-image", classesMeBoxMessage.image)}
                                src={this.props.photo}
                            />
                        ) : (
                            noUserPhoto(classNames("meBoxMessage-image", classesMeBoxMessage.image))
                        )}
                    </div>
                    <div className={classNames("meBoxMessage-contents", classesMeBoxMessage.contents)}>
                        {!!authors! && (
                            <div className={classNames("meBoxMessage-message", classesMeBoxMessage.message)}>
                                {authors!}
                            </div>
                        )}
                        {/* Current notifications API returns HTML-formatted messages. Should be updated to return something aside from raw HTML. */}
                        <div
                            className={classNames("meBoxMessage-message", classesMeBoxMessage.message)}
                            dangerouslySetInnerHTML={{ __html: message }}
                        />
                        <div className={classNames("meBoxMessage-metas", "metas", "isFlexed")}>
                            {timestamp && <DateTime timestamp={timestamp} className="meta" />}
                            {this.props.type === MeBoxItemType.MESSAGE && (
                                <span className="meta">
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
