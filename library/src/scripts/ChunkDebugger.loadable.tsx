/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { css, cx } from "@emotion/css";
import type {
    IVanillaManifestAsset,
    IVanillaManifestChunk,
    IVanillaManifestItem,
    IVanillaViteManifest,
} from "../../../build/VanillaManifestPlugin";
import { createContext, useCallback, useContext, useEffect, useState } from "react";
import { useQuery } from "@tanstack/react-query";
import { assetUrl } from "@library/utility/appUtils";
import { QueryLoader } from "@library/loaders/QueryLoader";
import Frame from "@library/layout/frame/Frame";
import FrameHeader from "@library/layout/frame/FrameHeader";
import FrameBody from "@library/layout/frame/FrameBody";
import Message from "@library/messages/Message";
import { shadowHelper } from "@library/styles/shadowHelpers";
import { Table } from "@dashboard/components/Table";
import { HumanFileSize } from "@library/utility/fileUtils";
import ScrollLock from "react-scrolllock";
import Button from "@library/forms/Button";
import { singleBorder } from "@library/styles/styleHelpersBorders";
import { TableAccordion } from "@dashboard/components/TableAccordion";
import { DataList } from "@library/dataLists/DataList";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { extendItemContainer } from "@library/styles/styleHelpersSpacing";
import { inputClasses } from "packages/vanilla-ui/src/forms/shared/input.styles";
import InputTextBlock from "@library/forms/InputTextBlock";
import SelectBox, { type ISelectBoxItem } from "@library/forms/select/SelectBox";
import { Row } from "@library/layout/Row";
import { spaceshipCompare, uuidv4 } from "@vanilla/utils";
import { Tabs } from "@library/sectioning/Tabs";
import { TabsTypes } from "@library/sectioning/TabsTypes";

interface IProps {}

export default function ChunkDebugger(props: IProps) {
    const [loadedChunkIDs, setLoadedChunkIDs] = useState<string[]>(
        Array.isArray(window.__VANILLA_CHUNK_DEBUGGER__) ? [...window.__VANILLA_CHUNK_DEBUGGER__] : [],
    );

    // Set up the proxy on mount
    useEffect(() => {
        // Initialize if not already initialized
        if (!Array.isArray(window.__VANILLA_CHUNK_DEBUGGER__)) {
            window.__VANILLA_CHUNK_DEBUGGER__ = [];
        }

        const originalArray = window.__VANILLA_CHUNK_DEBUGGER__;
        const originalPush = originalArray.push;

        // Override the push method
        originalArray.push = function (...items: string[]) {
            // Call the original push method
            const result = originalPush.apply(originalArray, items);
            // Update our state with the new array
            setLoadedChunkIDs([...originalArray]);
            return result;
        };

        // Cleanup function to restore original push
        return () => {
            window.__VANILLA_CHUNK_DEBUGGER__!.push = originalPush;
        };
    }, []);

    const manifestQuery = useQuery({
        queryKey: ["vanilla-manifest"],
        queryFn: async () => {
            const url = assetUrl(
                `/dist/v2/${window.__VANILLA_BUILD_SECTION__}/.vite/manifest.json?cacheBuster=${uuidv4()}`,
            );
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error("Failed to load manifest");
            }
            const manifest: IVanillaViteManifest = await response.json();
            return manifest;
        },
    });

    const [filter, setFilter] = useState("");
    const [expandedChunkIDs, setExpandedChunkIDs] = useState<string[]>([]);

    const setChunkIDExpanded = useCallback((chunkID: string, expanded: boolean) => {
        setExpandedChunkIDs((prev) => {
            if (expanded) {
                return [...prev, chunkID];
            } else {
                return prev.filter((id) => id !== chunkID);
            }
        });
    }, []);
    const [currentSortOption, setCurrentSortOption] = useState(ChunkSortOptions[0]);

    return (
        <QueryLoader
            query={manifestQuery}
            loader={() => null}
            error={(err) => <Message isFixed={true} error={err} />}
            success={(manifest) => {
                return (
                    <ChunkContext.Provider
                        value={{
                            filter,
                            setFilter,
                            currentSortOption,
                            setCurrentSortOption,
                            expandedChunkIDs,
                            setChunkIDExpanded,
                            manifest,
                            loadedChunkIDs,
                        }}
                    >
                        <ChunkDebuggerImpl />
                    </ChunkContext.Provider>
                );
            }}
        />
    );
}

const ChunkSortOptions: ISelectBoxItem[] = [
    { value: "size", name: "Size" },
    { value: "load", name: "Load Order" },
    { value: "name", name: "Name" },
];

type ISortableItem =
    | IVanillaManifestItem
    | {
          moduleId: string;
          sizeBytes: number;
      }
    | string;

