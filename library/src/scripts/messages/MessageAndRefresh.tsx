/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Message from "@library/messages/Message";
import { t } from "@library/utility/appUtils";
import React from "react";

interface IProps {
    className?: string;
    isFixed?: boolean;
}

/**
 * Message with refresh button
 */
export default function MessageAndRefresh(props: IProps) {
    const contents = t("The application has been updated. Refresh to get the latest version.");
    const refresh = () => {
        const currentUrl = window.location.href;
        window.location.href = currentUrl;
    };
    return (
        <Message
            confirmText={t("Refresh")}
            onConfirm={refresh}
            contents={contents}
            stringContents={contents}
            className={props.className}
            isFixed={props.isFixed}
        />
    );
}
