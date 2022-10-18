/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useDiscussionCheckBoxContext } from "@library/features/discussions/DiscussionCheckboxContext";
import CheckBox from "@library/forms/Checkbox";
import { t } from "@vanilla/i18n";
import ReactDOM from "react-dom";
import React, { useLayoutEffect, useRef, useState } from "react";
import intersection from "lodash/intersection";
import { discussionListClasses } from "@library/features/discussions/DiscussionList.classes";
import { IDiscussion } from "@dashboard/@types/api/discussion";

interface IProps {
    discussionIDs?: Array<IDiscussion["discussionID"]>;
    className?: string;
}

/**
 * Checkbox to select a full page of discussions.
 */
export function DiscussionListSelectAll(props: IProps) {
    const context = useDiscussionCheckBoxContext();

    const discussionIDs = props.discussionIDs ?? [];
    const allChecked =
        discussionIDs.length > 0 &&
        intersection(discussionIDs, context.checkedDiscussionIDs).length === discussionIDs.length;

    return (
        <CheckBox
            label={t("Select All")}
            hideLabel
            checked={allChecked}
            disabled={discussionIDs.length === 0}
            className={props.className}
            onChange={(e) => {
                if (e.target.checked) {
                    context.addCheckedDiscussionsByIDs(discussionIDs);
                } else {
                    context.removeCheckedDiscussionsByIDs(discussionIDs);
                }
            }}
        />
    );
}

/**
 * Shim the new select all checkbox in place of the legacy one.
 */
export function LegacyDiscussionListSelectAll(props: IProps) {
    const [portalMountPoint, setPortalMountPoint] = useState<HTMLElement | null>(null);
    const ref = useRef<HTMLElement | null>(null);

    useLayoutEffect(() => {
        const nearestPageControls = ref.current
            ?.closest(".MainContent")
            ?.querySelector(".PageControls.Top .PageControls-filters");

        // Remove the old checkbox.
        const legacyAdminCheck = nearestPageControls?.querySelector(".AdminCheck");
        if (legacyAdminCheck) {
            legacyAdminCheck.remove();
        }

        // Create a spot for us to mount in at the start of the page controls.
        if (nearestPageControls instanceof HTMLElement) {
            const container = document.createElement("span");
            nearestPageControls.insertBefore(container, nearestPageControls.firstElementChild);
            setPortalMountPoint(container);
        }
    }, [ref.current]);

    // Find where we are so we can get a spot to portal into.
    if (!portalMountPoint) {
        return <span ref={ref}></span>;
    }

    // Portal into the legacy HTML.
    const classes = discussionListClasses();
    return ReactDOM.createPortal(
        <DiscussionListSelectAll className={classes.legacySelectAllCheckbox} discussionIDs={props.discussionIDs} />,
        portalMountPoint,
    );
}
