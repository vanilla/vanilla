/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ColorVar } from "@library/styles/CssVar";
import { ToolTip } from "@library/toolTip/ToolTip";
import { t } from "@vanilla/i18n";

export type ChangeType = "added" | "modified" | "removed" | "error";

export function ChangeStatusIndicator(props: { className?: string; changeType: ChangeType; isSelected?: boolean }) {
    const { changeType, isSelected } = props;
    const color = (() => {
        if (isSelected) {
            return ColorsUtils.var(ColorVar.PrimaryContrast);
        }
        switch (changeType) {
            case "added":
                return ColorsUtils.var(ColorVar.Green);
            case "modified":
                return ColorsUtils.var(ColorVar.Yellow);
            case "removed":
                return ColorsUtils.var(ColorVar.Red);
        }
    })();

    const content = (() => {
        switch (changeType) {
            case "added":
                return <span>+</span>;
            case "modified":
                return <span style={{ fontWeight: 400 }}>•</span>;
            case "removed":
                return <span style={{ position: "relative", top: -1 }}>-</span>;
        }
    })();

    const title = (() => {
        switch (changeType) {
            case "added":
                return t("File Added");
            case "modified":
                return t("File Modified");
            case "removed":
                return t("File Removed");
        }
    })();

    return (
        <ToolTip label={title}>
            <span
                title={title}
                className={props.className}
                style={{
                    display: "inline-flex",
                    alignItems: "center",
                    justifyContent: "center",
                    width: 16,
                    height: 16,
                    border: `2px solid ${color}`,
                    color,
                    borderRadius: 4,
                    lineHeight: "16px",
                    fontWeight: "bold",
                    fontSize: 16,
                }}
            >
                {content}
            </span>
        </ToolTip>
    );
}
