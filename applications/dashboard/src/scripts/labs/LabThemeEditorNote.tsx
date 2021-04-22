/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import Translate from "@library/content/Translate";
import SmartLink from "@library/routing/links/SmartLink";
import React from "react";

interface IProps {
    translatedLabName: string;
    docsUrl: string;
}

export function LabThemeEditorNote(props: IProps) {
    return (
        <Translate
            shortSource="The <0/> lab needs to be configured to match your custom theme."
            source="N.B. The <0/> lab needs to be configured to match your custom theme. This can be done using our new theme editor. <1>Find out more.</1>"
            c0={props.translatedLabName}
            c1={(text) => <SmartLink to={props.docsUrl}>{text}</SmartLink>}
        />
    );
}
