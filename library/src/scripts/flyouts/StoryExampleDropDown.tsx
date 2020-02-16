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
import { DeviceProvider, Devices, useDevice } from "@library/layout/DeviceContext";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import InsertUpdateMetas from "@library/result/InsertUpdateMetas";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";
import DropDownItemLink from "@library/flyouts/items/DropDownItemLink";
import { t } from "@library/utility/appUtils";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import MeBoxDropDownItemList from "@library/headers/mebox/pieces/MeBoxDropDownItemList";
import { MeBoxItemType } from "@library/headers/mebox/pieces/MeBoxDropDownItem";

interface IProps extends Omit<IStoryTileAndTextProps, "children"> {
    flyoutType: FlyoutType;
    defaultsOpen?: boolean;
}

export function StoryExampleDropDown(props: IProps) {
    const [isVisible, setVisible] = useState(!!props.defaultsOpen);

    return (
        <DropDown
            name={t("Article Options")}
            flyoutType={props.flyoutType}
            isVisible={isVisible}
            onVisibilityChange={setVisible}
        >
            <InsertUpdateMetas
                dateInserted={"2019-03-06 21:21:18"}
                dateUpdated={"2019-03-06 21:21:18"}
                insertUser={{
                    userID: 1,
                    name: "test",
                    photoUrl: "test",
                    dateLastActive: null,
                }}
                updateUser={{
                    userID: 1,
                    name: "test",
                    photoUrl: "test",
                    dateLastActive: null,
                }}
            />
            <DropDownItemSeparator />
            <DropDownItemLink name={t("Link 1")} to={"#"} />
            <DropDownItemLink name={t("Link 2")} to={"#"} />
            <DropDownItemLink name={t("Link 3")} to={"#"} />
            <DropDownSection title={"Section Title"}>
                <MeBoxDropDownItemList
                    emptyMessage={t("You do not have any notifications yet.")}
                    className="headerDropDown-notifications"
                    type={MeBoxItemType.NOTIFICATION}
                    data={[
                        {
                            type: MeBoxItemType.NOTIFICATION,
                            message: "Sample message",
                            photo: null,
                            recordID: 1,
                            timestamp: "2019-03-06 21:21:18",
                            to: "#",
                            unread: true,
                        },
                        {
                            type: MeBoxItemType.NOTIFICATION,
                            message: "Sample message",
                            photo: null,
                            recordID: 1,
                            timestamp: "2019-03-06 21:21:18",
                            to: "#",
                            unread: false,
                        },
                    ]}
                />
            </DropDownSection>
        </DropDown>
    );
}
