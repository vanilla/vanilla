/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorAssetUtils } from "@dashboard/layout/editor/LayoutEditorAssetUtils";
import { ILayoutSectionInfo, LayoutSectionInfos } from "@dashboard/layout/editor/LayoutSectionInfos";
import {
    IEditableLayoutSpec,
    IEditableLayoutWidget,
    IHydratedEditableLayoutSpec,
    IHydratedEditableLayoutWidget,
    IHydratedEditableWidgetProps,
    ILayoutCatalog,
    ILayoutEditorDestinationPath,
    ILayoutEditorWidgetPath,
    ILayoutEditorPath,
    IWidgetCatalog,
    ILayoutEditorSectionPath,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { isHydratedLayoutWidget } from "@library/features/Layout/LayoutRenderer.utils";
import { ArrayUtils } from "@vanilla/utils";
import produce from "immer";
import omit from "lodash/omit";

/**
 * Class representing the primary data structure of the layout editor
 *
 * - Hydrates the catalog.
 * - Can modify the catalog.
 * - Immutable.
 */
export class LayoutEditorContents {
    private readonly widgetCatalog: IWidgetCatalog;

    /**
     * Constructor.
     *
     * @param editSpec The raw editor spec (from /api/v2/layouts/:id/edit)
     * @param catalog The layout catalog data (from /api/v2/layouts/catalog)
     */
    public constructor(
        private editSpec: IEditableLayoutSpec,
        private catalog: ILayoutCatalog,
        private onModify?: (newContents: LayoutEditorContents) => void,
    ) {
        Object.freeze(editSpec);
        this.widgetCatalog = { ...this.catalog.widgets, ...this.catalog.sections, ...this.catalog.assets };
    }

    /**
     * Insert a new section at a specified index.
     *
     * @param index The index to insert at.
     * @param sectionSpec The section to add.
     *
     * @returns A new editor contents instance.
     */
    public insertSection = (index: number, sectionSpec: IEditableLayoutWidget): LayoutEditorContents => {
        const maxIndex = this.editSpec.layout.length;
        if (index > maxIndex) {
            throw new InvalidEditorPathError(
                `Cannot insert section at index '${index}'. Maximum valid index is '${maxIndex}'`,
            );
        }
        const modified = this.modifyLayout((draft) => {
            return ArrayUtils.insertAt(draft, sectionSpec, index);
        });
        return modified;
    };

    /**
     * Delete a section at the specified index. Will delete all widgets inside the section as well.
     *
     * @param index The index to delete at.
     *
     * @returns A new editor contents instance.
     */
    public deleteSection = (index: number): LayoutEditorContents => {
        const modified = this.modifyLayout((draft) => {
            if (!draft[index]) {
                throw new InvalidEditorPathError(
                    `Cannot delete section at index '${index}' because that index does not exist.`,
                );
            }
            return ArrayUtils.removeAt(draft, index);
        });
        return modified;
    };

    /**
     * Move a section from one index to another.
     *
     * @param sourceIndex The index to move from.
     * @param destIndex The index that will contain the original item after it is moved.
     *
     * @returns A new editor contents instance.
     */
    public moveSection = (sourcePath: ILayoutEditorPath, destPath: ILayoutEditorPath): LayoutEditorContents => {
        const sourceIndex = sourcePath.sectionIndex;
        const destIndex = destPath.sectionIndex;
        const modified = this.modifyLayout((draft) => {
            if (!draft[sourceIndex]) {
                throw new InvalidEditorPathError(
                    `Cannot move section from index '${sourceIndex}' because that index does not exist.`,
                );
            }
            if (!draft[destIndex]) {
                throw new InvalidEditorPathError(
                    `Cannot move section to index '${destIndex}' because that index does not exist.`,
                );
            }
            return ArrayUtils.move(draft, sourceIndex, destIndex);
        });
        return modified;
    };

    /**
     * Delete a widget at a specified path.
     *
     * @param path An editor paht.
     *
     * @returns A new editor contents instance.
     */
    public deleteWidget = (path: ILayoutEditorDestinationPath): LayoutEditorContents => {
        return this.modifyLayout((draft) => {
            const section = draft[path.sectionIndex];
            if (!section) {
                throw new InvalidEditorPathError(
                    `Cannot delete from section at index '${path.sectionIndex}' because that index does not exist.`,
                );
            }
            let region: any[] = section[path.sectionRegion] ?? [];
            const maxIndex = region.length;
            const regionIndex = path.sectionRegionIndex ?? 0;
            if (regionIndex > maxIndex) {
                throw new InvalidEditorPathError(
                    `Cannot delete widget from region ${path.sectionRegion} at index '${regionIndex}'. Maximum valid index is '${maxIndex}'`,
                );
            }
            section[path.sectionRegion] = ArrayUtils.removeAt(region, regionIndex);

            return draft;
        });
    };

    /**
     * Add a new widget at a specified path.
     *
     * @param destination The path the widget will be at after insertion.
     * @param widgetSpec The widget specification.
     *
     * @returns A new editor contents instance.
     */
    public insertWidget = (destination: ILayoutEditorPath, widgetSpec: IEditableLayoutWidget): LayoutEditorContents => {
        return this.modifyLayout((draft) => {
            LayoutEditorPath.assertDestinationPath(destination);
            const section = draft[destination.sectionIndex];
            if (!section) {
                throw new InvalidEditorPathError(
                    `Cannot insert into section at index '${destination.sectionIndex}' because that index does not exist.`,
                );
            }
            let region = section[destination.sectionRegion] ?? [];
            const maxIndex = region.length;
            const regionIndex = destination.sectionRegionIndex ?? maxIndex;
            if (regionIndex > maxIndex) {
                throw new InvalidEditorPathError(
                    `Cannot insert widget into region ${destination.sectionRegion} at index '${regionIndex}'. Maximum valid index is '${maxIndex}'`,
                );
            }
            section[destination.sectionRegion] = ArrayUtils.insertAt(region, widgetSpec, regionIndex);

            return draft;
        });
    };

    /**
     *  Update a widget at a specified path.
     *
     * @param destination The path the widget will be at after modification.
     * @param widgetSpec The widget specification.
     *
     * @returns A new editor contents instance.
     */
    public modifyWidget = (destination: ILayoutEditorPath, widgetSpec: IEditableLayoutWidget): LayoutEditorContents => {
        return this.modifyLayout((draft) => {
            LayoutEditorPath.assertDestinationPath(destination);
            const section = draft[destination.sectionIndex];
            let region = section[destination.sectionRegion] ?? [];
            region[destination.sectionRegionIndex ?? region.length] = widgetSpec;
            section[destination.sectionRegion] = region;

            return draft;
        });
    };

    /**
     * Move a widget from one path to another.
     *
     * @param sourcePath The original path of the widget.
     * @param destPath The path the widget will be at after moving.
     *
     * @returns A new editor contents instance.
     */
    public moveWidget = (sourcePath: ILayoutEditorPath, destPath: ILayoutEditorPath): LayoutEditorContents => {
        return this.modifyLayout((draft) => {
            LayoutEditorPath.assertWidgetPath(sourcePath);
            LayoutEditorPath.assertWidgetPath(destPath);
            const widgetRegion = draft[sourcePath.sectionIndex][sourcePath.sectionRegion] ?? null;
            if (!widgetRegion) {
                throw new InvalidEditorPathError(
                    `Cannot move from widgetPath ${sourcePath.sectionIndex}.${sourcePath.sectionRegion} because it doesn't exist.`,
                );
            }

            const destinationRegion = draft[destPath.sectionIndex][destPath.sectionRegion] ?? [];
            if (widgetRegion === destinationRegion) {
                // We are swapping within the same section
                draft[sourcePath.sectionIndex][sourcePath.sectionRegion] = ArrayUtils.move(
                    widgetRegion,
                    sourcePath.sectionRegionIndex,
                    destPath.sectionRegionIndex,
                );
            } else {
                const toMove = widgetRegion[sourcePath.sectionRegionIndex];
                if (toMove == null) {
                    throw new InvalidEditorPathError(
                        `Cannot move from widgetPath ${sourcePath.sectionIndex}.${sourcePath.sectionRegion}.${sourcePath.sectionRegionIndex} because it doesn't exist.`,
                    );
                }
                // We are swapping between regions
                draft[sourcePath.sectionIndex][sourcePath.sectionRegion] = ArrayUtils.removeAt(
                    widgetRegion,
                    sourcePath.sectionRegionIndex,
                );
                draft[destPath.sectionIndex][destPath.sectionRegion] = ArrayUtils.insertAt(
                    destinationRegion,
                    toMove,
                    destPath.sectionRegionIndex,
                );
            }

            return draft;
        });
    };

    /**
     * Modify a section at the specified index.
     *
     * @param index The index to modify at.
     *
     * @returns A new editor contents instance.
     */
    public modifySection = (index: number, newSpec: IEditableLayoutWidget): LayoutEditorContents => {
        return this.modifyLayout((draft) => {
            draft[index] = newSpec;
            return draft;
        });
    };

    /**
     * Internal utility for immutably modifying the edit spec and creating a new instance.
     *
     * @param callback A callback to modify the edit spec.
     *
     * @returns A new editor contents instance.
     */
    private modifyLayout = (
        callback: (draft: IEditableLayoutWidget[]) => IEditableLayoutWidget[],
    ): LayoutEditorContents => {
        const newLayout = produce(this.editSpec.layout, callback);
        const newContents = new LayoutEditorContents(
            {
                ...this.editSpec,
                layout: newLayout,
            },
            this.catalog,
            this.onModify,
        );

        this.onModify?.(newContents);

        return newContents;
    };

    // public canMoveWidget = (widget: IHydratedEditableLayoutWidget, possiblePath: ILayoutEditorParent): boolean => {
    //     // Lookup the section type of the parent.
    //     // Does that type of section support this type of widget.
    //     // Ask that section if supports this widget?
    //     return true;
    // };

    /**
     * Validate that our editor contents can be saved.
     * - Contains required assets
     * TODO: - No empty sections.
     */
    public validate = () => {
        // for now, validating if the required assets are in the layout, might add more validation options later
        return LayoutEditorAssetUtils.validateAssets({
            layout: this.editSpec.layout,
            layoutViewType: this.editSpec.layoutViewType,
        });
    };

    /**
     * Get the raw edit contents.
     */
    public getEditSpec = (): IEditableLayoutSpec => {
        return this.editSpec;
    };

    /**
     * Get the raw edit contents.
     */
    public getLayout = (): IEditableLayoutSpec["layout"] => {
        return this.editSpec.layout;
    };

    /**
     * Get count of sections in the layout.
     */
    public getSectionCount = (): number => {
        return this.getLayout().length;
    };

    /**
     * Get a section in a layout by it's path.
     */
    public getSection = (path: ILayoutEditorPath): IEditableLayoutWidget | null => {
        return this.getLayout()[path.sectionIndex] ?? null;
    };

    /**
     * Check if there is a full width section at a path.
     */
    public isSectionFullWidth = (path: ILayoutEditorPath): boolean => {
        const section = this.getSection(path);
        return section?.$hydrate === "react.section.full-width";
    };

    /**
     * Get a section in a layout by it's path.
     */
    public getRegion = (path: ILayoutEditorPath): IEditableLayoutWidget[] | null => {
        if (!path.sectionRegion) {
            return null;
        }
        return this.getLayout()[path.sectionIndex][path.sectionRegion] ?? null;
    };

    /**
     * Get a widget from our contents.
     *
     * @param path The path to the widget.
     *
     * @returns The widget definition or null.
     */
    public getWidget = (path: ILayoutEditorPath): IEditableLayoutWidget | null => {
        if (!LayoutEditorPath.isWidgetPath(path)) {
            return null;
        }
        const region = this.getRegion(path);
        return region?.[path.sectionRegionIndex] ?? null;
    };

    /**
     * Get the highest index in a region.
     */
    public getMaxRegionIndex = (path: ILayoutEditorPath): number => {
        const sectionInfo = this.getSectionInfo(path);
        const region = this.getRegion(path) ?? [];
        let maxRegionIndex = region.length - 1;
        if (!sectionInfo?.oneWidgetPerRegion || maxRegionIndex < 0) {
            // To account for the "add widget" button.
            maxRegionIndex += 1;
        }
        return maxRegionIndex;
    };

    /**
     * Get information about a section at a specific path.
     */
    public getSectionInfo = (path: ILayoutEditorPath): ILayoutSectionInfo | null => {
        const section = this.getSection(path);
        if (section === null) {
            return null;
        }

        const sectionInfo = LayoutSectionInfos[section.$hydrate] ?? null;
        // handles "isInverted" prop
        if (sectionInfo && section.isInverted) {
            return { ...sectionInfo, regionNames: sectionInfo.invertedRegionNames as string[] };
        }
        return sectionInfo;
    };

    /**
     * Return a valid path for the next section.
     */
    public getValidPath = (
        path: ILayoutEditorWidgetPath,
        newSectionInfo: ILayoutSectionInfo,
        fromPath: ILayoutEditorWidgetPath,
    ): ILayoutEditorWidgetPath => {
        const initialRegion = path.sectionRegion;

        // if there's the same region exists on the next section, move into that
        if (newSectionInfo.regionNames.includes(initialRegion)) {
            return path;
        }

        // if there's only one region to move to, move into that
        if (newSectionInfo.regionNames.length === 1) {
            return {
                ...path,
                sectionRegion: newSectionInfo.regionNames[0],
            };
        }

        // we came from a main region, so try moving into the same on the next section
        if (["children", "mainBottom", "middleBottom"].includes(initialRegion)) {
            if (newSectionInfo.regionNames.includes("mainBottom")) {
                return {
                    ...path,
                    sectionRegion: "mainBottom",
                };
            }
            if (newSectionInfo.regionNames.includes("middleBottom")) {
                return {
                    ...path,
                    sectionRegion: "middleBottom",
                };
            }
        }

        // we came from either a left or right region, so move using the region index
        const fromSectionInfo = this.getSectionInfo(fromPath);
        const fromRegionIndex = fromSectionInfo ? fromSectionInfo.regionNames.indexOf(initialRegion) : -1;
        if (fromSectionInfo && fromRegionIndex > -1) {
            // the index is at the last position but both sections have different regions length
            // move into the last index of the next section
            if (fromRegionIndex >= fromSectionInfo.regionNames.length - 1) {
                return {
                    ...path,
                    sectionRegion: newSectionInfo.regionNames[newSectionInfo.regionNames.length - 1],
                };
            }
        }

        // fallback, just move into the first region on the next section
        return {
            ...path,
            sectionRegion: newSectionInfo.regionNames[0],
        };
    };

    /**
     * Get hydrated editor contents.
     */
    public hydrate = (): IHydratedEditableLayoutSpec => {
        return this.hydrateInternal(this.editSpec);
    };

    /**
     * Resolve a raw layout schema with the specifications in the catalog
     */
    private hydrateInternal(editSpec: IEditableLayoutSpec): IHydratedEditableLayoutSpec {
        const layout = editSpec.layout.map((node, i) => {
            return this.hydrateNode(node, {
                sectionIndex: i,
            });
        });

        return {
            ...editSpec,
            layout,
        };
    }

    /**
     * Hydrate a node recursively from the layout catalog.
     *
     * @param node The node to hydrate.
     * @param nodePath The path to the node.
     * @returns The hydrated node.
     */
    private hydrateNode = (node: unknown | IHydratedEditableLayoutWidget, nodePath: Partial<ILayoutEditorPath>) => {
        if (node == null || typeof node !== "object") {
            return node;
        }
        // If its an array, we want to resolve all the entries
        if (Array.isArray(node)) {
            return node.map((subNode, i) =>
                this.hydrateNode(subNode, {
                    ...nodePath,
                    sectionRegionIndex: i,
                }),
            );
        }

        if (!isHydrateable(node)) {
            return node;
        }

        let componentName = this.widgetCatalog[node.$hydrate]?.$reactComponent;
        if (componentName == null) {
            // Re-use the hydrate name. This will turn into an error down the line as the widget will not be found.
            componentName = node.$hydrate;
        }

        const props = Object.fromEntries(
            Object.keys(omit(node, "$hydrate", "$middleware")).map((key) => [
                key,
                this.hydrateNode(node[key], {
                    ...nodePath,
                    sectionRegion: key,
                }),
            ]),
        );

        const extraProps: IHydratedEditableWidgetProps = {
            // TODO: Figure out where we could have a bad section here.
            $componentName: componentName,
            $editorPath: nodePath as ILayoutEditorPath,
            $hydrate: node.$hydrate,
        };

        return {
            $reactComponent: componentName,
            $reactProps: {
                ...props,
                ...extraProps,
            },
        };
    };
}
/**
 * Check if some node is a hydrateable node.
 */
function isHydrateable(node: unknown): node is IEditableLayoutWidget {
    if (typeof node !== "object") {
        return false;
    }

    if (node == null) {
        return false;
    }

    if (!("$hydrate" in node)) {
        return false;
    }

    const hydrateVal = node["$hydrate"];
    if (typeof hydrateVal !== "string") {
        return false;
    }

    if (!hydrateVal.startsWith("react")) {
        return false;
    }

    return true;
}

/**
 * Check if a node is an editable and hydrateable widget.
 *
 * @param node The node to check.
 */
export function isHydrateableEditableWidget(node: unknown): node is IHydratedEditableLayoutWidget {
    return isHydrateable(node) && isHydratedLayoutWidget(node);
}

/**
 * Error thrown when modifying editor contents.
 */
export class InvalidEditorPathError extends Error {}

/**
 * Helper class for declaring a layout path.
 */
export class LayoutEditorPath {
    public static section(sectionIndex: number): ILayoutEditorSectionPath {
        return {
            sectionIndex,
        };
    }

    public static destination(sectionIndex: number, sectionRegion: string): ILayoutEditorDestinationPath {
        return {
            sectionIndex,
            sectionRegion,
        };
    }

    public static widget(
        sectionIndex: number,
        sectionRegion: string,
        sectionRegionIndex: number,
    ): ILayoutEditorWidgetPath {
        return {
            sectionIndex,
            sectionRegion,
            sectionRegionIndex,
        };
    }

    public static isWidgetPath(path: ILayoutEditorPath): path is ILayoutEditorWidgetPath {
        return path.sectionRegion != null && path.sectionIndex != null;
    }

    public static assertWidgetPath(path: ILayoutEditorPath): asserts path is ILayoutEditorWidgetPath {
        if (!this.isWidgetPath(path)) {
            throw new InvalidEditorPathError("Expected a full editor path.");
        }
    }

    public static isDestinationPath(path: ILayoutEditorPath): path is ILayoutEditorDestinationPath {
        return path.sectionRegion != null;
    }

    public static assertDestinationPath(path: ILayoutEditorPath): asserts path is ILayoutEditorDestinationPath {
        if (!this.isWidgetPath(path)) {
            throw new InvalidEditorPathError("Expected a full editor path.");
        }
    }

    public static areSectionPathsEqual(pathA: ILayoutEditorPath | null, pathB: ILayoutEditorPath | null): boolean {
        return !!pathA && !!pathB && pathA.sectionIndex === pathB.sectionIndex;
    }

    public static areWidgetPathsEqual(pathA: ILayoutEditorPath | null, pathB: ILayoutEditorPath | null): boolean {
        return (
            !!pathA &&
            !!pathB &&
            this.isWidgetPath(pathA) &&
            this.isWidgetPath(pathB) &&
            pathA.sectionIndex === pathB.sectionIndex &&
            pathA.sectionRegion === pathB.sectionRegion &&
            pathA.sectionRegionIndex === pathB.sectionRegionIndex
        );
    }
}
