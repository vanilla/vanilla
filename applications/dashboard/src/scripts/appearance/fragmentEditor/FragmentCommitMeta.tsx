/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { FragmentsApi } from "@dashboard/appearance/fragmentEditor/FragmentsApi";
import { css } from "@emotion/css";
import DateTime from "@library/content/DateTime";
import Translate from "@library/content/Translate";
import { MetaItem, Metas } from "@library/metas/Metas";
import { metasClasses } from "@library/metas/Metas.styles";
import ProfileLink from "@library/navigation/ProfileLink";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { ColorVar } from "@library/styles/CssVar";
import { globalVariables } from "@library/styles/globalStyleVars";
import { singleBorder } from "@library/styles/styleHelpersBorders";

export function FragmentCommitMeta(props: { fragment: FragmentsApi.Fragment; className?: string }) {
    const { fragment, className } = props;
    return (
        <Metas className={className}>
            <MetaItem>
                <span className={classes.metaTag}>
                    <span className={classes.statusCircle} style={{ background: statusColor(fragment.status) }} />
                    {fragment.status}
                </span>
            </MetaItem>
            <MetaItem>
                <Translate
                    source="Authored <0 /> by <1 />"
                    c0={<DateTime timestamp={fragment.dateRevisionInserted} />}
                    c1={
                        <ProfileLink
                            className={metasClasses().metaLink}
                            userFragment={fragment.revisionInsertUser}
                            isUserCard={true}
                        />
                    }
                />
            </MetaItem>
        </Metas>
    );
}

function statusColor(status: FragmentsApi.Fragment["status"]): string {
    switch (status) {
        case "draft":
            return globalVariables().messageColors.warning.state.toString();
        case "active":
            return globalVariables().messageColors.confirm.toString();
        case "past-revision":
        default:
            return ColorsUtils.var(ColorVar.Foreground);
    }
}

const classes = {
    metaTag: css({
        border: singleBorder(),
        padding: "0px 8px 2px",
        display: "inline-flex",
        alignItems: "baseline",
        gap: 6,
        borderRadius: 6,
        marginLeft: "-6px",
    }),
    statusCircle: css({
        display: "inline-block",
        width: 8,
        height: 8,
        borderRadius: "50%",
    }),
};
