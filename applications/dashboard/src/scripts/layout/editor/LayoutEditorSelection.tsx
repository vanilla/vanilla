/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { LayoutEditorContents, LayoutEditorPath } from "@dashboard/layout/editor/LayoutEditorContents";
import { ILayoutEditorPath, ILayoutEditorWidgetPath } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import clamp from "lodash/clamp";

export enum LayoutEditorDirection {
    LEFT,
    RIGHT,
    UP,
    DOWN,
}

export enum LayoutEditorSelectionMode {
    SECTION = "section",
    SECTION_ADD = "section_add",
    WIDGET = "widget",
    NONE = "none",
}

/**
 * Class holding state of the editor selection.
 *
 * - Immutable.
 */
export class LayoutEditorSelectionState {
    protected backedUpState: LayoutEditorSelectionState | null = null;

    /**
     * Constructor.
     */
    public constructor(
        protected path: ILayoutEditorPath = LayoutEditorPath.section(0),
        protected mode: LayoutEditorSelectionMode = LayoutEditorSelectionMode.NONE,
        public onUpdate?: (newSelection: LayoutEditorSelectionState) => void,
    ) {}

    /**
     * Get the current path.
     */
    public getPath(): ILayoutEditorPath {
        return this.path;
    }

    /**
     * Get the current mode.
     */
    public getMode(): LayoutEditorSelectionMode {
        return this.mode;
    }

    /**
     * Set the current path.
     */
    public setPath(path: ILayoutEditorPath): LayoutEditorSelectionState {
        const updated = new LayoutEditorSelectionState(path, this.mode, this.onUpdate);
        this.onUpdate?.(updated);
        return updated;
    }

    /**
     * Set the current mode.
     */
    public setMode(mode: LayoutEditorSelectionMode): LayoutEditorSelectionState {
        const updated = new LayoutEditorSelectionState(this.path, mode, this.onUpdate);
        this.onUpdate?.(updated);
        return updated;
    }

    /**
     * Apply contents to the state to allow for more utility methods.
     */
    public withContents(contents: LayoutEditorContents): LayoutEditorSelection {
        const selection = new LayoutEditorSelection(contents, this);
        selection.backedUpState = this.backedUpState;
        return selection;
    }

    /**
     * Stash our current state. Useful for if the editor loses focus.
     */
    public stashState(): LayoutEditorSelectionState {
        const newState = new LayoutEditorSelectionState(
            LayoutEditorPath.section(0),
            LayoutEditorSelectionMode.NONE,
            this.onUpdate,
        );
        newState.backedUpState = this;
        this.onUpdate?.(newState);
        return newState;
    }

    /**
     * Restore our stashed state if we have one.
     */
    public restoreState(): LayoutEditorSelectionState {
        if (!this.backedUpState) {
            return this;
        }

        const newState = new LayoutEditorSelectionState(
            this.backedUpState.path,
            this.backedUpState.mode,
            this.onUpdate,
        );

        this.onUpdate?.(newState);
        return newState;
    }
}

/**
 * Class holding a layout selection and a reference to the underlying contents.
 *
 * Offers much more utility than the bare state.
 */
export class LayoutEditorSelection extends LayoutEditorSelectionState {
    /**
     * Constructor.
     */
    public constructor(private contents: LayoutEditorContents, private state: LayoutEditorSelectionState) {
        super(state.getPath(), state.getMode(), state.onUpdate);
    }

    /**
     * Move the current selection directionally.
     *
     * - This is aware of the current mode.
     *
     * @param direction The direction to move.
     */
    public moveSelectionInDirection(direction: LayoutEditorDirection): LayoutEditorSelectionState {
        switch (this.getMode()) {
            case LayoutEditorSelectionMode.SECTION:
            case LayoutEditorSelectionMode.SECTION_ADD:
                return this.moveSectionSelectionInDirection(direction);
                break;
            case LayoutEditorSelectionMode.WIDGET:
                return this.moveWidgetSelectionInDirection(direction);
                break;
        }

        return this.state;
    }

