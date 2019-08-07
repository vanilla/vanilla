/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { useEffect, useRef, useState } from "react";
import { IStoryTileAndTextProps } from "@library/storybook/StoryTileAndText";
import Button from "@library/forms/Button";
import classNames from "classnames";
import { useUniqueID } from "@library/utility/idUtils";
import ModalConfirm from "@library/modal/ModalConfirm";
import { storyBookClasses } from "@library/storybook/StoryBookStyles";
import { StoryTile } from "@library/storybook/StoryTile";
import { Omit } from "@library/@types/utils";
import { DeviceProvider, Devices, useDevice } from "@library/layout/DeviceContext";
import DropDown from "@library/flyouts/DropDown";
import InsertUpdateMetas from "@library/result/InsertUpdateMetas";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { t } from "@library/utility/appUtils";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import MeBoxDropDownItemList from "@library/headers/mebox/pieces/MeBoxDropDownItemList";
import { MeBoxItemType } from "@library/headers/mebox/pieces/MeBoxDropDownItem";
import { ButtonTypes } from "@library/forms/buttonStyles";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";

interface IProps extends Omit<IStoryTileAndTextProps, "children"> {}

export function StoryExampleDropDownDraft(props: IProps) {
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
                toggleButtonClassName="draftPreview-actionsToggle"
                className={classNames("draftPreview-actions", "draftPreview-menu")}
                paddedList={true}
                openAsModal={device === Devices.MOBILE || device === Devices.XS}
            >
                <DropDownItemLink name={t("Edit")} to={"#"} className="draftPreview-option" />
                <DropDownItemButton name={t("Delete")} onClick={doNothing} className="draftPreview-option" />
            </DropDown>
        </DeviceProvider>
    );
}
