/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LeavingPageImpl } from "@library/leavingPage/LeavingPage";
import React from "react";

export default {
    title: "Pages/Leaving Page",
};

export function LeavingPage() {
    return <LeavingPageImpl target="https://someexternalsite.com" siteName="Vanilla Site Name" />;
}
