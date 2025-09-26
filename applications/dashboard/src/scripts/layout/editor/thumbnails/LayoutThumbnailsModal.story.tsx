/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import * as React from "react";
import Button from "@library/forms/Button";
import { useState } from "react";
import { LayoutSectionID, type ILayoutCatalog } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { LayoutThumbnailsModal } from "@dashboard/layout/editor/thumbnails/LayoutThumbnailsModal";
import { EMPTY_SCHEMA } from "@vanilla/json-schema-forms";
import OneColumnIcon from "../../../../../design/images/sectionIcons/1column.svg";
import TwoColumnIcon from "../../../../../design/images/sectionIcons/2column.svg";
import ThreeColumnIcon from "../../../../../design/images/sectionIcons/3column.svg";
import FullWidthIcon from "../../../../../design/images/sectionIcons/fullwidth.svg";
import ArticlesIcon from "../../../../../design/images/widgetIcons/articles.svg";
import ContentBannerIcon from "../../../../../design/images/widgetIcons/contentbanner.svg";
import BannerIcon from "../../../../../design/images/widgetIcons/banner.svg";
import CategoriesIcon from "../../../../../design/images/widgetIcons/categories.svg";
import AnnouncementsIcon from "../../../../../design/images/widgetIcons/announcements.svg";
import DiscussionsIcon from "../../../../../design/images/widgetIcons/discussions.svg";
import IdeasIcon from "../../../../../design/images/widgetIcons/ideas.svg";
import QuestionsIcon from "../../../../../design/images/widgetIcons/questions.svg";
import EventsIcon from "../../../../../design/images/widgetIcons/events.svg";
import CustomHtmlIcon from "../../../../../design/images/widgetIcons/customhtml.svg";
import LeaderboardIcon from "../../../../../design/images/widgetIcons/leaderboard.svg";
import WhosOnlineIcon from "../../../../../design/images/widgetIcons/whosonline.svg";
import QuickLinksIcon from "../../../../../design/images/widgetIcons/quicklinks.svg";
import RSSFeedIcon from "../../../../../design/images/widgetIcons/rssfeed.svg";
import TagCloudIcon from "../../../../../design/images/widgetIcons/tagcloud.svg";
import UserSpotlightIcon from "../../../../../design/images/widgetIcons/userspotlight.svg";
import SiteTotalsIcon from "../../../../../design/images/widgetIcons/sitetotals.svg";

export default {
    title: "Dashboard/Appearance",
};

//real sections are fetched via useLayoutCatalog() hook, e.g. useLayoutCatalog("home").sections;
const fauxData: ILayoutCatalog["assets"] = {
    "react.section.1-column": {
        schema: EMPTY_SCHEMA,
        $reactComponent: "Component 1",
        iconUrl: OneColumnIcon,
        name: "1 column",
        widgetGroup: "Sections",
    },
    "react.section.2-columns": {
        schema: EMPTY_SCHEMA,
        $reactComponent: "Component 2",
        iconUrl: TwoColumnIcon,
        name: "2 columns",
        widgetGroup: "Sections",
    },
    "react.section.3-columns": {
        schema: EMPTY_SCHEMA,
        $reactComponent: "Component 3",
        iconUrl: ThreeColumnIcon,
        name: "3 columns",
        widgetGroup: "Sections",
    },
    "react.section.full-width": {
        schema: EMPTY_SCHEMA,
        $reactComponent: "Component for full width",
        iconUrl: FullWidthIcon,
        name: "Full Width",
        widgetGroup: "Sections",
    },
};

//real sections are fetched via useLayoutCatalog() hook, e.g. useLayoutCatalog("home").widgets;
const widgetsFauxData: ILayoutCatalog["widgets"] = {
    "react.article.articles": {
        $reactComponent: "",
        iconUrl: ArticlesIcon,
        schema: EMPTY_SCHEMA,
        name: "Articles",
        widgetGroup: "Knowledge Base",
    },
    "react.banner.content": {
        $reactComponent: "",
        iconUrl: BannerIcon,
        schema: EMPTY_SCHEMA,
        name: "Content Banner",
        widgetGroup: "Knowledge Base",
    },
    "react.banner.full": {
        $reactComponent: "",
        iconUrl: BannerIcon,
        schema: EMPTY_SCHEMA,
        name: "Banner",
        widgetGroup: "Widgets",
    },
    "react.categories": {
        $reactComponent: "",
        iconUrl: CategoriesIcon,
        schema: EMPTY_SCHEMA,
        name: "Categories",
        widgetGroup: "Community",
    },
    "react.discussion.announcements": {
        $reactComponent: "",
        iconUrl: AnnouncementsIcon,
        schema: EMPTY_SCHEMA,
        name: "Announcements",
        widgetGroup: "Community",
    },
    "react.discussion.discussions": {
        $reactComponent: "",
        iconUrl: DiscussionsIcon,
        schema: EMPTY_SCHEMA,
        name: "Discussions",
        widgetGroup: "Community",
    },
    "react.discussion.ideas": {
        $reactComponent: "",
        iconUrl: IdeasIcon,
        schema: EMPTY_SCHEMA,
        name: "Ideas",
        widgetGroup: "Community",
    },
    "react.discussion.questions": {
        $reactComponent: "",
        iconUrl: QuestionsIcon,
        schema: EMPTY_SCHEMA,
        name: "Questions",
        widgetGroup: "Community",
    },
    "react.event.events": {
        $reactComponent: "",
        iconUrl: EventsIcon,
        schema: EMPTY_SCHEMA,
        name: "Events",
        widgetGroup: "Community",
    },
    "react.html": {
        $reactComponent: "",
        iconUrl: CustomHtmlIcon,
        schema: EMPTY_SCHEMA,
        name: "Custom HTML",
        widgetGroup: "Custom",
    },
    "react.leaderboard": {
        $reactComponent: "",
        iconUrl: LeaderboardIcon,
        schema: EMPTY_SCHEMA,
        name: "Leaderboard",
        widgetGroup: "Widgets",
    },
    "react.online": {
        $reactComponent: "",
        iconUrl: WhosOnlineIcon,
        schema: EMPTY_SCHEMA,
        name: "Who's Online",
        widgetGroup: "Widgets",
    },
    "react.quick-links": {
        $reactComponent: "",
        iconUrl: QuickLinksIcon,
        schema: EMPTY_SCHEMA,
        name: "Quick Links",
        widgetGroup: "Widgets",
    },
    "react.rss": {
        $reactComponent: "",
        iconUrl: RSSFeedIcon,
        schema: EMPTY_SCHEMA,
        name: "RSS Feed",
        widgetGroup: "Widgets",
    },
    "react.tag": {
        $reactComponent: "",
        iconUrl: TagCloudIcon,
        schema: EMPTY_SCHEMA,
        name: "Tag Cloud",
        widgetGroup: "Community",
    },
    "react.userspotlight": {
        $reactComponent: "",
        iconUrl: UserSpotlightIcon,
        schema: EMPTY_SCHEMA,
        name: "User Spotlight",
        widgetGroup: "Widgets",
    },
    "react.sitetotals": {
        $reactComponent: "",
        iconUrl: SiteTotalsIcon,
        schema: EMPTY_SCHEMA,
        name: "Site Totals",
        widgetGroup: "Widgets",
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
