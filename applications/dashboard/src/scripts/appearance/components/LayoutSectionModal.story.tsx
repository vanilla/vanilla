/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as React from "react";

import Button from "@library/forms/Button";
import { LayoutSectionsModal } from "@dashboard/appearance/components/LayoutSectionsModal";
import { useState } from "react";
import { LayoutSectionID } from "@dashboard/layout/layoutSettings/LayoutSettings.types";

export default {
    title: "Dashboard/Appearance",
};

//real sections are fetched via useLayoutCatalog() hook, e.g. useLayoutCatalog("home").sections;
const fauxData = {
    "react.section.1-column": {
        schema: {},
        $reactComponent: "Component 1",
        recommendedWidgets: [{ widgetID: "test.widget1", widgetName: "TestWidget1" }],
    },
    "react.section.2-columns": {
        schema: {},
        $reactComponent: "Component 2",
        recommendedWidgets: [{ widgetID: "test.widget2", widgetName: "TestWidget2" }],
    },
    "react.section.3-columns": {
        schema: {},
        $reactComponent: "Component 3",
        recommendedWidgets: [{ widgetID: "test.widget3", widgetName: "TestWidget3" }],
    },
    "react.section.full-width": {
        schema: {},
        $reactComponent: "Component for full width",
        recommendedWidgets: [{ widgetID: "full.width.widget", widgetName: "TestFullWidthWidget" }],
    },
};

export function LayoutSectionThumbnailsModal() {
    const [modalIsVisible, setModalIsVisible] = useState(true);
    const openModal = () => {
        setModalIsVisible(true);
    };
    const closeModal = () => setModalIsVisible(false);
    const [savedSectionID, setSavedSectionID] = useState<LayoutSectionID | undefined>(undefined);

    const addSectionHandler = (sectionID: LayoutSectionID) => {
        setSavedSectionID(sectionID);
        //add section code here
        closeModal();
    };
    return (
        <>
            <Button
                onClick={() => {
                    openModal();
                }}
            >
                Open Layout Sections Modal
            </Button>
            <div>
                <strong>Submitted Value:</strong> {savedSectionID ?? "None"}
            </div>
            <LayoutSectionsModal
                sections={fauxData}
                title="Choose the type of Section"
                isVisible={modalIsVisible}
                exitHandler={closeModal}
                onAddSection={addSectionHandler}
            />
        </>
    );
}
