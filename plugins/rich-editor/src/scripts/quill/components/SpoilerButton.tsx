/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { t } from "@library/utility/appUtils";
import { SpoilerIcon } from "@library/icons/editorIcons";

export default class SpoilerButton extends React.Component<{}> {
    public render() {
        return (
            <div contentEditable={false} className="spoiler-buttonContainer">
                <button title={t("Toggle Spoiler")} disabled className="iconButton button-spoiler" type="button">
                    <span className="spoiler-warning">
                        <span className="spoiler-warningMain">
                            <SpoilerIcon className={"spoiler-icon"} />
                            <span className="spoiler-warningLabel">{t("Spoiler Warning")}</span>
                        </span>
                    </span>
                </button>
            </div>
        );
    }
}
