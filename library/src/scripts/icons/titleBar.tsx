/*
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React, { AriaAttributes } from "react";
import { t } from "@library/utility/appUtils";
import classNames from "classnames";
import { iconClasses } from "@library/icons/iconStyles";
import { areaHiddenType } from "@library/styles/styleHelpersVisibility";
import { globalVariables } from "@library/styles/globalStyleVars";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { Icon } from "@vanilla/icons";

export function HelpIcon(props: { className?: string }) {
    const title = t("Help");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 16 16"
            className={classNames(classes.compact, "icon-help", props.className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <path
                d="M12,19a7,7,0,1,0-7-7A7,7,0,0,0,12,19Zm0,1a8,8,0,1,1,8-8A8,8,0,0,1,12,20Zm-.866-6.5v-.338a2,2,0,0,1,.211-.969,2.757,2.757,0,0,1,.741-.8,4.09,4.09,0,0,0,.812-.773,1.156,1.156,0,0,0,.183-.656.826.826,0,0,0-.3-.683,1.333,1.333,0,0,0-.851-.238A2.941,2.941,0,0,0,11,9.185a6.65,6.65,0,0,0-.836.344L9.721,8.6a4.653,4.653,0,0,1,2.3-.6,2.485,2.485,0,0,1,1.645.508,1.727,1.727,0,0,1,.609,1.4,1.983,1.983,0,0,1-.117.706,2.006,2.006,0,0,1-.352.59,5.653,5.653,0,0,1-.812.731,3.088,3.088,0,0,0-.659.64,1.229,1.229,0,0,0-.166.682V13.5Zm-.217,1.688a.7.7,0,0,1,.778-.8.775.775,0,0,1,.582.209.818.818,0,0,1,.2.59.838.838,0,0,1-.2.595.878.878,0,0,1-1.156.006A.844.844,0,0,1,10.917,15.185Z"
                transform="translate(-4 -4)"
                fill="currentColor"
            />
        </svg>
    );
}

export function ComposeIcon(props: { className?: string }) {
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames(classes.standard, "icon-compose", props.className)}
            aria-hidden="true"
        >
            <path
                fill="currentColor"
                d="M23.591,1.27l-.9-.9a1.289,1.289,0,0,0-1.807,0l-.762.863,2.6,2.587.868-.751a1.24,1.24,0,0,0,.248-.373,1.255,1.255,0,0,0,0-1.052A1.232,1.232,0,0,0,23.591,1.27ZM19.5,20.5H3.5V4.5H15.4l1.4-1.431H2.751A1,1,0,0,0,2,4.07V20.939a1,1,0,0,0,1,1H20.011a1,1,0,0,0,1-1V7L19.5,8.445ZM21.364,3.449l-9.875,9.8-.867-.861,9.874-9.8-.867-.863-4.938,4.9-4.938,4.9L8.74,15.167l3.617-1.055,9.875-9.8Z"
            />
        </svg>
    );
}

export function DownloadIcon(props: { className?: string }) {
    const title = t("Download");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames(classes.standard, "icon-compose", props.className)}
            role="img"
            aria-label={title}
        >
            <title>{title}</title>
            <path
                d="M6.483,10.462h.005a.5.5,0,0,1,.707.01l3.954,4.066V3.887a.5.5,0,0,1,.5-.5h.621a.5.5,0,0,1,.5.5V14.533l4.035-4.073h0a.5.5,0,0,1,.707,0l.437.437a.5.5,0,0,1,0,.707h0l-5.6,5.6a.5.5,0,0,1-.707,0h0l-5.6-5.6a.5.5,0,0,1,0-.707h0ZM20.25,19.5V17.25a.75.75,0,0,1,1.5,0v3A.75.75,0,0,1,21,21H3a.75.75,0,0,1-.75-.75v-3a.75.75,0,0,1,1.5,0V19.5Z"
                fill="currentColor"
            />
        </svg>
    );
}

export function VanillaLogo(props: { className?: string; fill?: string; isMobile?: boolean }) {
    const { className, isMobile } = props;
    const classes = iconClasses();
    const fill = props.fill ? props.fill : "currentColor";
    return (
        <Icon
            icon={"vanilla-logo"}
            className={classNames(className, {
                [classes.vanillaLogo]: !isMobile,
                [classes.vanillaLogoMobile]: isMobile,
            })}
            fill={fill}
        />
    );
}

export function SettingsIcon(props: { className?: string }) {
    const title = t("Settings");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 18"
            className={classNames(classes.settings, "icon-settings", props.className)}
            aria-hidden="true"
        >
            <title>{title}</title>
            <path
                d="M6,18v-.5a.5.5,0,0,1,.5-.5h4a.5.5,0,0,1,.5.5V18H22v2H11v.5a.5.5,0,0,1-.5.5h-4a.5.5,0,0,1-.5-.5V20H2V18Zm9-7v-.5a.5.5,0,0,1,.5-.5h4a.5.5,0,0,1,.5.5V11h2v2H20v.5a.5.5,0,0,1-.5.5h-4a.5.5,0,0,1-.5-.5V13H2V11ZM4,4V3.5A.5.5,0,0,1,4.5,3h4a.5.5,0,0,1,.5.5V4H22V6H9v.5a.5.5,0,0,1-.5.5h-4A.5.5,0,0,1,4,6.5V6H2V4ZM5,4V6H8V4Zm11,7v2h3V11ZM7,18v2h3V18Z"
                transform="translate(-2 -3)"
                fill="currentColor"
            />
        </svg>
    );
}

export enum UserIconTypes {
    DEFAULT = "default",
    SELECTED_INACTIVE = "fg background added",
    SELECTED_ACTIVE = "primary color and primary background added",
}

interface IUserIconType {
    head: {
        outline: string | undefined;
        bg: string | undefined;
    };
    body: {
        outline: string | undefined;
        bg: string | undefined;
    };
    circle: {
        outline: string;
        bg: string;
    };
}

const userIconStyles = (type: UserIconTypes): IUserIconType => {
    const mainColors = globalVariables().mainColors;
    const fg = ColorsUtils.colorOut(mainColors.fg) as string;
    const bg = ColorsUtils.colorOut(mainColors.bg) as string;
    const primary = ColorsUtils.colorOut(mainColors.primary) as string;

    const styles: IUserIconType = {
        head: {
            outline: undefined,
            bg: undefined,
        },
        body: {
            outline: undefined,
            bg: undefined,
        },
        circle: {
            outline: fg,
            bg: bg,
        },
    };

    switch (type) {
        case UserIconTypes.SELECTED_INACTIVE:
            styles.head.bg = bg;
            styles.body.bg = bg;
            styles.circle.bg = fg;
            break;
        case UserIconTypes.SELECTED_ACTIVE:
            styles.head.bg = bg;
            styles.body.bg = bg;
            styles.circle.outline = primary;
            styles.circle.bg = primary;
            break;
        default:
            // DEFAULT
            styles.head.outline = fg;
            styles.body.outline = fg;
    }
    return styles;
};

export function UserIcon(props: { styleType?: UserIconTypes; className?: string; title: string; alt: string }) {
    const { styleType = UserIconTypes.DEFAULT, className, title = t("Me"), alt } = props;
    const classes = iconClasses();

    const { head, body, circle } = userIconStyles(styleType);

    return (
        <svg
            role={"img"}
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 20 20"
            className={classNames(classes.user, className)}
            aria-label={title}
        >
            <title>{title}</title>
            <desc>{alt}</desc>

            {/*Background for whole Icon*/}
            {circle.bg && <path d="M10,0A10,10,0,1,0,20,10,10,10,0,0,0,10,0Z" style={{ fill: circle.bg }} />}

            {/*Body bg*/}
            {body.bg && (
                <path
                    d="M15.415,17.3a8.761,8.761,0,0,0,.761-.653c.18-.175.325-.335.436-.463A4.027,4.027,0,0,0,12.656,12.5c-.827,0-1.226.459-2.581.459S8.325,12.5,7.494,12.5a4.023,4.023,0,0,0-3.823,3.17,3.034,3.034,0,0,0,.486.916,3.559,3.559,0,0,0,.909.781,10.755,10.755,0,0,0,4.8,1.616A10.634,10.634,0,0,0,15.415,17.3Z"
                    style={{ fill: body.bg }}
                />
            )}

            {/*Body Outline */}
            {body.outline && (
                <path
                    d="M12.663,12.5c-.827,0-1.226.459-2.581.459S8.332,12.5,7.5,12.5a4.022,4.022,0,0,0-3.824,3.173,13.175,13.175,0,0,0,1.4,1.7l-.02-.512a2.486,2.486,0,0,1,2.488-2.982h.049a10.754,10.754,0,0,0,2.5.475,10.684,10.684,0,0,0,2.487-.472c1.408.059,2.474.732,2.52,3.1l.325.326a6.453,6.453,0,0,0,1.2-1.117A4.025,4.025,0,0,0,12.663,12.5Z"
                    style={{ fill: body.outline }}
                />
            )}

            {/*Head Background */}
            {head.bg && (
                <path
                    d="M10.141,4.514h0a3.55,3.55,0,1,0,3.533,3.567V8.063a3.54,3.54,0,0,0-3.531-3.549h0Z"
                    style={{ fill: head.bg }}
                />
            )}

            {/*Head Outline*/}
            {head.outline && (
                <path
                    d="M10.141,4.514h0a3.55,3.55,0,1,0,3.533,3.567V8.063a3.54,3.54,0,0,0-3.531-3.549h0Zm0,5.808a2.26,2.26,0,1,1,2.253-2.267v.009a2.254,2.254,0,0,1-2.25,2.258Z"
                    style={{ fill: head.outline }}
                />
            )}

            {/*Circle Border*/}
            {circle.outline && (
                <path
                    d="M10,0A10,10,0,1,0,20,10,10,10,0,0,0,10,0Zm0,18.419A8.418,8.418,0,1,1,18.417,10,8.418,8.418,0,0,1,10,18.419Z"
                    style={{ fill: circle.outline }}
                />
            )}
        </svg>
    );
}

