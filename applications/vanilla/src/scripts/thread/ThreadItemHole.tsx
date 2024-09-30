/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { cx } from "@emotion/css";
import Translate from "@library/content/Translate";
import Button from "@library/forms/Button";
import { ButtonTypes } from "@library/forms/buttonTypes";
import { UserPhoto, UserPhotoSize } from "@library/headers/mebox/pieces/UserPhoto";
import { userPhotoVariables } from "@library/headers/mebox/pieces/userPhotoStyles";
import { BottomChevronIcon } from "@library/icons/common";
import ButtonLoader from "@library/loaders/ButtonLoader";
import { StackedList } from "@library/stackedList/StackedList";
import { stackedListVariables } from "@library/stackedList/StackedList.variables";
import { globalVariables } from "@library/styles/globalStyleVars";
import { IThreadItem } from "@vanilla/addon-vanilla/thread/@types/CommentThreadTypes";
import { useCommentThread } from "@vanilla/addon-vanilla/thread/CommentThreadContext";
import { nestCommentListClasses } from "@vanilla/addon-vanilla/thread/NestedComments.classes";
import { useState } from "react";

interface IHoleProps {
    threadItem: IThreadItem & { type: "hole" };
}

export function ThreadItemHole(props: IHoleProps) {
    const { countAllComments, insertUsers, countAllInsertUsers, apiUrl, path } = props.threadItem;

    const { updateThread } = useCommentThread();
    const [isLoading, setIsLoading] = useState(false);

    const classes = nestCommentListClasses();

    const otherUserCount = countAllInsertUsers - insertUsers.length;

    return (
        <Button
            buttonType={ButtonTypes.CUSTOM}
            className={cx(classes.hole, "hole")}
            onClick={() => {
                setIsLoading(true);
                updateThread(apiUrl, path);
            }}
        >
            {isLoading ? <ButtonLoader /> : <BottomChevronIcon />}
            <StackedList
                themingVariables={{
                    ...stackedListVariables("thread-participants"),
                    sizing: {
                        ...stackedListVariables("thread-participants").sizing,
                        width: userPhotoVariables().sizing.xsmall,
                        offset: 10,
                    },
                    plus: {
                        ...stackedListVariables("thread-participants").plus,
                        font: globalVariables().fontSizeAndWeightVars("medium"),
                    },
                }}
                data={insertUsers}
                maxCount={5}
                ItemComponent={(user) => <UserPhoto size={UserPhotoSize.XSMALL} userInfo={user} />}
            />
            <span>
                <Translate
                    source={"<0/> <1/> more comments"}
                    c0={`${otherUserCount > 1 ? `+ ${otherUserCount} others in` : ""}`}
                    c1={countAllComments}
                />
            </span>
        </Button>
    );
}
