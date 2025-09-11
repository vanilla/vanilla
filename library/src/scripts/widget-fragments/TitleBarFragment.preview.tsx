/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { FragmentPreviewFlushWrapper } from "@dashboard/appearance/pages/FragmentPreviewFlushWrapper";
import BannerWidget from "@library/banner/BannerWidget";
import { TitleBarParamContextProvider } from "@library/headers/TitleBar.ParamContext";
import { PageBox } from "@library/layout/PageBox";
import SectionFullWidth from "@library/layout/SectionFullWidth";
import SectionOneColumn from "@library/layout/SectionOneColumn";
import { WidgetLayout } from "@library/layout/WidgetLayout";
import { STORY_IPSUM_LONG } from "@library/storybook/storyData";
import { BorderType } from "@library/styles/styleHelpersBorders";
import previewData from "@library/widget-fragments/BannerFragment.previewData";
import type TitleBarFragment from "@library/widget-fragments/TitleBarFragment.injectable";

export default function TitleBarFragmentPreview(props: {
    previewData: TitleBarFragment.Props;
    children?: React.ReactNode;
}) {
    return (
        <>
            <FragmentPreviewFlushWrapper>
                <TitleBarParamContextProvider {...previewData}>{props.children}</TitleBarParamContextProvider>
                <WidgetLayout>
                    <SectionFullWidth>
                        <BannerWidget title={"Dummy Banner"} showSearch={true} />
                    </SectionFullWidth>
                    <SectionOneColumn>
                        {[0, 1, 2, 3, 4, 5, 6, 7, 8, 9].map((i) => (
                            <PageBox options={{ borderType: BorderType.SHADOW }} key={i}>
                                {STORY_IPSUM_LONG}
                            </PageBox>
                        ))}
                    </SectionOneColumn>
                </WidgetLayout>
            </FragmentPreviewFlushWrapper>
        </>
    );
}
