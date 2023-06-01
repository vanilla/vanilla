/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ELEMENT_SPOILER } from "@library/vanilla-editor/plugins/spoilerPlugin/createSpoilerPlugin";
import { getPluginType, InjectComponentProps, InjectComponentReturnType, Value } from "@udecode/plate-headless";
import { t } from "@vanilla/i18n";
import { Icon } from "@vanilla/icons";
import React from "react";

export const injectSpoilerComponent = <V extends Value = Value>(
    props: InjectComponentProps<V>,
): InjectComponentReturnType<V> => {
    const { element, editor } = props;
    const spoilerType = getPluginType(editor, ELEMENT_SPOILER);

    if (element.type === spoilerType) {
        // eslint-disable-next-line no-console
        console.log("injectSpoilerComponent", props);

        const SpoilerComponent = ({ children }) => (
            <div className="spoiler isShowingSpoiler">
                <div className="spoiler-buttonContainer">
                    <button className="button-spoiler" disabled>
                        <Icon icon="editor-eye-slash" />
                        {t("Spoiler Warning")}
                    </button>
                </div>
                <div className="spoiler-content">{children}</div>
            </div>
        );

        return SpoilerComponent;
    }
};
