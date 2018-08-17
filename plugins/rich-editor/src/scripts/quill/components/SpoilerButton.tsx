/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import { t } from "@dashboard/application";
import { spoiler } from "@rich-editor/components/icons";

export default class SpoilerButton extends React.Component<{}> {
    public render() {
        return (
            <div contentEditable={false} className="spoiler-buttonContainer">
                <button disabled className="iconButton button-spoiler" type="button">
                    <span className="spoiler-warning">
                        <span className="spoiler-warningMain">
                            {spoiler("spoiler-icon")}
                            <strong className="spoiler-warningBefore">{t("Warning")}</strong>
                            <span className="spoiler-warningAfter">{t("This is a spoiler")}</span>
                        </span>
                    </span>
                </button>
            </div>
        );
    }
}
