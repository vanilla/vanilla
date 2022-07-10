/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Message from "@library/messages/Message";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import React from "react";

interface IProps extends Omit<React.ComponentProps<typeof Message>, "icon" | "title" | "contents" | "stringContents"> {}

export const FormatConversionNotice = React.forwardRef(function FormatConversionNotice(
    props: IProps,
    ref: React.RefObject<HTMLDivElement>,
) {
    const conversionTitle = t("This text has been converted from another format.");
    const conversionMessage = t(
        "As a result you may lose some of your original content and will not be able to revert your changes. Do you wish to continue?",
    );

    return (
        <Message
            {...props}
            ref={ref}
            title={conversionTitle}
            icon={<Icon icon={"status-warning"} size={"compact"} />}
            contents={conversionMessage}
            stringContents={conversionMessage}
        />
    );
});
