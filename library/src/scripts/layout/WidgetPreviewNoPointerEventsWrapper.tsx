/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import React from "react";
import { css } from "@emotion/css";

export default function WidgetPreviewNoPointerEventsWrapper(props: React.PropsWithChildren<{}>) {
    return (
        <div
            className={css({
                pointerEvents: "none",
            })}
        >
            {props.children}
        </div>
    );
}
