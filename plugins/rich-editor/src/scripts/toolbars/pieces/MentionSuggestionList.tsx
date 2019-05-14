/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import classNames from "classnames";
import { t } from "@library/utility/appUtils";
import { RangeStatic } from "quill/core";
import { dropDownClasses } from "@library/flyouts/dropDownStyles";
import MentionSuggestion, {
    IMentionProps,
    MentionSuggestionLoading,
    MentionSuggestionSpacer,
} from "@rich-editor/toolbars/pieces/MentionSuggestion";
import ToolbarPositioner from "@rich-editor/toolbars/pieces/ToolbarPositioner";
import { IWithEditorProps } from "@rich-editor/editor/context";
import { withEditor } from "@rich-editor/editor/withEditor";

interface IProps extends IWithEditorProps {
    mentionProps: Array<Partial<IMentionProps>>;
    matchedString: string;
    id: string;
    loaderID: string;
    activeItemId: string | null;
    onItemClick: React.MouseEventHandler<any>;
    showLoader: boolean;
    mentionSelection: RangeStatic | null;
    legacyMode: boolean;
}

interface IState {
    flyoutWidth?: number | null;
    flyoutHeight?: number | null;
}

class MentionSuggestionList extends React.PureComponent<IProps, IState> {
    public state = {
        flyoutWidth: null,
        flyoutHeight: null,
    };
    private flyoutRef: React.RefObject<HTMLSpanElement> = React.createRef();

    constructor(props) {
        super(props);
    }

    public render() {
        const { activeItemId, id, onItemClick, matchedString, mentionProps, showLoader, mentionSelection } = this.props;
        const classesDropDown = dropDownClasses();

        const hasResults = mentionProps.length > 0 || showLoader;
        const classes = classNames(
            "richEditor-menu",
            "atMentionList-items",
            "likeDropDownContent",
            classesDropDown.likeDropDownContent,
        );
        const isVisible = hasResults && (!!mentionSelection || this.hasFocusedElement);

        return (
            <ToolbarPositioner
                horizontalAlignment="start"
                verticalAlignment="below"
                flyoutWidth={this.state.flyoutWidth}
                flyoutHeight={this.state.flyoutHeight}
                isActive={isVisible}
                selectionIndex={mentionSelection ? mentionSelection.index : 0}
                selectionLength={mentionSelection ? mentionSelection.length : 0}
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
                            zIndex: 10,
                            visibility: "visible",
                        };
                    }

                    const items = mentionProps.map(mentionProp => {
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
    }

    private get hasFocusedElement(): boolean {
        if (!this.flyoutRef.current) {
            return false;
        }
        return (
            document.activeElement === this.flyoutRef.current || this.flyoutRef.current.contains(document.activeElement)
        );
    }
}

export default withEditor<IProps>(MentionSuggestionList);
