/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ManageIconModal } from "@dashboard/appearance/manageIcons/ManageIconModal";
import type { ManageIconsApi } from "@dashboard/appearance/manageIcons/ManageIconsApi";
import { useManageIconsForm } from "@dashboard/appearance/manageIcons/ManageIconsFormContext";
import { ManagedIcon } from "@dashboard/appearance/manageIcons/ManagedIcon";
import { EmptyState } from "@dashboard/moderation/components/EmptyState";
import { css } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { t } from "@vanilla/i18n";
import { useState } from "react";

interface IProps {
    activeIcons: ManageIconsApi.IManagedIcon[];
}

export function ManageIconsTable(props: IProps) {
    const { activeIcons } = props;
    const { iconFilter, iconType } = useManageIconsForm();
    const [activeIconName, setActiveIconName] = useState<string | null>(null);
    const activeIcon = activeIcons.find((icon) => icon.iconName === activeIconName) ?? null;

    const filteredIcons = activeIcons.filter((icon) => {
        if (iconType === "custom" && !icon.isCustom) {
            return false;
        }

        if (iconType === "system" && icon.isCustom) {
            return false;
        }

        if (!iconFilter) {
            return true;
        }

        return icon.iconName.toLowerCase().includes(iconFilter.toLowerCase());
    });

    return (
        <div className={classes.root}>
            {filteredIcons.length === 0 && <EmptyState subtext={t("No icons match those filters.")} />}

            <div className={classes.grid}>
                {filteredIcons.map((icon) => (
                    <TableItem
                        onClick={() => {
                            setActiveIconName(icon.iconName);
                        }}
                        key={icon.iconName}
                        managedIcon={icon}
                    />
                ))}
            </div>
            <ManageIconModal activeIcon={activeIcon} onClose={() => setActiveIconName(null)} />
        </div>
    );
}

function TableItem(props: { managedIcon: ManageIconsApi.IManagedIcon; onClick(): void }) {
    const { iconColor, iconSize } = useManageIconsForm();

    const { managedIcon } = props;
    let content = (
        <Button
            buttonType={ButtonTypes.CUSTOM}
            onClick={props.onClick}
            className={classes.itemRoot}
            style={{ color: iconColor }}
        >
            <ManagedIcon iconSize={iconSize} managedIcon={managedIcon} withGrid={true} />
            {<div className={classes.itemName}>{managedIcon.iconName}</div>}
        </Button>
    );

    return content;
}

const classes = {
    root: css({
        padding: 16,
    }),
    itemRoot: css({
        border: singleBorder({ radius: 16 }),
        padding: 16,
        display: "flex",
        flexDirection: "column",
        gap: 8,
        alignItems: "center",
        justifyContent: "center",
        borderRadius: 6,
        "&:hover, &:active, &:focus-visible": {
            borderColor: ColorsUtils.colorOut(globalVariables().mainColors.primary),
            background: ColorsUtils.colorOut(globalVariables().mixPrimaryAndBg(0.02)),
        },
    }),
    itemName: css({
        fontFamily: globalVariables().fonts.families.monospace,
        fontSize: 12,
    }),
    grid: css({
        display: "grid",
        gridTemplateColumns: "repeat(auto-fill, minmax(180px, 1fr))",
        gap: 16,
    }),
};