export function NoUserPhotoIcon(props: { className?: string; photoAlt?: string }) {
    const title = t("User");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            className={classNames(classes.standard, "icon-noUserPhoto", props.className)}
            aria-hidden="true"
            aria-label={props.photoAlt}
        >
            <title>{props.photoAlt || title}</title>
            <path
                d="M12.046,12.907c-2.225,0-4.03-2.218-4.03-4.954C8.016,4.16,9.82,3,12.046,3s4.03,1.16,4.03,4.953C16.076,10.689,14.271,12.907,12.046,12.907Zm8.9,6.452a17.94,17.94,0,0,1-.194,4.2A1.025,1.025,0,0,1,19.9,24H3.96a1.024,1.024,0,0,1-.852-.443,17.956,17.956,0,0,1,.04-4.2l2.033-4.39a1,1,0,0,1,.46-.469L8.8,12.926a.211.211,0,0,1,.217.017,5.149,5.149,0,0,0,6.068,0,.211.211,0,0,1,.216-.017L18.452,14.5a1,1,0,0,1,.46.469Z"
                fill="currentColor"
            />
        </svg>
    );
}

export function UserWarningIcon(props: { className?: string }) {
    const title = t("Warning");
    const classes = iconClasses();
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 40 40"
            className={classNames(classes.userWarning, props.className)}
            aria-hidden="true"
        >
            <title>{title}</title>
            <path
                d="M32.707,25.862a2.167,2.167,0,0,1-1.876,3.249H9.169a2.168,2.168,0,0,1-1.877-3.249L18.123,7.083a2.168,2.168,0,0,1,3.754,0Z"
                fill="#d0021b"
                stroke="#fff"
                strokeWidth="1px"
            />
            <path
                d="M20,20.979a2.077,2.077,0,1,0,2.076,2.077A2.076,2.076,0,0,0,20,20.979Zm-1.971-7.463.335,6.139a.541.541,0,0,0,.54.512H21.1a.543.543,0,0,0,.541-.512l.334-6.139a.542.542,0,0,0-.54-.572H18.569A.542.542,0,0,0,18.029,13.516Z"
                fill="#fff"
            />
        </svg>
    );
}
