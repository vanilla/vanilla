/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { DashboardMediaItem } from "@dashboard/tables/DashboardMediaItem";
import { DashboardTable } from "@dashboard/tables/DashboardTable";
import { TableColumnSize } from "@dashboard/tables/DashboardTableHeadItem";
import { DashboardTableOptions } from "@dashboard/tables/DashboardTableOptions";
import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonStyles";
import { DeleteIcon, EditIcon } from "@library/icons/common";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { storiesOf } from "@storybook/react";
import React from "react";
const { HeadItem } = DashboardTable;
const puppyImage = require("../../../styleguide/public/resources/images/smart-puppy.jpg");
const chickImage = require("../../../styleguide/public/resources/images/little-chick.jpg");

const formsStory = storiesOf("Dashboard", module).addDecorator(dashboardCssDecorator);

formsStory.add("Tables", () =>
    (() => {
        const OptionsCell = () => {
            return (
                <td>
                    <DashboardTableOptions>
                        <Button baseClass={ButtonTypes.ICON_COMPACT}>
                            <EditIcon />
                        </Button>
                        <Button baseClass={ButtonTypes.ICON_COMPACT}>
                            <DeleteIcon />
                        </Button>
                    </DashboardTableOptions>
                </td>
            );
        };
        return (
            <StoryContent>
                <StoryHeading depth={1}>Dashboard Tables</StoryHeading>
                <DashboardTable
                    head={
                        <tr>
                            <HeadItem size={TableColumnSize.LG}>Username</HeadItem>
                            <HeadItem>Roles</HeadItem>
                            <HeadItem size={TableColumnSize.MD}>First Visit</HeadItem>
                            <HeadItem size={TableColumnSize.MD}>Last Visit</HeadItem>
                            <HeadItem>Last IP</HeadItem>
                            <HeadItem>Options</HeadItem>
                        </tr>
                    }
                    body={
                        <>
                            <tr>
                                <td>
                                    <DashboardMediaItem
                                        imgSrc={puppyImage}
                                        title="Smart Puppy"
                                        info="puppy@email.com"
                                    />
                                </td>
                                <td>Member, Moderater</td>
                                <td>April 2</td>
                                <td>April 26</td>
                                <td>10.0.10.1</td>
                                <OptionsCell />
                            </tr>
                            <tr>
                                <td>
                                    <DashboardMediaItem
                                        imgSrc={chickImage}
                                        title="Little Chick"
                                        info="chick@email.com"
                                    />
                                </td>
                                <td>Member</td>
                                <td>April 2</td>
                                <td>April 26</td>
                                <td>10.0.10.1</td>
                                <OptionsCell />
                            </tr>
                        </>
                    }
                />
            </StoryContent>
        );
    })(),
);
