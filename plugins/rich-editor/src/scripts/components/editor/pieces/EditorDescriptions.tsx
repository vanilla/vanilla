/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { t } from "@library/application";

interface IProps {
    id: string;
}

export default function EditorDescriptions(props: IProps) {
    return (
        <p id={props.id} className="sr-only">
            {t("richEditor.description.title")}
            {t("richEditor.description.paragraphMenu")}
            {t("richEditor.description.inlineMenu")}
            {t("richEditor.description.embed")}
        </p>
    );
}
