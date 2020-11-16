import React from "react";

export function DashboardToolbar(props) {
    return <div className="toolbar flex-wrap js-toolbar-sticky">{props.children}</div>;
}

export function DashboardToolbarButtons(props) {
    return <div className="toolbar-buttons">{props.children}</div>;
}

export function DashboardPagerArea(props) {
    return <div className="pager-wrap">{props.children}</div>;
}
