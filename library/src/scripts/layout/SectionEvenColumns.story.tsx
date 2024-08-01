/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { SectionEvenColumns } from "@library/layout/SectionEvenColumns";
import { DummyWidget } from "@library/layout/WidgetLayout.story";
import Breadcrumbs from "@library/navigation/Breadcrumbs";
import { STORY_CRUMBS } from "@library/storybook/storyData";

export default {
    title: "Layout/Section Even Columns",
};

export function TwoColumns() {
    return (
        <SectionEvenColumns
            left={
                <>
                    <DummyWidget title="Left 1" />
                    <DummyWidget title="Left 2" />
                </>
            }
            right={
                <>
                    <DummyWidget title="Right 1" />
                    <DummyWidget title="Right 2" />
                </>
            }
            breadcrumbs={<Breadcrumbs>{STORY_CRUMBS}</Breadcrumbs>}
        />
    );
}

export function ThreeColumns() {
    return (
        <SectionEvenColumns
            left={
                <>
                    <DummyWidget title="Left 1" />
                    <DummyWidget title="Left 2" />
                </>
            }
            middle={
                <>
                    <DummyWidget title="Middle 1" />
                    <DummyWidget title="Middle 2" />
                </>
            }
            right={
                <>
                    <DummyWidget title="Right 1" />
                    <DummyWidget title="Right 2" />
                </>
            }
            breadcrumbs={<Breadcrumbs>{STORY_CRUMBS}</Breadcrumbs>}
        />
    );
}
