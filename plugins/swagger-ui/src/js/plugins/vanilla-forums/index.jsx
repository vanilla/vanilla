import DashboardLayout from "./components/layouts/dashboard";

export default function vanillaForums() {
    return {
        components: {
            contentType: () => { return null; },
            DashboardLayout: DashboardLayout
        }
    };
};
