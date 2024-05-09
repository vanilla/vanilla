import { dashboardCssDecorator } from "@dashboard/__tests__/dashboardCssDecorator";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import React from "react";
import { IApiError, ILoadable, LoadStatus } from "@library/@types/api/core";
import { PageLoadStatus } from "@library/loaders/PageLoadStatus";
import { IAPIErrorFragment } from "@library/errorPages/CoreErrorMessages";

export default {
    title: "Loaders/LoadingStatus",
    decorators: [dashboardCssDecorator],
};

const data: ILoadable<any, IAPIErrorFragment> = {
    status: LoadStatus.PENDING,
    data: [],
    error: {
        response: {
            status: 404,
        },
    },
};

const story = (status: LoadStatus) =>
    function Story() {
        return (
            <StoryContent>
                <StoryHeading depth={1}>Loading Status ({status})</StoryHeading>
                <StoryParagraph>
                    The <code>LoadingStatus</code> component takes an <code>ILoadable</code> and will render a loader,
                    error, or the component children if the data loaded successfully.
                </StoryParagraph>
                <div>
                    <PageLoadStatus loadable={{ ...data, status }}>Data yay!</PageLoadStatus>
                </div>
            </StoryContent>
        );
    };

export const Pending = story(LoadStatus.PENDING);
export const Loading = story(LoadStatus.LOADING);
export const Error = story(LoadStatus.ERROR);
export const Success = story(LoadStatus.SUCCESS);
