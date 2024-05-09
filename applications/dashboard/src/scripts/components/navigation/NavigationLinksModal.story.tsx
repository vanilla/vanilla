/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useState } from "react";
import { StoryContent } from "@library/storybook/StoryContent";
import { storiesOf } from "@storybook/react";
import { NavigationLinksModal } from "@dashboard/components/navigation/NavigationLinksModal";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { INavigationVariableItem } from "@library/headers/navigationVariables";
import Button from "@library/forms/Button";
import { userContentClasses } from "@library/content/UserContent.styles";
import { ButtonTypes } from "@library/forms/buttonTypes";

const story = storiesOf("Theme UI", module);

const INITIAL_DATA: INavigationVariableItem[] = [
    {
        id: "buildin-discussions",
        name: "Discussions",
        url: "/discussions",
        children: [
            {
                id: "custom-will-be-guid-in-real-app",
                name: "Welcome Area",
                url: "/categories/1-welcome-area",
                children: [],
                isCustom: true,
            },
            {
                id: "custom-will-be-guid-in-real-app2",
                name: "Community MVPs",
                url: "/categories/2-mvps",
                children: [],
                isCustom: true,
            },
        ],
    },
    {
        id: "buildin-help",
        name: "Help",
        url: "~/help",
        children: [
            {
                id: "custom-will-be-guid-in-real-app3",
                name: "Product Documentation",
                url: "~/help/kb/product-docs",
                children: [],
                isCustom: true,
            },
            {
                id: "custom-will-be-guid-in-real-app4",
                name: "Release Notes",
                url: "~/help/kb/release-notes",
                children: [],
                isCustom: true,
            },
        ],
    },
];

function NavigationLinksModalWrapper() {
    const [data, setData] = useState(INITIAL_DATA);
    const [isOpen, setIsOpen] = useState(true);

    return (
        <StoryContent>
            <StoryHeading depth={1}>Navigation Links Modal</StoryHeading>
            <div className={userContentClasses().root}>
                <Button buttonType={ButtonTypes.PRIMARY} onClick={() => setIsOpen(true)}>
                    Open Modal
                </Button>
                <h2>Current Data</h2>
                <pre>
                    <code>{JSON.stringify(data, null, 4)}</code>
                </pre>
            </div>
            {isOpen && (
                <NavigationLinksModal
                    isNestingEnabled={true}
                    title={"Navigation Links"}
                    navigationItems={data}
                    onSave={(data) => {
                        setData(data);
                        setIsOpen(false);
                    }}
                    isVisible
                    onCancel={() => {
                        setIsOpen(false);
                    }}
                />
            )}
        </StoryContent>
    );
}

story.add("Navigation Links Modal", () => {
    return <NavigationLinksModalWrapper />;
});
