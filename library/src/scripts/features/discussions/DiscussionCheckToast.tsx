/*
 * @author Carla França <cfranca@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
import Toast from "@library/features/toaster/Toast";
import { ButtonTypes } from "@library/forms/buttonTypes";
import Translate from "@library/content/Translate";

const DiscussionCheckToast = ({ discussionIDs }) => {
    const handleCancel = () => {};
    const handleMove = () => {};
    const handleMerge = () => {};
    const handleWarn = () => {};
    const handleDelete = () => {};

    return (
        <div>
            <Toast
                links={[
                    {
                        name: "Cancel",
                        type: ButtonTypes.TEXT,
                        onClick: handleCancel,
                    },
                    {
                        name: "Move",
                        type: ButtonTypes.TEXT,
                        onClick: handleMove,
                    },
                    {
                        name: "Merge",
                        type: ButtonTypes.TEXT,
                        onClick: handleMerge,
                    },
                    {
                        name: "Warn",
                        type: ButtonTypes.TEXT,
                        onClick: handleWarn,
                    },
                    {
                        name: "Delete",
                        type: ButtonTypes.TEXT,
                        onClick: handleDelete,
                    },
                ]}
                message={
                    <Translate
                        source={
                            discussionIDs.length > 1
                                ? "You have selected <0/> discussions."
                                : "You have selected <0/> discussion."
                        }
                        c0={discussionIDs.length}
                    />
                }
            ></Toast>
        </div>
    );
};

export default DiscussionCheckToast;
