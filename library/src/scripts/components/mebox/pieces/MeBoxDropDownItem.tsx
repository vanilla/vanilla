/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { UserPhoto, UserPhotoSize } from "@library/components/mebox/pieces/UserPhoto";
import { IUserFragment } from "@library/@types/api/users";
import { userWarning } from "@library/components/icons/header";
import FlexSpacer from "@library/components/FlexSpacer";
import { t } from "@library/application";
import Translate from "@library/components/translation/Translate";
import DateTime from "@library/components/DateTime";
import SmartLink from "@library/components/navigation/SmartLink";

export enum MeBoxItemType {
    NOTIFICATION = "notification",
    MESSAGE = "message",
}

// Common to both notifications and messages dropdowns
interface IMeBoxItem {
    unread?: boolean;
    timestamp: string;
    to: string;
    className?: string;
    message: string;
}

export interface IMeBoxMessageItem extends IMeBoxItem {
    authors: IUserFragment[];
    featuredUser: IUserFragment;
    count: number;
    type: MeBoxItemType.MESSAGE;
}

export interface IMeBoxNotificationItem extends IMeBoxItem {
    featuredUser: IUserFragment; // Whom is the message about?
    warning?: boolean;
    type: MeBoxItemType.NOTIFICATION;
}

type IProps = IMeBoxMessageItem | IMeBoxNotificationItem;

/**
 * Implements Drop down message (for notifications or messages)
 */
export default class MeBoxDropDownItem extends React.Component<IProps> {
    public render() {
        const { unread, message, timestamp, to, featuredUser } = this.props;

        let warning: boolean;
        let count: number;
        let subject: string;
        let authors: JSX.Element[];

        if (this.props.type === MeBoxItemType.NOTIFICATION) {
            // Notification
            warning = !!this.props.warning;
            subject = warning ? t("You've") : this.props.featuredUser.name;
        } else {
            // Message
            warning = false;
            count = this.props.count;
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

        const image = warning ? (
            userWarning(`meBoxMessage-photo ${UserPhotoSize.MEDIUM} userPhoto`)
        ) : (
            <UserPhoto size={UserPhotoSize.MEDIUM} className="meBoxMessage-photo" userInfo={featuredUser!} />
        );

        return (
            <li className={classNames("meBoxMessage", this.props.className)}>
                <SmartLink to={to} className="meBoxMessage-link" tabIndex={0}>
                    <div className="meBoxMessage-image">{image}</div>
                    <div className="meBoxMessage-contents">
                        {!!authors! && <div className="meBoxMessage-message">{authors!}</div>}
                        <div className="meBoxMessage-message">
                            <Translate
                                source={message}
                                c0={<strong className="meBoxMessage-subject">{subject!}</strong>}
                            />
                        </div>
                        {(timestamp || !!count!) && (
                            <div className="meBoxMessage-metas metas">
                                <DateTime timestamp={timestamp} className="meta" />
                                {!!count! && (
                                    <span className="meta">
                                        {count! === 1 && <Translate source="<0/> message" c0={1} />}
                                        {count! > 1 && <Translate source="<0/> messages" c0={count!} />}
                                    </span>
                                )}
                            </div>
                        )}
                    </div>
                    {!unread && <FlexSpacer className="meBoxMessage-status isRead" />}
                    {unread && (
                        <div title={t("Unread")} className="u-flexSpacer meBoxMessage-status isUnread">
                            <span className="sr-only">{t("Unread")}</span>
                        </div>
                    )}
                </SmartLink>
            </li>
        );
    }
}
