/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React, { useEffect, useMemo, useState } from "react";
import { StatusLightClasses } from "@library/statusLight/StatusLight.classes";
import { t } from "@vanilla/i18n";
import { cx } from "@emotion/css";

export default function StatusLight(
    props: Omit<React.HTMLAttributes<HTMLSpanElement>, "children"> & { active?: boolean },
) {
    const { active = true, title = active ? t("Active") : t("Inactive"), className, ...rest } = props;
    const classes = StatusLightClasses(active);

    return (
        <span className={cx(classes.root, props.className)} title={title} {...rest}>
            <span className="sr-only">{title}</span>
        </span>
    );
}