    /**
     * Move the selection to a particular path and mode.
     *
     * @param newPath The path to move to.
     * @param newMode The mode to use.
     */
    public moveSelectionTo(newPath: ILayoutEditorPath, newMode: LayoutEditorSelectionMode): LayoutEditorSelectionState {
        let newState = this.setPath(newPath).setMode(newMode);

        if (newMode === LayoutEditorSelectionMode.WIDGET && !LayoutEditorPath.isWidgetPath(newPath)) {
            // If we are moving into the widget mode but only have a section path
            // Select the first widget in that section.

            const sectionInfo = this.contents.getSectionInfo(newPath);
            if (!sectionInfo) {
                return newState;
            }

            newState = newState.setPath(LayoutEditorPath.widget(newPath.sectionIndex, sectionInfo.regionNames[0], 0));
        }
        return newState;
    }

    /**
     * Get the next widget in a particular direction.
     *
     * @param fromPath The current path.
     * @param direction The direction to move in.
     * @param treatAddAsWidget Set to true if we should treat the "add widget" button as a valid path.
     */
    public getWidgetPathInDirection(
        fromPath: ILayoutEditorWidgetPath,
        direction: LayoutEditorDirection,
        treatAddAsWidget = false,
    ): ILayoutEditorWidgetPath | null {
        const { contents } = this;

        switch (direction) {
            case LayoutEditorDirection.DOWN: {
                let newPath = LayoutEditorPath.widget(
                    fromPath.sectionIndex,
                    fromPath.sectionRegion,
                    fromPath.sectionRegionIndex + 1,
                );

                // This seems a little sketchy. Could probably use a few more tests.
                let maxRegionIndex = treatAddAsWidget
                    ? contents.getMaxRegionIndex(newPath)
                    : contents.getRegion(newPath)!.length - 1;

                if (newPath.sectionRegionIndex > maxRegionIndex) {
                    // We need to move into next section.
                    newPath.sectionIndex += 1;
                    const nextSection = contents.getSection(newPath);
                    const nextSectionInfo = contents.getSectionInfo(newPath);
                    if (!nextSection || !nextSectionInfo) {
                        // Nowhere to move.
                        return null;
                    }

                    newPath = contents.getValidPath(newPath, nextSectionInfo, fromPath);
                    newPath.sectionRegionIndex = 0;
                }
                return newPath;
            }
            case LayoutEditorDirection.UP: {
                let newPath = LayoutEditorPath.widget(
                    fromPath.sectionIndex,
                    fromPath.sectionRegion,
                    fromPath.sectionRegionIndex - 1,
                );
                if (newPath.sectionRegionIndex < 0) {
                    newPath.sectionIndex = newPath.sectionIndex - 1;
                    // We need to move into the previous section.
                    const previousSection = contents.getSection(newPath);
                    const previousSectionInfo = contents.getSectionInfo(newPath);
                    if (!previousSection || !previousSectionInfo) {
                        // There is no previous section.
                        return null;
                    }
                    newPath = contents.getValidPath(newPath, previousSectionInfo, fromPath);

                    let maxRegionIndex = treatAddAsWidget
                        ? contents.getMaxRegionIndex(newPath)
                        : (contents.getRegion(newPath) ?? []).length;
                    newPath.sectionRegionIndex = maxRegionIndex;
                }

                return newPath;
            }
            case LayoutEditorDirection.RIGHT: {
                const newPath = LayoutEditorPath.widget(
                    fromPath.sectionIndex,
                    fromPath.sectionRegion,
                    fromPath.sectionRegionIndex,
                );
                const sectionInfo = contents.getSectionInfo(newPath);
                if (!sectionInfo) {
                    return null;
                }
                const currentRegionIndex = sectionInfo.regionNames.indexOf(newPath.sectionRegion);
                const nextRegionName = sectionInfo.regionNames[currentRegionIndex + 1];
                if (!nextRegionName) {
                    // There is no region in this direction.
                    return null;
                }

                newPath.sectionRegion = nextRegionName;
                const maxIndex = contents.getMaxRegionIndex(newPath);
                if (newPath.sectionRegionIndex > maxIndex) {
                    newPath.sectionRegionIndex = maxIndex;
                }
                return newPath;
            }
            case LayoutEditorDirection.LEFT: {
                const newPath = LayoutEditorPath.widget(
                    fromPath.sectionIndex,
                    fromPath.sectionRegion,
                    fromPath.sectionRegionIndex,
                );
                const sectionInfo = contents.getSectionInfo(newPath);
                if (!sectionInfo) {
                    return null;
                }
                const currentRegionIndex = sectionInfo.regionNames.indexOf(newPath.sectionRegion);
                const prevRegionName = sectionInfo.regionNames[currentRegionIndex - 1];
                if (!prevRegionName) {
                    // There is no region in this direction.
                    return null;
                }

                newPath.sectionRegion = prevRegionName;
                const maxIndex = contents.getMaxRegionIndex(newPath);
                if (newPath.sectionRegionIndex > maxIndex) {
                    newPath.sectionRegionIndex = maxIndex;
                }
                return newPath;
            }
        }
    }