function getItemLabel(sortableItem: ISortableItem): string {
    if (typeof sortableItem === "string") {
        return sortableItem;
    }

    if ("file" in sortableItem) {
        return sortableItem.file;
    }

    return sortableItem.moduleId;
}

function filterItems<T extends ISortableItem>(items: T[], filter: string): T[] {
    if (!filter) {
        return items;
    }

    return items.filter((item) => {
        const label = getItemLabel(item);
        const isOwnMatch = label.toLocaleLowerCase().includes(filter.toLocaleLowerCase());
        if (isOwnMatch) {
            return true;
        }

        if (typeof item === "object" && "type" in item && item.type === "chunk") {
            return item.modules.some((module) =>
                module.moduleId.toLocaleLowerCase().includes(filter.toLocaleLowerCase()),
            );
        } else {
            return false;
        }
    });
}

function sortItems<T extends ISortableItem>(items: T[], sortValue: string): T[] {
    if (sortValue === "load") {
        return items;
    }

    return items.sort((a, b) => {
        if (sortValue === "size") {
            if (typeof a === "string") {
                return -1;
            }

            if (typeof b === "string") {
                return 1;
            }

            return b.sizeBytes - a.sizeBytes;
        } else {
            const aName = getItemLabel(a);
            const bName = getItemLabel(b);
            return spaceshipCompare(aName, bName);
        }
    });
}

const ChunkContext = createContext<{
    filter: string;
    setFilter: (filter: string) => void;
    currentSortOption: ISelectBoxItem;
    setCurrentSortOption: (sort: ISelectBoxItem) => void;
    expandedChunkIDs: string[];
    setChunkIDExpanded: (chunkID: string, expanded: boolean) => void;
    manifest: IVanillaViteManifest;
    loadedChunkIDs: string[];
}>({} as any);

function useChunkContext() {
    return useContext(ChunkContext);
}

