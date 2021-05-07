/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";

interface FormGroupClassesProps {
    sideBySide?: boolean;
}

export const formGroupClasses = ({ sideBySide }: FormGroupClassesProps) => ({
    formGroup: css({
        margin: "8px 0",

        ...(sideBySide
            ? {
                  display: "flex",
                  flexDirection: "row",
                  alignItems: "center",
                  justifyContent: "space-between",
              }
            : {}),
    }),
    label: css({
        display: "inline-block",
        fontSize: "13px",
        fontWeight: 600,

        ...(sideBySide ? {} : { marginBottom: 8 }),
    }),
});
