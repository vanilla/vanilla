/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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

export enum MeBoxItemType {
    NOTIFICATION = "notification",
    MESSAGE = "message",
}

// Common to both notifications and messages dropdowns
interface IMeBoxItem {
    className?: string;
    message: string;
    photo?: string;
    recordID: number;
    timestamp: string;
    to: string;
    unread?: boolean;
}

export interface IMeBoxMessageItem extends IMeBoxItem {
    authors: IUserFragment[];
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

        let count: number;
        let subject: string;
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
            <li className={classNames("meBoxMessage", this.props.className)}>
                <SmartLink to={to} className="meBoxMessage-link" tabIndex={0}>
                    <div className="meBoxMessage-image">{noUserPhoto()}</div>
                    <div className="meBoxMessage-contents">
                        {!!authors! && <div className="meBoxMessage-message">{authors!}</div>}
                        <div className="meBoxMessage-message">
                            <Translate
                                source={message}
                                c0={<strong className="meBoxMessage-subject">{subject!}</strong>}
                            />
                        </div>
                        {(timestamp || !!count!) && (
                            <div className="meBoxMessage-metas metas isFlexed">
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
