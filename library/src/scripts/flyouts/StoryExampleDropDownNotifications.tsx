/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { IStoryTileAndTextProps } from "@library/storybook/StoryTileAndText";
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
}

export function StoryExampleDropDown(props: IProps) {
    return (
        <DropDown name={t("Article Options")} flyoutType={props.flyoutType}>
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
                            photoAlt: "",
                            recordID: 1,
                            timestamp: "2019-03-06 21:21:18",
                            to: "#",
                            unread: true,
                        },
                        {
                            type: MeBoxItemType.NOTIFICATION,
                            message: "Sample message",
                            photo: null,
                            photoAlt: "",
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
