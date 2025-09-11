/**
 * @author Daisy Barrette <dbarrette@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

// Detects if HTML content is from Microsoft Word and contains lists
export function isWordListContent(html: string) {
    return html.includes('class="MsoListParagraph"') || html.includes('style="mso-list:');
}

// Helper function to remove Word-specific list marker spans
function removeWordListMarkers(element: HTMLElement) {
    const ignoredSpans = element.querySelectorAll('span[style*="mso-list:Ignore"]');
    ignoredSpans.forEach((span) => span.remove());
}

// Helper function to clean up Word-specific attributes from all elements
function cleanWordAttributes(container: HTMLElement) {
    const allElements = container.querySelectorAll("*");

    allElements.forEach((element) => {
        // Remove Mso class attributes
        const className = element.getAttribute("class");
        if (className) {
            const cleanedClass = className
                .split(" ")
                .filter((cls) => !cls.startsWith("Mso"))
                .join(" ");

            if (cleanedClass) {
                element.setAttribute("class", cleanedClass);
            } else {
                element.removeAttribute("class");
            }
        }

        // Clean style attributes by removing mso-* properties
        const style = element.getAttribute("style");
        if (style) {
            const styleProps = style
                .split(";")
                .map((prop) => prop.trim())
                .filter((prop) => prop && !prop.toLowerCase().includes("mso-"));

            if (styleProps.length > 0) {
                element.setAttribute("style", styleProps.join("; "));
            } else {
                element.removeAttribute("style");
            }
        }
    });
}

// Helper function to remove Word conditional comments
function removeWordComments(container: HTMLElement) {
    const walker = document.createTreeWalker(container, NodeFilter.SHOW_COMMENT, null);

    const commentsToRemove: Comment[] = [];
    let node;

    while ((node = walker.nextNode())) {
        const comment = node as Comment;
        if (comment.textContent?.includes("[if !supportLists]") || comment.textContent?.includes("[endif]")) {
            commentsToRemove.push(comment);
        }
    }

    commentsToRemove.forEach((comment) => comment.remove());
}

// Helper function to clean text content of list markers
function cleanListItemText(li: HTMLElement) {
    const walker = document.createTreeWalker(li, NodeFilter.SHOW_TEXT, null);

    let firstTextNode = walker.nextNode() as Text;
    if (firstTextNode && firstTextNode.textContent) {
        const content = firstTextNode.textContent;

        // List markers are always at the start of the content
        // They typically follow patterns like "1.", "•", "a.", etc.
        // We need to distinguish between actual list markers and content that's just numbers

        // Check if the content starts with a list marker pattern
        const listMarkerPattern = /^[\s\u00A0]*(\d+\.|\u00B7|\*|-)[\s\u00A0]+/;
        const hasListMarker = listMarkerPattern.test(content);

        if (hasListMarker) {
            // Remove the list marker but keep the rest of the content
            firstTextNode.textContent = content.replace(listMarkerPattern, "").trim();
        } else {
            // No list marker, just trim whitespace
            firstTextNode.textContent = content.trim();
        }
    }
}

export default function cleanWordListContent(html: string) {
    if (!isWordListContent(html)) {
        return html;
    }

    // Use DOMParser to parse the HTML content
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, "text/html");
    const container = doc.body;

    const potentialItems = container.querySelectorAll('p.MsoListParagraph, p[style*="mso-list:"]');

    if (potentialItems.length === 0) {
        return html;
    }

    // Track lists at different levels
    let listStack: Array<{ list: HTMLElement; level: number }> = [];

    potentialItems.forEach((item) => {
        const style = (item as HTMLElement).getAttribute("style") || "";

        // Extract level from Word's style attribute
        const levelMatch = style.match(/level(\d+)/);
        const level = levelMatch ? parseInt(levelMatch[1], 10) : 1;

        // Check for list marker format in the text content
        const textContent = item.textContent || "";
        const hasNumberMarker = /^\s*\d+\./.test(textContent);
        const hasLetterMarker = /^\s*[a-z]\./.test(textContent);

        // Determine if this is an ordered list based on marker type or style
        const isOrdered = hasNumberMarker || hasLetterMarker || style.includes("mso-level-number-format:");

        // Create new list or find parent list based on level
        while (listStack.length > 0 && listStack[listStack.length - 1].level >= level) {
            listStack.pop();
        }

        let parentElement: HTMLElement;
        if (listStack.length === 0) {
            // Top-level list
            const newList = doc.createElement(isOrdered ? "ol" : "ul");
            item.parentNode?.insertBefore(newList, item);
            listStack.push({ list: newList, level });
            parentElement = newList;
        } else if (level > listStack[listStack.length - 1].level) {
            // Nested list
            const newList = doc.createElement(isOrdered ? "ol" : "ul");
            const lastItem = listStack[listStack.length - 1].list.lastElementChild as HTMLElement;
            if (lastItem) {
                lastItem.appendChild(newList);
                listStack.push({ list: newList, level });
                parentElement = newList;
            } else {
                // Fallback if no previous item exists
                parentElement = listStack[listStack.length - 1].list;
            }
        } else {
            // Same level as current list
            parentElement = listStack[listStack.length - 1].list;
        }

        // Create list item and move content
        const li = doc.createElement("li");
        li.innerHTML = item.innerHTML;

        // Clean up Word's list markers using DOM methods
        removeWordListMarkers(li);
        cleanListItemText(li);

        parentElement.appendChild(li);
        item.parentNode?.removeChild(item);
    });

    // Clean up Word-specific markup using DOM methods
    cleanWordAttributes(container);
    removeWordComments(container);

    return container.innerHTML;
}
