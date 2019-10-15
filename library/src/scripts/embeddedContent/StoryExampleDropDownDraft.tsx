/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Omit } from "@library/@types/utils";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { DeviceProvider, Devices, useDevice } from "@library/layout/DeviceContext";
import { IStoryTileAndTextProps } from "@library/storybook/StoryTileAndText";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import React, { useRef } from "react";
import { draftPreviewClasses } from "@knowledge/modules/drafts/components/DraftPreviewStyles";

interface IProps extends Omit<IStoryTileAndTextProps, "children"> {}

export function StoryExampleDropDownDraft(props: IProps) {
    const classes = draftPreviewClasses();
    const device = useDevice();
    const toggleButtonRef = useRef(null);
    const doNothing = e => {
        e.preventDefault();
    };

    return (
        <DeviceProvider>
            <DropDown
                name={t("Draft Options")}
                buttonClassName={ButtonTypes.CUSTOM}
                renderLeft={true}
                buttonRef={toggleButtonRef}
                toggleButtonClassName={classes.toggle}
                className={classNames(classes.actions, "draftPreview-menu")}
                flyoutType={FlyoutType.LIST}
                openAsModal={device === Devices.MOBILE || device === Devices.XS}
            >
                <DropDownItemLink name={t("Edit")} to={"#"} className={classes.option} />
                <DropDownItemButton name={t("Delete")} onClick={doNothing} className="draftPreview-option" />
            </DropDown>
        </DeviceProvider>
    );
}
