/**
 * @author Stéphane LaFlèche <stephane.l@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import React from "react";
/**
 * Create long text for scroll testing.
 */
export function StoryLongText(props: { times?: number }) {
    let longText: React.ReactNode[] = [];
    for (let i = 0; i < (props.times ?? 1000); i++) {
        longText.push(<p key={i}>Scrollable content</p>);
    }
    return <>{longText}</>;
}
