/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    getDeveloperProfileSpanTitle,
    DEVELOPER_PROFILE_SPAN_COLORS,
} from "@dashboard/developer/profileViewer/DeveloperProfiles.metas";
import {
    IDeveloperProfileDetails,
    IDeveloperProfileSpan,
} from "@dashboard/developer/profileViewer/DeveloperProfile.types";
import { FlameChartReactWrapper } from "@dashboard/developer/profileViewer/FlameChartReactWrapper";
import { useDeveloperProfile } from "@dashboard/developer/profileViewer/DeveloperProfile.context";
import { css, cx } from "@emotion/css";
import { DataList } from "@library/dataLists/DataList";
import { toolTipClasses } from "@library/toolTip/toolTipStyles";
import { useMeasure } from "@vanilla/react-utils";
import { notEmpty } from "@vanilla/utils";
import { FlameChartNode } from "flame-chart-js";
import React, { useMemo, useRef, useState } from "react";
import { DeveloperProfileSpanDetails } from "@dashboard/developer/profileViewer/DeveloperProfile.SpanDetails";

interface IProps {
    profile: IDeveloperProfileDetails;
    visibleTypes?: string[] | null;
}

interface IZoom {
    start: number;
    end: number;
}

interface MyFlameChartNode extends FlameChartNode {
    raw: IDeveloperProfileSpan;
    children: MyFlameChartNode[];
}

interface IMousePosition {
    x: number;
    y: number;
}
const classes = {
    tooltipContainer: css({
        background: "#fff",
        cursor: "default",
        pointerEvents: "none",
    }),
    tooltipNub: css({
        position: "absolute",
        top: "-6px",
        left: "50%",
        transform: "translateX(-50%)",
    }),
    tooltipContent: css({
        width: "100%",
        maxWidth: "100%",
    }),
};

const TOOLTIP_WIDTH = 460;

export function DeveloperProfileFlameChart(props: IProps) {
    const { profile } = props.profile;
    const rootNodes = useMemo(() => [spanAsFlameNode(profile.rootSpanUuid, profile.spans)!], []);
    const filteredNodes = useFilteredFlameNodes(rootNodes);
    const { setSelectedSpan, selectedSpan } = useDeveloperProfile();
    const [zoom, setZoom] = useState<IZoom | null>(null);

    const [tooltipUuid, setTooltipUuid] = useState<string | null>(null);
    const [mousePosition, setMousePosition] = useState<IMousePosition | null>(null);
    const tooltipSpan = tooltipUuid ? profile.spans[tooltipUuid] ?? null : null;
    const containerRef = useRef<HTMLDivElement | null>(null);
    const containerMeasure = useMeasure(containerRef, { watchRef: true });
    const tooltipContentRef = useRef<HTMLDivElement | null>(null);
    const tooltipContentMeasure = useMeasure(tooltipContentRef, { watchRef: true });

    const flameClass = useMemo(() => {
        const flameClass = css({
            minHeigth: 300,
            height: window.innerHeight - containerMeasure.top,
            width: containerMeasure.width ? containerMeasure.width : "100%",
        });
        return flameClass;
    }, [containerMeasure.width, containerMeasure.top, window.innerHeight]);

    return (
        <div>
            <div
                ref={containerRef}
                onMouseMove={(e) => {
                    setMousePosition({ x: e.clientX, y: e.clientY });
                }}
                onMouseLeave={(e) => {
                    setMousePosition(null);
                }}
            >
                <FlameChartReactWrapper
                    zoom={zoom ?? undefined}
                    className={flameClass}
                    data={filteredNodes}
                    settings={{
                        styles: {
                            main: {
                                blockHeight: 32,
                                blockPaddingLeftRight: 6,
                                font: "12px Open Sans",
                            },
                        },
                        options: {
                            tooltip(data, renderEngine, mouse) {
                                let newUuid = null;
                                if (data?.data?.source?.raw && mouse) {
                                    newUuid = data.data.source.raw.uuid;
                                }
                                if (newUuid !== tooltipUuid) {
                                    if (newUuid === null) {
                                        return;
                                    } else {
                                        setTooltipUuid(newUuid);
                                    }
                                }
                            },
                        },
                    }}
                    onSelect={(node) => {
                        if (node?.type == "flame-chart-node" && node.node) {
                            const source = node.node.source as MyFlameChartNode;
                            setZoom({
                                start: source.start,
                                end: source.start + source.duration,
                            });
                            setSelectedSpan(source.raw);
                        }
                    }}
                    colors={DEVELOPER_PROFILE_SPAN_COLORS}
                />
                {tooltipSpan && mousePosition && (
                    <div
                        className={classes.tooltipContainer}
                        style={{
                            position: "fixed",
                            transform: `translate3d(${Math.min(
                                mousePosition.x - TOOLTIP_WIDTH / 2,
                                window.innerWidth - TOOLTIP_WIDTH - 60,
                            )}px, ${mousePosition.y + 40}px, 0)`,
                            top: 0,
                            left: 0,
                            transition: "all 0.05s ease",
                            width: TOOLTIP_WIDTH,
                            height: tooltipContentMeasure.height || 100,
                            visibility: tooltipContentMeasure.width === 0 ? "hidden" : "visible",
                        }}
                    >
                        <div ref={tooltipContentRef} className={cx(toolTipClasses().box, classes.tooltipContent)}>
                            <DeveloperProfileSpanDetails span={tooltipSpan} allSpans={Object.values(profile.spans)} />
                        </div>
                        <span className={cx(toolTipClasses().nub, classes.tooltipNub, "isUp")}></span>
                    </div>
                )}
            </div>
        </div>
    );
}

function useFilteredFlameNodes(nodes: MyFlameChartNode[]) {
    const { filteredSpanTypes } = useDeveloperProfile();
    const filtered = useMemo(() => {
        function filterNodes(nodes: MyFlameChartNode[]): MyFlameChartNode[] {
            if (filteredSpanTypes == null) {
                return nodes;
            }
            let result: MyFlameChartNode[] = [];
            for (let node of nodes) {
                if (filteredSpanTypes.includes(node.raw.type)) {
                    let newNode = {
                        ...node,
                        children: filterNodes(node.children),
                    };
                    result.push(newNode);
                } else {
                    // Just get the filtered children
                    result = result.concat(filterNodes(node.children));
                }
            }
            return result;
        }
        return filterNodes(nodes);
    }, [nodes, filteredSpanTypes]);
    return filtered;
}

function spanAsFlameNode(
    spanUuid: string,
    spans: IDeveloperProfileDetails["profile"]["spans"],
): MyFlameChartNode | null {
    const span = spans[spanUuid] ?? null;
    if (!span) {
        return null;
    }

    const childSpans = Object.values(spans).filter((s) => s.parentUuid === span.uuid);
    const childNodes = childSpans.map((s) => spanAsFlameNode(s.uuid, spans)).filter(notEmpty);

    return {
        name: getDeveloperProfileSpanTitle(span),
        type: span.type,
        start: span.startMs,
        duration: span.elapsedMs,
        children: childNodes,
        raw: span,
    };
}
