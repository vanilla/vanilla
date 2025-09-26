/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { fragmentEditorClasses } from "@dashboard/appearance/fragmentEditor/FragmentEditor.classes";
import { cx } from "@emotion/css";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ToolTip, ToolTipIcon } from "@library/toolTip/ToolTip";
import { Icon } from "@vanilla/icons";

interface IProps {
    className?: string;
    error: React.ReactNode;
}

export function ErrorIndicator(props: IProps) {
    const classes = fragmentEditorClasses.useAsHook();
    return (
        <ToolTip noPadding customWidth={500} label={props.error}>
            <ToolTipIcon className={cx(classes.errorIndicator, props.className)}>
                <Icon icon={"status-alert"} />
            </ToolTipIcon>
        </ToolTip>
    );
}
