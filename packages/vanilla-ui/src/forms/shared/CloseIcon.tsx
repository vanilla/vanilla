/**
 * @author Raphaël Bergina <rbergina@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { t } from "@vanilla/i18n";
import * as React from "react";

/** @internal */
export function CloseIcon(props: React.SVGProps<SVGSVGElement>) {
    const title = t("Close");
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 9.5 9.5"
            {...props}
            aria-hidden={props["aria-hidden"] !== undefined ? props["aria-hidden"] : "true"}
            className={props.className}
        >
            <title>{title}</title>
            <path
                fill="currentColor"
                d="M10.836,11.75,7.793,8.707A1,1,0,0,1,9.207,7.293l3.043,3.043,3.043-3.043a1,1,0,0,1,1.414,1.414L13.664,11.75l3.043,3.043a1,1,0,0,1-1.414,1.414L12.25,13.164,9.207,16.207a1,1,0,1,1-1.439-1.389l.025-.025Z"
                transform="translate(-7.488 -7.012)"
            />
        </svg>
    );
}
