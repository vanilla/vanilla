/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { t } from "../../../dom/appUtils";
import { messages } from "../../../icons/header";
import Count from "library/src/scripts/content/Count";
import classNames from "classnames";
import * as React from "react";

interface IProps {
    countClass?: string;
    open: boolean;
    className?: string;
}

/**
 * Implements Messages Drop down for header
 */
export default class MessagesCount extends React.PureComponent<IProps> {
    public state = {
        open: false,
    };

    public render() {
        const count = 0;
        return (
            <div className={classNames(this.props.className, "messagesToggle")}>
                {messages(this.props.open)}
                {count > 0 && (
                    <Count
                        className={classNames("vanillaHeader-count", this.props.countClass)}
                        label={t("Messages: ")}
                        count={count}
                    />
                )}
            </div>
        );
    }
}