function ChunkDebuggerImpl() {
    const {
        loadedChunkIDs,
        manifest,
        filter,
        setFilter,
        currentSortOption,
        setCurrentSortOption,
        expandedChunkIDs,
        setChunkIDExpanded,
    } = useChunkContext();
    const [isExpanded, setIsExpanded] = useState(false);

    const lowerCaseValue = filter.toLocaleLowerCase();

    let loadedChunks = loadedChunkIDs.map((chunkID) => {
        const foundChunk = manifest[chunkID] ?? null;
        if (!foundChunk) {
            return chunkID;
        }

        return foundChunk;
    });

    loadedChunks = filterItems(loadedChunks, lowerCaseValue);
    loadedChunks = sortItems(loadedChunks, currentSortOption.value);

    return (
        <div>
            {<div className={cx(classes.scrim, isExpanded)}></div>}
            <div className={cx(classes.root, { isExpanded })}>
                <ScrollLock isActive={isExpanded}>
                    <div style={{ height: "100%" }}>
                        <Frame
                            header={
                                <FrameHeader
                                    className={classes.frameHeader}
                                    title={"Chunk Debugger"}
                                    onClick={() => {
                                        setIsExpanded(!isExpanded);
                                    }}
                                >
                                    <Button
                                        buttonType={ButtonTypes.TEXT_PRIMARY}
                                        onClick={() => {
                                            setIsExpanded(!isExpanded);
                                        }}
                                    >
                                        {isExpanded ? "Collapse" : "Expand"}
                                    </Button>
                                </FrameHeader>
                            }
                            body={
                                <FrameBody hasVerticalPadding={true}>
                                    <Row align={"center"} gap={24} className={classes.filters}>
                                        <DataCard label={"# Chunks Loaded"}>{loadedChunks.length}</DataCard>
                                        <DataCard label={"# Modules Loaded"}>
                                            {loadedChunks.reduce((acc, chunk) => {
                                                if (typeof chunk === "string") {
                                                    return acc;
                                                }
                                                if (chunk.type !== "chunk") {
                                                    return acc;
                                                }

                                                return acc + chunk.modules.length;
                                            }, 0)}
                                        </DataCard>
                                        <DataCard label={"Total Size"}>
                                            <HumanFileSize
                                                numBytes={loadedChunks.reduce((acc, chunk) => {
                                                    if (typeof chunk === "string") {
                                                        return acc;
                                                    }

                                                    return acc + chunk.sizeBytes;
                                                }, 0)}
                                            />
                                        </DataCard>
                                    </Row>
                                    <Row align={"center"} gap={24} className={classes.filters}>
                                        <InputTextBlock
                                            className={classes.input}
                                            inputProps={{
                                                value: filter,
                                                onChange: (e) => setFilter(e.target.value),
                                                placeholder: "Filter chunks...",
                                            }}
                                        />
                                        <label className={classes.sort}>
                                            <span>Sort:</span>
                                            <SelectBox
                                                options={ChunkSortOptions}
                                                value={currentSortOption}
                                                onChange={setCurrentSortOption}
                                            />
                                        </label>
                                    </Row>
                                    <div>
                                        <div className={classes.item}>
                                            <div className={cx(classes.itemRow, "isHeader")}>
                                                <strong className={classes.itemTitle}>Chunkname</strong>
                                                <strong className={classes.itemSize}>Size</strong>
                                                <strong className={classes.itemNumber}># Modules</strong>
                                                <strong className={classes.itemNumber}># Importers</strong>
                                            </div>
                                        </div>
                                        {loadedChunks.map((chunk) => {
                                            if (typeof chunk === "string") {
                                                return (
                                                    <div className={classes.itemRow} key={chunk}>
                                                        {chunk} - Manifest not found
                                                    </div>
                                                );
                                            }

                                            const importers = Object.values(manifest).filter((item) => {
                                                if (item.type !== "chunk") {
                                                    return false;
                                                }
                                                return item.imports.includes(chunk.file);
                                            });

                                            return (
                                                <div key={chunk.file} className={classes.item}>
                                                    <TableAccordion
                                                        isExpanded={expandedChunkIDs.includes(chunk.file)}
                                                        onExpandChange={(expanded) =>
                                                            setChunkIDExpanded(chunk.file, expanded)
                                                        }
                                                        lazy={true}
                                                        toggleButtonContent={
                                                            <ModuleItem
                                                                name={chunk.file}
                                                                sizeBytes={chunk.sizeBytes}
                                                                countModules={
                                                                    chunk.type === "chunk" ? chunk.modules.length : 0
                                                                }
                                                                countImporters={importers.length}
                                                            />
                                                        }
                                                    >
                                                        {chunk.type === "chunk" && <ChunkMeta chunk={chunk} />}
                                                    </TableAccordion>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </FrameBody>
                            }
                        ></Frame>
                    </div>
                </ScrollLock>
            </div>
        </div>
    );
}

function ChunkMeta(props: { chunk: IVanillaManifestChunk }) {
    const { loadedChunkIDs, manifest, filter, currentSortOption, setFilter, setChunkIDExpanded } = useChunkContext();
    const sort = currentSortOption.value;

    const { chunk } = props;

    let modules = filterItems(chunk.modules, filter);
    modules = sortItems(chunk.modules, sort);

    const importedByAll = Object.values(manifest).filter((item) => {
        if (item.type !== "chunk") {
            return false;
        }
        return item.imports.includes(chunk.file);
    });
    const importedByLoaded = importedByAll.filter((item) => loadedChunkIDs.includes(item.file));

    return (
        <div className={classes.chunkMeta}>
            <Tabs
                includeVerticalPadding={false}
                tabType={TabsTypes.BROWSE}
                data={[
                    {
                        label: "Modules",
                        contents: (
                            <>
                                <div>
                                    {modules.map((module) => {
                                        return (
                                            <ModuleItem
                                                isSubModule
                                                name={module.moduleId}
                                                sizeBytes={module.sizeBytes}
                                                key={module.moduleId}
                                            />
                                        );
                                    })}
                                </div>
                            </>
                        ),
                    },
                    {
                        label: "Imported By (Loaded)",
                        contents: (
                            <>
                                <div>
                                    {importedByLoaded.map((chunk) => {
                                        return (
                                            <ModuleItem
                                                isSubModule
                                                name={chunk.file}
                                                sizeBytes={chunk.sizeBytes}
                                                key={chunk.file}
                                                onViewChunk={() => {
                                                    setFilter(chunk.file);
                                                    setChunkIDExpanded(chunk.file, true);
                                                }}
                                            />
                                        );
                                    })}
                                </div>
                            </>
                        ),
                    },
                    {
                        label: "Imported By (All)",
                        contents: (
                            <>
                                <div>
                                    {importedByAll.map((chunk) => {
                                        return (
                                            <ModuleItem
                                                isSubModule
                                                name={chunk.file}
                                                sizeBytes={chunk.sizeBytes}
                                                key={chunk.file}
                                            />
                                        );
                                    })}
                                </div>
                            </>
                        ),
                    },
                ]}
            />
        </div>
    );
}

function ModuleItem(props: {
    name: string;
    sizeBytes: number;
    countModules?: number;
    countImporters?: number;
    onViewChunk?: () => void;
    isHeader?: boolean;
    isSubModule?: boolean;
}) {
    const { filter } = useChunkContext();
    const { name, sizeBytes, isHeader, isSubModule } = props;
    const [didMatch, highlightedName] = getHighlightedText(name, filter ?? "");

    const didMatchSubModule = filter && !didMatch;

    return (
        <div className={cx(classes.itemRow, { isHeader, isSubModule })}>
            <span className={cx(classes.itemTitle)} title={name}>
                {highlightedName}
            </span>
            <span className={classes.itemSize}>
                <HumanFileSize numBytes={sizeBytes} />
            </span>
            {props.countModules !== undefined && (
                <span className={cx(classes.itemNumber, didMatchSubModule && classes.highlight)}>
                    {props.countModules}
                </span>
            )}
            {props.countImporters !== undefined && <span className={classes.itemNumber}>{props.countImporters}</span>}
            {props.onViewChunk && (
                <Button
                    buttonType={ButtonTypes.TEXT}
                    onClick={() => {
                        props.onViewChunk?.();
                    }}
                >
                    View Chunk
                </Button>
            )}
        </div>
    );
}

function getHighlightedText(text: string, highlight: string) {
    // Split text on highlight term, include term itself into parts, ignore case
    const parts = text.split(new RegExp(`(${highlight})`, "gi"));

    let didMatch = parts.length > 1 || text.toLowerCase() === highlight.toLowerCase();
    parts.forEach((part, i) => {
        if (part.toLowerCase() === highlight.toLowerCase()) {
            didMatch = true;
        }
    });
    return [
        didMatch,
        <>
            {parts.map((part, i) =>
                part.toLowerCase() === highlight.toLowerCase() ? (
                    <span className={classes.highlight} key={i}>
                        {part}
                    </span>
                ) : (
                    <span key={i}>{part}</span>
                ),
            )}
        </>,
    ];
}

function DataCard(props: { label: React.ReactNode; children: React.ReactNode }) {
    return (
        <div className={classes.dataCard}>
            <div className={classes.dataCardLabel}>{props.label}</div>
            <div className={classes.dataCardContent}>{props.children}</div>
        </div>
    );
}

const classes = {
    root: css({
        ...shadowHelper().modal(),
        border: singleBorder(),
        position: "fixed",
        bottom: 0,
        left: 0,
        right: 0,
        borderRadius: 6,
        background: "#fff",
        height: 48,
        overflow: "hidden",
        transition: "300ms height ease-in-out",

        "&.isExpanded, & .frame": {
            maxHeight: "calc(100vh - 32px)",
            height: "calc(100vh - 32px)",
        },
    }),
    scrim: css({
        position: "fixed",
        top: 0,
        bottom: 0,
        left: 0,
        right: 0,
        background: "rgba(0, 0, 0, 0.5)",
        opacity: 0,
        transition: "300ms opacity ease-in-out",
        pointerEvents: "none",

        "&.isExpanded": {
            opacity: 1,
            pointerEvents: "initial",
        },
    }),
    highlight: css({
        background: "#ff0",
    }),
    filters: css({
        marginBottom: 16,
    }),
    item: css({
        borderBottom: singleBorder(),
    }),
    frameHeader: css({
        minHeight: 48,
        cursor: "pointer",
    }),
    itemRow: css({
        display: "flex",
        alignItems: "center",
        gap: 16,

        "&.isHeader": {
            paddingLeft: 24,
            textTransform: "uppercase",
            paddingTop: 8,
            paddingBottom: 8,
        },

        "&.isSubModule": {
            ...extendItemContainer(8),
            padding: 8,
            borderBottom: singleBorder(),
            "&:last-child": {
                borderBottom: "none",
            },
        },
    }),
    itemTitle: css({
        display: "inline-block",
        width: 550,
        maxWidth: "70vw",
        overflow: "hidden",
        textOverflow: "ellipsis",
        whiteSpace: "nowrap",

        "&.isSubModule": {
            width: 550 - 12,
        },
    }),
    itemSize: css({
        width: 120,
    }),
    itemNumber: css({
        width: 120,
        textAlign: "center",
    }),
    chunkMeta: css({
        padding: "0 6px",
    }),
    input: css({
        width: 400,
        maxWidth: "100%",
    }),
    sort: css({
        display: "inline-flex",
        gap: 8,
        alignItems: "center",
    }),
    dataCard: css({
        padding: 16,
        border: singleBorder(),
        position: "relative",
        flex: 1,
        maxWidth: 240,
    }),
    dataCardLabel: css({
        fontWeight: "bold",
        background: "#fff",
        top: 0,
        left: 16,
        position: "absolute",
        transform: "translateY(-50%)",
        padding: "0 8px",
        display: "inline-block",
    }),
    dataCardContent: css({
        display: "flex",
        alignItems: "center",
        padding: "0 8px",
    }),
};
