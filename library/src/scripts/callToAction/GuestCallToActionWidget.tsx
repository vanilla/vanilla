/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import CallToActionWidget from "@library/callToAction/CallToActionWidget";
import { useRegisterLink, useSignInLink } from "@library/contexts/EntryLinkContext";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { t } from "@vanilla/i18n";

interface IProps extends React.ComponentProps<typeof CallToActionWidget> {}

export default function GuestCallToActionWidget(props: IProps) {
    const signInLink = useSignInLink();
    const registerLink = useRegisterLink();
    let widgetProps = {
        ...props,
        button: {
            title: props.button?.title ?? t("Sign In"),
            type: props.button?.type ?? ButtonTypes.PRIMARY,
            url: signInLink,
        },
    };

    if (registerLink) {
        widgetProps = {
            ...widgetProps,
            secondButton: {
                title: props.secondButton?.title ?? t("Sign In"),
                type: props.secondButton?.type ?? ButtonTypes.STANDARD,
                url: registerLink,
            },
        };
    }
    return <CallToActionWidget {...widgetProps} />;
}
