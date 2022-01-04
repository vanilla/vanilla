/**
 * @author Dominic Lacaille <dominic.lacaille@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { css } from "@emotion/css";

interface FormGroupClassesProps {
    sideBySide?: boolean;
    compact?: boolean;
}

export const formGroupClasses = ({ sideBySide, compact }: FormGroupClassesProps) => ({
    formGroup: css({
        margin: "8px 0",
        display: "flex",

        ...(sideBySide
            ? {
                  flexDirection: "row",
                  alignItems: "baseline",
                  justifyContent: "space-between",

                  ...(compact
                      ? {}
                      : {
                            borderBottom: "1px dotted #e7e8e9",
                            padding: "16px 0",

                            "&:last-of-type": {
                                borderBottom: 0,
                            },
                        }),
              }
            : {
                  flexDirection: "column",
              }),
    }),
    inputContainer: css({
        ...(sideBySide ? { flex: 12 } : {}),
    }),
    labelContainer: css({
        ...(sideBySide ? { flex: 6 } : { marginBottom: 8 }),
    }),
    label: css({
        fontSize: "14px",
        lineHeight: "21px",
        fontWeight: 600,
        marginBottom: 2,
    }),
    description: css({
        fontSize: "12px",
        color: "#949aa2",
        marginBottom: 0,
    }),
});
