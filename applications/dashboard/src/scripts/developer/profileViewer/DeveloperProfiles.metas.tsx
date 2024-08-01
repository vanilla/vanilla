/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IDeveloperProfile, IDeveloperProfileSpan } from "@dashboard/developer/profileViewer/DeveloperProfile.types";
import { useDownloadDetailsMutation } from "@dashboard/developer/profileViewer/DeveloperProfiles.hooks";
import DateTime from "@library/content/DateTime";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { DownloadIcon } from "@library/icons/titleBar";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { MetaIcon, MetaItem } from "@library/metas/Metas";

export function DeveloperProfileMetas(props: IDeveloperProfile) {
    return (
        <>
            <MetaIcon icon="meta-time" style={{ marginLeft: -4 }}>
                {props.requestElapsedMs.toFixed(2)}ms
            </MetaIcon>
            <MetaItem>
                Recorded:{" "}
                <strong>
                    <DateTime timestamp={props.dateRecorded} mode="fixed" />
                </strong>
            </MetaItem>
        </>
    );
}

export function getDeveloperProfileSpanTitle(props: IDeveloperProfileSpan) {
    switch (props.type) {
        case "dbRead": {
            return "Database Read";
        }
        case "dbWrite": {
            return "Database Write";
        }
        case "cacheRead":
            return "Cache Read";
        case "cacheWrite":
            return "Cache Write";
        case "http-request":
            return `${props.data.method} ${props.data.url}`;
        default:
            return props.data.name ?? props.type;
    }
}

export const DEVELOPER_PROFILE_SPAN_COLORS: Record<string, string> = {
    dbRead: "#8ecae6",
    dbWrite: "#219ebc",
    cacheWrite: "#fb8500",
    cacheRead: "#ffb703",
    "http-request": "#EB806D",
    "create-instance": "#C59DD5",
};

export function getDeveloperProfileSpanColor(spanType: IDeveloperProfileSpan["type"]): string {
    return DEVELOPER_PROFILE_SPAN_COLORS[spanType] ?? "#74D17D";
}

export function DeveloperProfileDownloadButton(props: { profileID: number }) {
    const mutation = useDownloadDetailsMutation();
    return (
        <Button
            buttonType={ButtonTypes.ICON}
            onClick={() => {
                mutation.mutate(props.profileID);
            }}
        >
            {mutation.isLoading ? <ButtonLoader /> : <DownloadIcon />}
        </Button>
    );
}
