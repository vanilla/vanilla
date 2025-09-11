import type { ButtonTypes } from "@library/forms/buttonTypes";
import type { IBoxOptions } from "@library/styles/cssUtilsTypes";
import type { ColorHelper } from "csx";

export const WidgetItemContentType = {
    Title: "title",
    TitleDescription: "title-description",
    TitleDescriptionImage: "title-description-image",
    TitleDescriptionIcon: "title-description-icon",
    TitleBackground: "title-background",
    TitleBackgroundDescription: "title-background-description",
    TitleChatBubble: "title-chat-bubble",
} as const;
export type WidgetItemContentType = (typeof WidgetItemContentType)[keyof typeof WidgetItemContentType];

export const WidgetImageType = {
    None: "none",
    Icon: "icon",
    Image: "image",
    Background: "background",
} as const;

export type WidgetImageType = (typeof WidgetImageType)[keyof typeof WidgetImageType];

/**
 * Converts a widget item content type to a widget image type.
 * @param contentType - The widget item content type to convert.
 * @returns The widget image type.
 */
export function widgetItemContentTypeToImageType(contentType: WidgetItemContentType): WidgetImageType {
    switch (contentType) {
        case WidgetItemContentType.TitleBackground:
        case WidgetItemContentType.TitleBackgroundDescription:
            return WidgetImageType.Background;
        case WidgetItemContentType.TitleDescriptionImage:
            return WidgetImageType.Image;
        case WidgetItemContentType.TitleDescriptionIcon:
            return WidgetImageType.Icon;
        default:
            return WidgetImageType.None;
    }
}

export interface IHomeWidgetItemOptions {
    box: IBoxOptions;
    contentType: WidgetItemContentType;
    fg: string | ColorHelper;
    display: {
        name: boolean;
        description: boolean;
        counts: boolean;
        cta: boolean;
    };
    verticalAlignment: "top" | "middle" | "bottom" | string;
    alignment: "center" | "left";
    viewMore: {
        labelCode: string;
        buttonType: ButtonTypes;
    };
    defaultIconUrl: string | undefined;
    defaultImageUrl: string | undefined;
    imagePlacement: "top" | "left";
    imagePlacementMobile: "top" | "left";
    callToActionText: string;
    fallbackIcon?: string;
    fallbackImage?: string;
    /** @deprecated */
    iconProps?: any;
}
