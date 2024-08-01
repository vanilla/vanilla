/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IHomeWidgetItemProps } from "@library/homeWidget/HomeWidgetItem";
import { HomeWidgetItemContentType } from "@library/homeWidget/HomeWidgetItem.styles";
import { STORY_IMAGE, STORY_ICON, STORY_IPSUM_SHORT, STORY_IPSUM_MEDIUM } from "@library/storybook/storyData";
import { BorderType } from "@library/styles/styleHelpersBorders";

export const STORY_WIDGET_ITEMS = [
    dummyWidgetItemProps(),
    dummyWidgetItemProps(),
    dummyWidgetItemProps(),
    dummyWidgetItemProps(),
    dummyWidgetItemProps(),
];

export interface IDummyWidgetItemProps {
    image?: boolean;
    icon?: boolean;
    iconCustomUrl?: string;
    imageMissing?: boolean;
    shortTitle?: boolean;
    shortBody?: boolean;
    noBody?: boolean;
    noCounts?: boolean;
    noBorder?: boolean;
    iconBackground?: boolean;
}

export function dummyWidgetItemProps(props?: IDummyWidgetItemProps): IHomeWidgetItemProps {
    return {
        options: {
            contentType: props?.image
                ? HomeWidgetItemContentType.TITLE_DESCRIPTION_IMAGE
                : props?.icon
                ? HomeWidgetItemContentType.TITLE_DESCRIPTION_ICON
                : HomeWidgetItemContentType.TITLE_DESCRIPTION,

            box: {
                borderType: props?.noBorder ? BorderType.NONE : undefined,
            },
        },
        url: "https://vanillaforums.com/en/",
        imageUrl: props?.imageMissing ? undefined : STORY_IMAGE,
        iconUrl: props?.imageMissing ? undefined : props?.iconCustomUrl ? props.iconCustomUrl : STORY_ICON,
        to: "#",
        name: props?.shortTitle ? "Short Title" : "Hello Longer longer longer longer longer even longer",
        description: props?.noBody ? undefined : props?.shortBody ? STORY_IPSUM_SHORT : STORY_IPSUM_MEDIUM,
        counts: props?.noCounts
            ? undefined
            : [
                  { count: 2000, labelCode: "Items" },
                  { count: 42, labelCode: "Resources" },
              ],
    };
}
