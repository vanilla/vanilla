/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

import React from "react";
import classNames from "classnames";
import { withEditor, IWithEditorProps } from "@rich-editor/components/context";
import MentionSuggestion, {
    IMentionProps,
    MentionSuggestionLoading,
    MentionSuggestionSpacer,
} from "./MentionSuggestion";
import { t } from "@dashboard/application";
import { getMentionRange } from "@rich-editor/quill/utility";
import ToolbarPositioner from "./ToolbarPositioner";
import Quill, { RangeStatic, DeltaStatic, Sources } from "quill/core";

interface IProps extends IWithEditorProps {
    mentionProps: Array<Partial<IMentionProps>>;
    matchedString: string;
    id: string;
    loaderID: string;
    isVisible: boolean;
    activeItemId: string | null;
    onItemClick: React.MouseEventHandler<any>;
    showLoader: boolean;
}

interface IState {
    flyoutWidth?: number | null;
    flyoutHeight?: number | null;
    selectionIndex: number | null;
    selectionLength: number | null;
}

class MentionSuggestionList extends React.PureComponent<IProps, IState> {
    public state = {
        flyoutWidth: null,
        flyoutHeight: null,
        selectionIndex: null,
        selectionLength: null,
    };
    private flyoutRef: React.RefObject<any> = React.createRef();
    private quill: Quill;

    constructor(props) {
        super(props);

        // Quill can directly on the class as it won't ever change in a single instance.
        this.quill = props.quill;
    }

    public render() {
        const {
            activeItemId,
            id,
            onItemClick,
            matchedString,
            mentionProps,
            loaderID,
            isVisible,
            showLoader,
        } = this.props;

        const hasResults = mentionProps.length > 0 || showLoader;
        const classes = classNames("atMentionList-items", "MenuItems");

        return (
            <ToolbarPositioner
                horizontalAlignment="start"
                verticalAlignment="below"
                flyoutWidth={this.state.flyoutWidth}
                flyoutHeight={this.state.flyoutHeight}
                isActive={isVisible}
                selectionIndex={this.state.selectionIndex}
                selectionLength={this.state.selectionLength}
            >
                {({ x, y }) => {
                    let style: React.CSSProperties = {
                        visibility: "hidden",
                        position: "absolute",
                        zIndex: -1,
                    };

                    if (x && y) {
                        style = {
                            position: "absolute",
                            top: y.position,
                            left: x.position,
                            zIndex: 1,
                            visibility: "visible",
                        };
                    }

                    const items = mentionProps.slice(0, 5).map(mentionProp => {
                        if (mentionProp.mentionData == null) {
                            return null;
                        }
                        const isActive = mentionProp.mentionData.domID === activeItemId;
                        return (
                            <MentionSuggestion
                                mentionData={mentionProp.mentionData}
                                key={mentionProp.mentionData.name}
                                onMouseEnter={mentionProp.onMouseEnter}
                                onClick={onItemClick}
                                isActive={isActive}
                                matchedString={matchedString}
                            />
                        );
                    });

                    if (showLoader) {
                        const loadingData = {
                            domID: this.props.loaderID,
                        };
                        const isActive = loadingData.domID === activeItemId;

                        items.push(
                            <MentionSuggestionLoading
                                loadingData={loadingData}
                                isActive={isActive}
                                key="Loading"
                                matchedString={matchedString}
                            />,
                        );
                    }

                    return (
                        <span style={style} className="atMentionList" ref={this.flyoutRef}>
                            <ul
                                id={id}
                                aria-label={t("@mention user suggestions")}
                                className={classes + (hasResults ? "" : " isHidden")}
                                role="listbox"
                            >
                                {hasResults && items}
                            </ul>
                            <div className={classes} style={{ visibility: "hidden" }}>
                                <MentionSuggestionSpacer />
                            </div>
                        </span>
                    );
                }}
            </ToolbarPositioner>
        );
    }

    public componentDidMount() {
        this.setState({
            flyoutWidth: this.flyoutRef.current ? this.flyoutRef.current.offsetWidth : null,
            flyoutHeight: this.flyoutRef.current ? this.flyoutRef.current.offsetHeight : null,
        });
        this.quill.on(Quill.events.EDITOR_CHANGE, this.handleEditorChange);
    }

    /**
     * Be sure to remove the listeners when the component unmounts.
     */
    public componentWillUnmount() {
        this.quill.off(Quill.events.EDITOR_CHANGE, this.handleEditorChange);
    }

    /**
     * Handle changes from the editor.
     */
    private handleEditorChange = (
        type: string,
        rangeOrDelta: RangeStatic | DeltaStatic,
        oldRangeOrDelta: RangeStatic | DeltaStatic,
        source: Sources,
    ) => {
        const isTextOrSelectionChange = type === Quill.events.SELECTION_CHANGE || type === Quill.events.TEXT_CHANGE;
        if (source === Quill.sources.SILENT || !isTextOrSelectionChange) {
            return;
        }
        const range = this.quill.getSelection();
        const selection: RangeStatic | null = range ? getMentionRange(this.quill, range.index) : null;

        if (selection && selection.length > 0) {
            const content = this.quill.getText(selection.index, selection.length);
            const isNewLinesOnly = !content.match(/[^\n]/);

            if (!isNewLinesOnly) {
                this.setState({ selectionIndex: selection.index, selectionLength: selection.length });
                return;
            }
        }

        this.setState({
            selectionIndex: null,
            selectionLength: null,
        });
    };
}

export default withEditor<IProps>(MentionSuggestionList);
