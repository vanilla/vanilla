/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    IDeveloperProfileCacheReadSpan,
    IDeveloperProfileCacheWriteSpan,
    IDeveloperProfileDbSpan,
    IDeveloperProfileSpan,
} from "@dashboard/developer/profileViewer/DeveloperProfile.types";
import { getDeveloperProfileSpanTitle } from "@dashboard/developer/profileViewer/DeveloperProfiles.metas";
import { css } from "@emotion/css";
import { highlightTextSync, highlightText } from "@library/content/code";
import { userContentClasses } from "@library/content/UserContent.styles";
import { DataList } from "@library/dataLists/DataList";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { CloseIcon } from "@library/icons/common";
import { PageHeadingBox } from "@library/layout/PageHeadingBox";
import { MetaIcon, MetaItem, Metas } from "@library/metas/Metas";
import { Tag } from "@library/metas/Tags";
import { TagPreset } from "@library/metas/Tags.variables";
import { TokenItem } from "@library/metas/TokenItem";
import { useIsMounted } from "@vanilla/react-utils";
import React, { isValidElement, useEffect, useRef, useState } from "react";

export function DeveloperProfileSpanDetails(props: {
    span: IDeveloperProfileSpan;
    allSpans: IDeveloperProfileSpan[];
    fullSize?: boolean;
    onClose?: () => void;
}) {
    const { span, allSpans, fullSize } = props;
    const title = getDeveloperProfileSpanTitle(props.span);
    const childSpans = allSpans.filter((s) => s.parentUuid === span.uuid);

    const selfTime = span.elapsedMs - childSpans.reduce((acc, s) => acc + s.elapsedMs, 0);
    const selfRef = useRef<HTMLDivElement | null>(null);

    return (
        <div ref={selfRef}>
            <PageHeadingBox
                title={title}
                depth={props.fullSize ? 2 : 4}
                actions={
                    props.onClose && (
                        <Button buttonType={ButtonTypes.ICON} onClick={props.onClose}>
                            <CloseIcon />
                        </Button>
                    )
                }
                description={
                    <>
                        <Metas>
                            <MetaIcon icon="meta-time" className={classes.metaIcon}>
                                {span.elapsedMs.toFixed(2)}ms {`(self ${selfTime.toFixed(2)}ms)`}
                            </MetaIcon>
                            <MetaItem>Started {span.startMs}ms</MetaItem>
                            {title !== span.type && (
                                <MetaItem>
                                    <Tag preset={TagPreset.GREYSCALE}>{span.type}</Tag>
                                </MetaItem>
                            )}
                        </Metas>
                    </>
                }
            />
            <DeveloperProfileSpanExtraContent span={span} truncate={!props.fullSize} />
        </div>
    );
}

function DeveloperProfileSpanExtraContent(props: { span: IDeveloperProfileSpan; truncate?: boolean }) {
    const truncate = props.truncate ?? true;
    switch (props.span.type) {
        case "dbRead":
        case "dbWrite":
            return <DbSpanExtraContent span={props.span as IDeveloperProfileDbSpan} truncate={truncate} />;
        case "cacheWrite":
            return <CacheWriteContent span={props.span as IDeveloperProfileCacheWriteSpan} truncate={truncate} />;
        case "cacheRead":
            return <CacheReadContent span={props.span as IDeveloperProfileCacheReadSpan} truncate={truncate} />;
        default:
            return <GenericData data={props.span.data} truncateArrays={truncate} />;
    }
}

function DbSpanExtraContent(props: { span: IDeveloperProfileDbSpan; truncate?: boolean }) {
    return (
        <div>
            {props.truncate ? (
                <>
                    <h4>Query</h4>
                    <HighlightedCode code={props.span.data.query} />
                    <GenericData data={props.span.data.params} truncateArrays />
                </>
            ) : (
                <GenericData
                    data={{
                        Query: <HighlightedCode code={props.span.data.query} />,
                        ...props.span.data.params,
                    }}
                    truncateArrays={false}
                />
            )}
        </div>
    );
}

function HighlightedCode(props: { code: string }) {
    const [html, setHtml] = useState(highlightTextSync(props.code) ?? "");
    const isMounted = useIsMounted();

    useEffect(() => {
        highlightText(props.code).then((highlighted) => {
            if (isMounted()) {
                setHtml(highlighted);
            }
        });
    }, [props.code]);

    return (
        <div className={userContentClasses().root}>
            <code
                className="code codeBlock codeBlockWrapped"
                dangerouslySetInnerHTML={{
                    __html: html,
                }}
            ></code>
        </div>
    );
}

function CacheWriteContent(props: { span: IDeveloperProfileCacheWriteSpan; truncate: boolean }) {
    return (
        <div>
            {props.truncate ? (
                <CacheKeys keys={props.span.data.keys} truncate={props.truncate} />
            ) : (
                <GenericData
                    data={{
                        Keys: props.span.data.keys,
                    }}
                    truncateArrays={false}
                />
            )}
        </div>
    );
}

function CacheKeys(props: { keys: string[]; truncate?: boolean }) {
    let keys: React.ReactNode;
    if (props.truncate && props.keys.length > 4) {
        keys = (
            <>
                {props.keys.slice(0, 3).map((key) => (
                    <TokenItem key={key}>{key}</TokenItem>
                ))}
                <div>+ {props.keys.length - 3} more</div>
            </>
        );
    } else {
        keys = (
            <>
                {props.keys.map((key) => (
                    <TokenItem key={key}>{key}</TokenItem>
                ))}
            </>
        );
    }
    return (
        <div>
            <h4>Keys</h4>
            <div className={classes.keys}>{keys}</div>
        </div>
    );
}

function CacheReadContent(props: { span: IDeveloperProfileCacheReadSpan; truncate: boolean }) {
    return (
        <div>
            {props.truncate ? (
                <>
                    <GenericData
                        data={{
                            "Hit Count": props.span.data.hitCount,
                        }}
                        truncateArrays
                    />
                    <CacheKeys keys={props.span.data.keys} truncate={props.truncate} />
                </>
            ) : (
                <>
                    <GenericData
                        truncateArrays={false}
                        data={{
                            "Hit Count": props.span.data.hitCount,
                            Keys: props.span.data.keys,
                        }}
                    />
                </>
            )}
        </div>
    );
}

function GenericData(props: { data: Record<string, any>; truncateArrays: boolean }) {
    if (Object.entries(props.data).length === 0) {
        return <div>No addition details.</div>;
    }

    return (
        <DataList
            caption="Data"
            colgroups={["140px", "calc(100% - 140px)"]}
            data={Object.entries(props.data).map(([key, value]) => {
                let finalValue = value;
                if (Array.isArray(value) && props.truncateArrays && value.length > 4) {
                    finalValue = [...value.slice(0, 3), `+ ${value.length - 4} more`];
                } else if (
                    finalValue &&
                    typeof finalValue === "object" &&
                    !Array.isArray(finalValue) &&
                    !isValidElement(finalValue)
                ) {
                    finalValue = <HighlightedCode code={JSON.stringify(value)} />;
                }

                return {
                    key,
                    value: finalValue,
                };
            })}
        />
    );
}

const classes = {
    metaIcon: css({
        marginLeft: -4,
    }),
    keys: css({
        display: "flex",
        flexWrap: "wrap",
        gap: 4,
    }),
};
