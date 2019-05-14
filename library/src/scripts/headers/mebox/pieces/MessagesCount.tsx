/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { MeBoxIcon } from "@library/headers/mebox/pieces/MeBoxIcon";
import { messages } from "@library/icons/header";
import { t } from "@library/utility/appUtils";
import React from "react";

interface IProps {
    open?: boolean;
    compact: boolean;
}

/**
 * Implements Messages Drop down for header
 */
export default function MessagesCount(props: IProps) {
    const { open, compact } = props;

    return (
        <MeBoxIcon count={0} countLabel={t("Messages") + ": "} compact={compact}>
            {messages(!!open)}
        </MeBoxIcon>
    );
}
