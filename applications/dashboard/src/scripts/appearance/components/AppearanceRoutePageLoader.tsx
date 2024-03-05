/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import AdminHeader from "@dashboard/components/AdminHeader";
import Loader from "@library/loaders/Loader";
import React from "react";

export default function AppearanceRoutePageLoader() {
    return (
        <>
            <AdminHeader activeSectionID={"appearance"} />
            <Loader />
        </>
    );
}
