/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import * as React from "react";
import Button from "@library/forms/Button";
import { useState } from "react";
import { LayoutSectionID } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { LayoutThumbnailsModal } from "@dashboard/layout/editor/thumbnails/LayoutThumbnailsModal";

export default {
    title: "Dashboard/Appearance",
};

//real sections are fetched via useLayoutCatalog() hook, e.g. useLayoutCatalog("home").sections;
const fauxData = {
    "react.section.1-column": {
        schema: {},
        $reactComponent: "Component 1",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/sectionIcons/1column.svg").default,
        name: "1 column",
    },
    "react.section.2-columns": {
        schema: {},
        $reactComponent: "Component 2",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/sectionIcons/2columnu.svg").default,
        name: "2 columns",
    },
    "react.section.3-columns": {
        schema: {},
        $reactComponent: "Component 3",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/sectionIcons/3column.svg").default,
        name: "3 columns",
    },
    "react.section.full-width": {
        schema: {},
        $reactComponent: "Component for full width",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/sectionIcons/fullwidth.svg").default,
        name: "Full Width",
    },
};

//real sections are fetched via useLayoutCatalog() hook, e.g. useLayoutCatalog("home").widgets;
const widgetsFauxData = {
    "react.article.articles": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/articles.svg").default,
        schema: {},
        name: "Articles",
    },
    "react.banner.content": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/contentbanner.svg").default,
        schema: {},
        name: "Content Banner",
    },
    "react.banner.full": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/banner.svg").default,
        schema: {},
        name: "Banner",
    },
    "react.categories": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/categories.svg").default,
        schema: {},
        name: "Categories",
    },
    "react.discussion.announcements": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/announcements.svg").default,
        schema: {},
        name: "Announcements",
    },
    "react.discussion.discussions": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/discussions.svg").default,
        schema: {},
        name: "Discussions",
    },
    "react.discussion.ideas": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/ideas.svg").default,
        schema: {},
        name: "Ideas",
    },
    "react.discussion.questions": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/questions.svg").default,
        schema: {},
        name: "Questions",
    },
    "react.event.events": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/events.svg").default,
        schema: {},
        name: "Events",
    },
    "react.html": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/customhtml.svg").default,
        schema: {},
        name: "Custom HTML",
    },
    "react.leaderboard": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/leaderboard.svg").default,
        schema: {},
        name: "Leaderboard",
    },
    "react.online": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/whosonline.svg").default,
        schema: {},
        name: "Who's Online",
    },
    "react.quick-links": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/quicklinks.svg").default,
        schema: {},
        name: "Quick Links",
    },
    "react.rss": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/rssfeed.svg").default,
        schema: {},
        name: "RSS Feed",
    },
    "react.tag": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/tagcloud.svg").default,
        schema: {},
        name: "Tag Cloud",
    },
    "react.userspotlight": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/userspotlight.svg").default,
        schema: {},
        name: "User Spotlight",
    },
    "react.sitetotals": {
        $reactComponent: "",
        iconUrl: require("!file-loader!/applications/dashboard/design/images/widgetIcons/sitetotals.svg").default,
        schema: {},
        name: "Site Totals",
    },
};

function LayoutSectionsThumbnailsModalStory() {
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
            <LayoutThumbnailsModal
                sections={fauxData}
                title="Choose the type of Section"
                isVisible={modalIsVisible}
                exitHandler={closeModal}
                onAddSection={addSectionHandler}
                itemType="sections"
            />
        </>
    );
}

function LayoutWidgetsThumbnailsModalWithSearchStory() {
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
                Open Widgets Modal
            </Button>
            <div>
                <strong>Submitted Value:</strong> {savedSectionID ?? "None"}
            </div>
            <LayoutThumbnailsModal
                sections={widgetsFauxData}
                title="Choose your Widget"
                isVisible={modalIsVisible}
                exitHandler={closeModal}
                onAddSection={addSectionHandler}
                itemType="widgets"
                selectedSection="react.discussion.announcements"
            />
        </>
    );
}

export function LayoutSectionsThumbnailsModal() {
    return <LayoutSectionsThumbnailsModalStory />;
}

export function LayoutWidgetsThumbnailsModalWithSearch() {
    return <LayoutWidgetsThumbnailsModalWithSearchStory />;
}
