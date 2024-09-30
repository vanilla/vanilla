/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IDiscussion } from "@dashboard/@types/api/discussion";
import { cx } from "@emotion/css";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { ColorsUtils } from "@library/styles/ColorsUtils";
import { globalVariables } from "@library/styles/globalStyleVars";
import { BorderType } from "@library/styles/styleHelpersBorders";
import { IThreadItem } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { useCommentThread } from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import { CommentThreadItem } from "@vanilla/addon-vanilla/thread/CommentThreadItem";
import { nestCommentListClasses } from "@vanilla/addon-vanilla/thread/NestedComments.classes";
import { PartialCommentsList } from "@vanilla/addon-vanilla/thread/NestedCommentsList";
import { isThreadHole } from "@vanilla/addon-vanilla/thread/threadUtils";
import { Icon } from "@vanilla/icons";
import { useMeasure } from "@vanilla/react-utils";
import { useCallback, useEffect, useRef, useState } from "react";

interface IProps {
    threadItem: IThreadItem & { type: "comment" };
    discussion: IDiscussion;
}

// Comment so the branch does not automatically get deleted

export function ThreadItemComment(props: IProps) {
    const { threadItem } = props;
    const [isLoading, setIsLoading] = useState(false);
    const { getComment, updateThread, lastChildRefsByID } = useCommentThread();
    const comment = getComment(props.threadItem.commentID);
    const classes = nestCommentListClasses();
    const childrenRef = useRef<HTMLDivElement>(null);
    const childrenMeasure = useMeasure(childrenRef);

    const offsetHeight = useCallback(() => {
        let lastChildHeight = 100;
        if (lastChildRefsByID[threadItem.commentID]?.current) {
            const lastChildBox = lastChildRefsByID[threadItem.commentID].current?.getBoundingClientRect();
            if (lastChildBox?.height) {
                lastChildHeight = lastChildBox.height + 50;
            }
        }

        // Ensure positive SVG integer
        const roundedHeight = Math.ceil(childrenMeasure?.height - lastChildHeight);
        if (roundedHeight < 0) {
            return 1;
        }
        return roundedHeight;
    }, [childrenMeasure.height, lastChildRefsByID, threadItem.commentID]);

    const hasHole = props.threadItem.children && props.threadItem.children.some((child) => child.type === "hole");

    const descenderButtonContent = hasHole ? <Icon icon={"analytics-add"} /> : <Icon icon={"analytics-remove"} />;

    const descenderButtonAction = () => {
        if (hasHole) {
            // Should find the hole and update the thread
            setIsLoading(true);
            const hole = props.threadItem.children?.find((child) => child.type === "hole");
            hole && isThreadHole(hole) && updateThread(hole.apiUrl, hole.path);
        } else {
            // Should collapse the children
            /**
             * TODO: We should talk about what we want here.
             * Presumably the collapsed children are replaced with a hole.
             * Maybe even a faux hole which acts as an "expand" button?
             * Or we just trim the entire child list and replace it with a hole and refetch from the server?
             */
        }
    };

    useEffect(() => {
        if (isLoading) {
            setIsLoading(false);
        }
    }, [props.threadItem]);

    return (
        <>
            {comment && (
                <div
                    className={cx(
                        // Root comments have different styling than child comments
                        threadItem.depth <= 1 ? classes.rootCommentItem : classes.childCommentItem,
                    )}
                >
                    <CommentThreadItem
                        boxOptions={{ borderType: BorderType.NONE }}
                        comment={comment}
                        discussion={props.discussion}
                        canReplyInline={true}
                    />
                </div>
            )}
            <div style={{ height: "100%" }}>
                {props.threadItem.children && props.threadItem.children.length > 0 && (
                    <div className={cx(classes.childContainer)} ref={childrenRef}>
                        <div className={classes.descender}>
                            <Button buttonType={ButtonTypes.ICON} onClick={() => descenderButtonAction()}>
                                {isLoading ? <ButtonLoader /> : descenderButtonContent}
                            </Button>
                            <svg width={2} height={offsetHeight()}>
                                <line
                                    stroke={ColorsUtils.colorOut(globalVariables().border.color)}
                                    strokeWidth={2}
                                    x1="0"
                                    y1="0"
                                    x2="0"
                                    y2={offsetHeight()}
                                />
                            </svg>
                        </div>
                        <div className={cx(classes.commentChildren)}>
                            <PartialCommentsList threadStructure={props.threadItem.children} />
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
