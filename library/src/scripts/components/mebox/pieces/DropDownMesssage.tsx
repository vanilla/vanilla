/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as React from "react";
import classNames from "classnames";
import { UserPhoto } from "@library/components/mebox/pieces/UserPhoto";
import { IUserFragment } from "@library/@types/api/users";
import { userWarning } from "@library/components/icons/header";
import FlexSpacer from "@library/components/FlexSpacer";
import { t } from "@library/application";
import Translate from "@library/components/translation/Translate";
import DateTime from "@library/components/DateTime";

export interface IDropDownMessage {
    unread?: boolean;
    userInfo?: IUserFragment;
    message: string;
    timestamp: string;
    warning?: boolean;
}

interface IProps extends IDropDownMessage {
    className?: string;
}

/**
 * Implements Drop down message (for notifications or messages)
 */
export default class DropDownMessage extends React.Component<IProps> {
    public render() {
        const { unread, userInfo, message, timestamp, warning } = this.props;
        const subject = warning ? t("You've") : userInfo!.name;
        return (
            <li className={classNames("dropDownMessage", this.props.className)}>
                {userInfo && <UserPhoto userInfo={this.props.userInfo!} />}
                {!userInfo && userWarning()}
                <div className="dropDownMessage-contents">
                    <Translate source={message} c0={subject} />
                    <DateTime timestamp={timestamp} />
                </div>
                {unread && <div className="dropDownMessage-status isRead" />}
                {!unread && <FlexSpacer className="dropDownMessage-status" />}
            </li>
        );
    }
}
