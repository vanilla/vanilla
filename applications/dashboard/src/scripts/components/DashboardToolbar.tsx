import React, { useEffect, useState } from "react";

export function DashboardToolbar({ children, ...otherProps }) {
    return (
        <div className="toolbar flex-wrap js-toolbar-sticky" {...otherProps}>
            {children}
        </div>
    );
}

export function DashboardToolbarButtons({ children, ...otherProps }) {
    return (
        <div className="toolbar-buttons" {...otherProps}>
            {children}
        </div>
    );
}

export function DashboardPagerArea({ children, ...otherProps }) {
    return (
        <div className="pager-wrap" {...otherProps}>
            {children}
        </div>
    );
}

interface DashboardSearchProps {
    className?: string;
    auto?: boolean;
    onSearch(search: string): void;
}

export function DashboardSearch(props: DashboardSearchProps) {
    const { className, onSearch, auto, ...otherProps } = props;
    const [search, setSearch] = useState<string | undefined>(undefined);

    useEffect(() => {
        if (auto && search !== undefined) {
            const timeout = setTimeout(() => {
                onSearch(search || "");
            }, 500);
            return () => clearTimeout(timeout);
        }
    }, [auto, search, onSearch]);

    return (
        <form className={className} onSubmit={() => onSearch(search || "")}>
            <div className="search-wrap">
                <div className="search-icon-wrap search-icon-search-wrap">
                    <svg className="icon icon-svg icon-svg-search" viewBox="0 0 17 17">
                        <use xlinkHref="#search" />
                    </svg>
                </div>
                <input className="form-control" onChange={(e) => setSearch(e.target.value || "")} {...otherProps} />
                <button type="submit" className="search-submit">
                    Search
                </button>
                <div
                    className="search-icon-wrap search-icon-clear-wrap"
                    onClick={() => {
                        setSearch("");
                        onSearch("");
                    }}
                >
                    <svg className="icon icon-svg icon-svg-close" viewBox="0 0 17 17">
                        <use xlinkHref="#close" />
                    </svg>
                </div>
            </div>
        </form>
    );
}
