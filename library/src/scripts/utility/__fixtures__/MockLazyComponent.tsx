import React from "react";

export function MockLazyComponent() {
    return <span data-testid="loaded">I am the lazy component</span>;
}

export default MockLazyComponent;
