/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import DateTime from "@library/content/DateTime";
import { useToast } from "@library/features/toaster/ToastContext";
import { MetaButton, MetaLink } from "@library/metas/Metas";
import { t } from "@vanilla/i18n";
import React from "react";
import { ThreadItemPermalinkClasses } from "@vanilla/addon-vanilla/thread/ThreadItemPermalink.classes";
import { useThreadItemContext } from "@vanilla/addon-vanilla/thread/ThreadItemContext";

export default function ThreadItemPermalink() {
    const toast = useToast();
    const classes = ThreadItemPermalinkClasses();

    const { recordUrl, timestamp, handleCopyUrl } = useThreadItemContext();

    return (
        <>
            <MetaLink to={recordUrl}>
                <DateTime timestamp={timestamp} />
            </MetaLink>

            <MetaButton
                icon={"editor-link"}
                className={classes.copyLinkButton}
                title={t("Copy Link")}
                aria-label={t("Copy Link")}
                onClick={async () => {
                    await handleCopyUrl();
                    toast.addToast({
                        body: <>{t("Link copied to clipboard.")}</>,
                        autoDismiss: true,
                    });
                }}
            />
        </>
    );
}
