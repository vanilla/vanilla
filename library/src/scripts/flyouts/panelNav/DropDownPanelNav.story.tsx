/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";

import { STORY_SITE_NAV_ACTIVE_RECORD, STORY_SITE_NAV_ITEMS } from "@library/navigation/siteNav.storyData";
import { StoryContent } from "@library/storybook/StoryContent";
import DropDown, { FlyoutType } from "@library/flyouts/DropDown";
import { SettingsIcon } from "@library/icons/titleBar";
import Modal from "@library/modal/Modal";
import ModalSizes from "@library/modal/ModalSizes";
import { DropDownPanelNav } from "@library/flyouts/panelNav/DropDownPanelNav";
import SiteNavProvider, { SiteNavContext } from "@library/navigation/SiteNavContext";
import DropDownSection from "@library/flyouts/items/DropDownSection";
import DropDownItemButton from "@library/flyouts/items/DropDownItemButton";
import DropDownItemSeparator from "@library/flyouts/items/DropDownItemSeparator";

export default {
    title: "Navigation/PanelNav",
    parameters: {
        chromatic: {
            // viewports: [1400, 400],
        },
    },
};

function Story() {
    return (
        <SiteNavProvider categoryRecordType="knowledgeCategory">
            <Modal scrollable isVisible={true} size={ModalSizes.MODAL_AS_SIDE_PANEL_LEFT}>
                <DropDownPanelNav
                    activeRecord={STORY_SITE_NAV_ACTIVE_RECORD}
                    isNestable={true}
                    title="Navigation First Root"
                    navItems={STORY_SITE_NAV_ITEMS}
                    afterNavSections={
                        <>
                            <DropDownItemSeparator />
                            <DropDownItemButton onClick={() => {}}>Hello world 1</DropDownItemButton>
                            <DropDownItemButton onClick={() => {}}>Hello world 2</DropDownItemButton>
                        </>
                    }
                />
                <DropDownPanelNav
                    activeRecord={STORY_SITE_NAV_ACTIVE_RECORD}
                    isNestable={true}
                    title="Navigation Second Root"
                    navItems={STORY_SITE_NAV_ITEMS}
                    afterNavSections={
                        <>
                            <DropDownItemSeparator />
                            <DropDownItemButton onClick={() => {}}>Hello world 1</DropDownItemButton>
                            <DropDownItemButton onClick={() => {}}>Hello world 2</DropDownItemButton>
                        </>
                    }
                />
            </Modal>
        </SiteNavProvider>
    );
}

export function PanelNavigation() {
    return <Story />;
}
