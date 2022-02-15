/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";

interface IProps {
    children: React.ReactNode;
}

export function SectionFullWidth(props: IProps) {
    return <>{props.children}</>;
}
