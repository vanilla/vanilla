/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import { CallToAction } from "@library/callToAction/CallToAction";
import { t } from "@vanilla/i18n";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { ImageSourceSet } from "@library/utility/appUtils";
import { BorderType } from "@library/styles/styleHelpersBorders";

interface IProps {
    title: string;
    description?: string;
    alignment?: "center" | "left";
    textColor?: string;
    borderType?: BorderType;
    button?: {
        title: string;
        type?: Partial<ButtonTypes>;
        url: string;
    };
    secondButton?: {
        title: string;
        type?: Partial<ButtonTypes>;
        url: string;
    };
    background?: {
        color?: string;
        image?: string;
        imageUrlSrcSet?: ImageSourceSet;
        useOverlay?: boolean;
    };
}

export default function CallToActionWidget(props: IProps) {
    return (
        <CallToAction
            to={props.button?.url ?? ""}
            textCTA={(props.button?.title as string) ?? ""}
            title={props.title}
            description={props.description ?? ""}
            otherCTAs={
                props.secondButton?.url
                    ? [
                          {
                              to: props.secondButton?.url ?? "",
                              textCTA: t((props.secondButton?.title as string) ?? ""),
                              linkButtonType: props.secondButton?.type,
                          },
                      ]
                    : undefined
            }
            options={{
                alignment: props.alignment ?? "center",
                textColor: props.textColor,
                linkButtonType: props.button?.type,
                box: {
                    background: {
                        color: props.background?.color,
                        image: props.background?.image,
                        imageSrcSet: props.background?.imageUrlSrcSet,
                    },
                    borderType: props.borderType,
                },
                useOverlay: props.background?.useOverlay,
            }}
        />
    );
}
