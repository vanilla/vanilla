/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardFormGroup } from "@dashboard/forms/DashboardFormGroup";
import { DashboardLabelType } from "@dashboard/forms/DashboardFormLabel";
import { DashboardInput } from "@dashboard/forms/DashboardInput";
import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React, { useState } from "react";
import { DashboardSelect } from "@dashboard/forms/DashboardSelect";
import { IComboBoxOption } from "@library/features/search/SearchBar";
import Translator from "@library/content/translationGrid/TranslationButton";

const formsStory = storiesOf("Dashboard/Forms", module).addDecorator(dashboardCssDecorator);

formsStory.add("FormGroup", () =>
    (() => {
        const [dropdownValue, setDropdownValue] = useState<IComboBoxOption | null>(null);

        return (
            <StoryContent>
                <Translator
                    translationData={{
                        id: 123,
                        resource: "kb",
                        recordType: "category",
                    }}
                />
            </StoryContent>
        );
    })(),
);
