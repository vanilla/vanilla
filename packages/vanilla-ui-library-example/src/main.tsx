import { StrictMode } from "react";
import { render } from "react-dom";
import App from "./App";
import "@vanilla/ui-library/dist/index.css";
import { init } from "@vanilla/ui-library";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";

init({ locale: "en" });

const queryClient = new QueryClient();

render(
    <StrictMode>
        <QueryClientProvider client={queryClient}>
            <App />
        </QueryClientProvider>
    </StrictMode>,
    document.getElementById("root")!,
);
