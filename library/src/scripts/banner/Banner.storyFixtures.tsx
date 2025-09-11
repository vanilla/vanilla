/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import Banner, { IBannerProps } from "@library/banner/Banner";
import { MockSearchData } from "@library/contexts/DummySearchContext";
import SearchContext from "@library/contexts/SearchContext";
import { ISelectBoxItem } from "@library/forms/select/SelectBox";
import { DeviceProvider } from "@library/layout/DeviceContext";
import { SectionProvider } from "@library/layout/LayoutContext";
import { SectionTypes } from "@library/layout/types/interface.layoutTypes";
import { StoryContent } from "@library/storybook/StoryContent";
import { StoryHeading } from "@library/storybook/StoryHeading";
import { StoryParagraph } from "@library/storybook/StoryParagraph";
import { globalVariables } from "@library/styles/globalStyleVars";
import { MemoryRouter } from "react-router";

const globalVars = globalVariables();

interface IStoryBannerProps extends IBannerProps {
    message?: string;
    bannerProps?: IBannerProps;
    onlyOne?: boolean;
}

export function StoryBanner(props: IStoryBannerProps) {
    const { bannerProps = {}, message } = props;
    // Allow either passing props through "bannerProps", or overwriting them individually
    const mergedProps: IBannerProps = {
        action: props.action ?? bannerProps.action,
        title: props.title ?? bannerProps.title,
        description:
            props.description ??
            bannerProps.description ??
            `Sample description: Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.`,
        className: props.className ?? bannerProps.className,
        backgroundImage: props.backgroundImage ?? bannerProps.backgroundImage,
        contentImage: props.contentImage ?? bannerProps.contentImage,
        logoImage: props.logoImage ?? bannerProps.logoImage,
        searchBarNoTopMargin: props.searchBarNoTopMargin ?? bannerProps.searchBarNoTopMargin,
        forceSearchOpen: props.forceSearchOpen ?? bannerProps.forceSearchOpen,
        isContentBanner: props.isContentBanner ?? bannerProps.isContentBanner,
        scope: props.scope ?? bannerProps.scope,
        initialQuery: props.initialQuery ?? bannerProps.initialQuery,
    };

    return (
        <MemoryRouter>
            <SearchContext.Provider value={{ searchOptionProvider: new MockSearchData() }}>
                <SectionProvider type={SectionTypes.THREE_COLUMNS}>
                    <Banner {...mergedProps} />
                </SectionProvider>
            </SearchContext.Provider>
            <StoryContent>
                {message && (
                    <>
                        <StoryHeading>Note:</StoryHeading>
                        <StoryParagraph>{message}</StoryParagraph>
                    </>
                )}
            </StoryContent>
        </MemoryRouter>
    );
}

export function StoryBannerWithScope(props: IStoryBannerProps) {
    const { onlyOne = false } = props;
    const optionsItems: ISelectBoxItem[] = [
        {
            name: "scope1",
            value: "scope1",
        },
        {
            name: "scope2",
            value: "scope2",
        },
        {
            name: "Everywhere",
            value: "every-where",
        },
    ];

    const value = {
        name: "Everywhere",
        value: "every-where",
    };

    const scope = {
        optionsItems,
        value,
    };

    return (
        <DeviceProvider>
            {/* Fix z-index in storybook */}
            <style>{`#root > div { z-index: inherit; }`}</style>
            <style>{`#root { min-height: 100%; }`}</style>

            {!onlyOne && (
                <>
                    <StoryContent>
                        <StoryHeading>{`Banner - Search with no button`}</StoryHeading>
                    </StoryContent>
                </>
            )}
            <StoryBanner {...props} scope={scope} />

            {!onlyOne && (
                <>
                    <StoryContent>
                        <StoryHeading>{`Title bar search with button`}</StoryHeading>
                    </StoryContent>
                    <StoryBanner
                        {...props}
                        initialQuery={
                            "This is an example queryThis is an example queryThis is an example queryThis is an example queryThis is an example queryThis is an example query"
                        }
                    />
                    <StoryContent>
                        <StoryHeading>{`Title bar search with scope`}</StoryHeading>
                    </StoryContent>
                    <StoryBanner
                        {...props}
                        scope={scope}
                        initialQuery={"This is an example queryThis is an example queryThis is an example query"}
                    />
                </>
            )}
        </DeviceProvider>
    );
}
