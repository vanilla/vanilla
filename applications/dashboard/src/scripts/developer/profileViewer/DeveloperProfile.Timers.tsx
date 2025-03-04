/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    IDeveloperProfileDetails,
    IDeveloperProfileSpan,
} from "@dashboard/developer/profileViewer/DeveloperProfile.types";
import { useDeveloperProfile } from "@dashboard/developer/profileViewer/DeveloperProfile.context";
import { developerProfileTimersClasses } from "@dashboard/developer/profileViewer/DeveloperProfile.Timers.classes";
import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { List } from "@library/lists/List";
import { ListItem } from "@library/lists/ListItem";
import { ListItemLayout } from "@library/lists/ListItem.variables";
import { MetaIcon } from "@library/metas/Metas";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { ToolTip } from "@library/toolTip/ToolTip";
import React, { useEffect } from "react";
import { DeveloperProfileSpanDetails } from "@dashboard/developer/profileViewer/DeveloperProfile.SpanDetails";
import { useLastValue } from "@vanilla/react-utils";

interface IProps {
    profile: IDeveloperProfileDetails;
}

export function DeveloperProfilerTimers(props: IProps) {
    const { profile } = props;
    const groupedTimers = useGroupedTimers(profile);
    const { selectedSpan, setSelectedSpan, filteredSpanTypes } = useDeveloperProfile();

    const lastSelectedSpan = useLastValue(selectedSpan);

    useEffect(() => {
        if (selectedSpan && lastSelectedSpan != selectedSpan) {
            const spanDomNode = document.getElementById(selectedSpan.uuid);
            if (!spanDomNode) {
                return;
            }

            const rect = spanDomNode.getBoundingClientRect();

            const isInView =
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= document.documentElement.clientHeight &&
                rect.right <= document.documentElement.clientWidth;

            if (!isInView) {
                spanDomNode.scrollIntoView();
            }
        }
    }, [lastSelectedSpan, selectedSpan]);

    return (
        <div style={{ marginTop: -24 }}>
            <List
                options={{
                    itemLayout: ListItemLayout.TITLE_METAS_DESCRIPTION,
                    itemBox: {
                        borderType: BorderType.SEPARATOR_BETWEEN,
                    },
                }}
            >
                {Object.values(groupedTimers)
                    .filter((group) => filteredSpanTypes?.includes(group.name) ?? true)
                    .sort((a, b) => b.totalElapsedMs - a.totalElapsedMs)
                    .map((group) => {
                        return (
                            <ListItem
                                key={group.name}
                                name={group.name}
                                metas={
                                    <>
                                        <MetaIcon icon="meta-time" style={{ marginLeft: -4 }}>
                                            {group.totalElapsedMs.toFixed(2)}ms
                                        </MetaIcon>
                                        <MetaIcon icon="meta-categories">{group.spans.length} Items</MetaIcon>
                                    </>
                                }
                                description={
                                    <TimerGroupSpans
                                        group={group}
                                        profile={profile}
                                        setSelectedSpan={setSelectedSpan}
                                        selectedSpan={selectedSpan}
                                    />
                                }
                            />
                        );
                    })}
            </List>
        </div>
    );
}

function TimerGroupSpans(props: {
    group: ITimerGroup;
    profile: IDeveloperProfileDetails;
    setSelectedSpan: (span: IDeveloperProfileSpan) => void;
    selectedSpan: IDeveloperProfileSpan | null;
}) {
    const { group, profile } = props;

    if (group.spans.length === 0) {
        return <></>;
    }

    const classes = developerProfileTimersClasses();
    return (
        <div style={{ display: "flex", gap: 6, alignItems: "center", flexWrap: "wrap" }}>
            {group.spans
                .sort((a, b) => b.elapsedMs - a.elapsedMs)
                .map((span) => {
                    return (
                        <ToolTip
                            key={span.uuid}
                            customWidth={460}
                            label={
                                <DeveloperProfileSpanDetails
                                    span={span}
                                    allSpans={Object.values(profile.profile.spans)}
                                />
                            }
                        >
                            <Button
                                id={span.uuid}
                                buttonType={ButtonTypes.CUSTOM}
                                className={cx(classes.span, { "focus-visible": span === props.selectedSpan })}
                                style={{
                                    flex: span.elapsedMs * 1000,
                                }}
                                onClick={() => {
                                    props.setSelectedSpan(span);
                                }}
                            >
                                <div style={{ whiteSpace: "nowrap" }}>{span.elapsedMs.toFixed(2)}ms</div>
                            </Button>
                        </ToolTip>
                    );
                })}
        </div>
    );
}

type ITimers = { [type: string]: ITimerGroup };

interface ITimerGroup {
    name: string;
    spans: IDeveloperProfileSpan[];
    totalElapsedMs: number;
}

function useGroupedTimers(profile: IDeveloperProfileDetails): ITimers {
    const groupsByType: ITimers = {};

    for (const span of Object.values(profile.profile.spans)) {
        if (!groupsByType[span.type]) {
            groupsByType[span.type] = {
                name: span.type,
                spans: [],
                totalElapsedMs: 0,
            };
        }

        groupsByType[span.type].spans.push(span);
        groupsByType[span.type].totalElapsedMs += span.elapsedMs;
    }

    return groupsByType;
}
