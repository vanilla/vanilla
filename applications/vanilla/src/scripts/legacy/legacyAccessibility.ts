/**
 * Allows user to click on links styled as buttons using the space bar or enter key.
 */

export function accessibleRoleButton() {
    document.addEventListener("keydown", (event) => {
        const target = event.target as HTMLElement;
        if (target.getAttribute("role") === "button" && (event.code === "Enter" || event.code === "Space")) {
            event.preventDefault();
            target.click();
        }
    });
}
