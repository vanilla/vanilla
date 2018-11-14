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

export interface IMeBoxMessage {
    unread?: boolean;
    userInfo?: IUserFragment;
    message: string;
    timestamp: string;
    warning?: boolean;
    to: string;
}

interface IProps extends IMeBoxMessage {
    className?: string;
}

/**
 * Implements Drop down message (for notifications or messages)
 */
export default class MeBoxMessage extends React.Component<IProps> {
    public render() {
        const { unread, userInfo, message, timestamp, warning, to } = this.props;
        const subject = warning ? t("You've") : userInfo!.name;
        return (
            <li className={classNames("MeBoxMessage", this.props.className)}>
                <SmartLink to={to} className="MeBoxMessage-link" tabIndex={0}>
                    <div className="MeBoxMessage-image">
                        {userInfo && (
                            <UserPhoto
                                size={UserPhotoSize.MEDIUM}
                                className="MeBoxMessage-photo"
                                userInfo={this.props.userInfo!}
                            />
                        )}
                        {!userInfo && userWarning(`MeBoxMessage-photo ${UserPhotoSize.MEDIUM} userPhoto`)}
                    </div>
                    <div className="MeBoxMessage-contents">
                        <div className="MeBoxMessage-message">
                            <Translate
                                source={message}
                                c0={<strong className="MeBoxMessage-subject">{subject}</strong>}
                            />
                        </div>
                        {timestamp && (
                            <div className="MeBoxMessage-metas metas">
                                <DateTime timestamp={timestamp} className="meta" />
                            </div>
                        )}
                    </div>
                    {unread && <div className="MeBoxMessage-status isRead" />}
                    {!unread && <FlexSpacer className="MeBoxMessage-status isUnread">{t("Unread")}</FlexSpacer>}
                </SmartLink>
            </li>
        );
    }
}