    /**
     * Move widget selection directionally.
     *
     * - There are many scenarios where we can't do this.
     *   In those cases this method will return the existing state.
     *
     * @param direction The direction to move in.
     */
    private moveWidgetSelectionInDirection(direction: LayoutEditorDirection): LayoutEditorSelectionState {
        if (this.mode !== LayoutEditorSelectionMode.WIDGET) {
            return this.state;
        }

        if (!LayoutEditorPath.isWidgetPath(this.path)) {
            return this.state;
        }

        const newPath = this.getWidgetPathInDirection(this.path, direction, true);
        if (newPath === null) {
            return this.state;
        }

        return this.setPath(newPath);
    }

    /**
     * Move section selection directionally.
     *
     * - There are many scenarios where we can't do this.
     *   In those cases this method will return the existing state.
     *
     * @param direction The direction to move in.
     */
    private moveSectionSelectionInDirection(direction: LayoutEditorDirection): LayoutEditorSelectionState {
        const { contents } = this;
        if (!contents) {
            return this.state;
        }

        if (this.path == null) {
            return this.state.setPath(LayoutEditorPath.section(0)).setMode(LayoutEditorSelectionMode.SECTION_ADD);
        }

        const isModeSectionAdd = this.mode === LayoutEditorSelectionMode.SECTION_ADD;
        const isModeSection = this.mode === LayoutEditorSelectionMode.SECTION;
        const isFirstSection = this.path.sectionIndex === 0;
        const isLastSection = this.path.sectionIndex === contents.getSectionCount();

        const incrementSectionIndex = (increment: number): LayoutEditorSelectionState => {
            const result = clamp(this.path.sectionIndex + increment, 0, contents.getSectionCount());
            return this.state.setPath(LayoutEditorPath.section(result));
        };

        switch (direction) {
            case LayoutEditorDirection.DOWN: {
                if (isModeSectionAdd) {
                    if (isLastSection) {
                        // Already at the bottom.
                        return this.state;
                    }
                    return this.state.setMode(LayoutEditorSelectionMode.SECTION);
                } else if (isModeSection) {
                    return incrementSectionIndex(1).setMode(LayoutEditorSelectionMode.SECTION_ADD);
                }
                break;
            }
            case LayoutEditorDirection.UP: {
                if (isModeSectionAdd) {
                    if (isFirstSection) {
                        // Already at the top.
                        return this.state;
                    }
                    return incrementSectionIndex(-1).setMode(LayoutEditorSelectionMode.SECTION);
                } else if (isModeSection) {
                    return this.state.setMode(LayoutEditorSelectionMode.SECTION_ADD);
                }
                break;
            }
        }

        // Nothing changed.
        return this.state;
    }
}
