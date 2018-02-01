import Delta from "quill-delta";
import Parchment from "parchment";
import Quill from "quill/core/quill";
import Module from "quill/core/module";

import * as utilities from "@core/utility";

class Toolbar extends Module {
    constructor(quill, options) {
        super(quill, options);
        this.container = this.options.container;

        if (!(this.container instanceof HTMLElement)) {
            return utilities.logError("Container required for toolbar", this.options);
        }

        this.controls = [];
        this.handlers = {};
        Object.keys(this.options.handlers).forEach((format) => {
            this.addHandler(format, this.options.handlers[format]);
        });
        [].forEach.call(this.container.querySelectorAll("button, select"), (input) => {
            this.attach(input);
        });
        this.quill.on(Quill.events.EDITOR_CHANGE, (type, range) => {
            if (type === Quill.events.SELECTION_CHANGE) {
                this.update(range);
            }
        });
        this.quill.on(Quill.events.SCROLL_OPTIMIZE, () => {
            const [range ] = this.quill.selection.getRange(); // quill.getSelection triggers update
            this.update(range);
        });
    }

    addHandler(format, handler) {
        this.handlers[format] = handler;
    }

    attach(input) {
        format = format.slice("ql-".length);

        input.addEventListener(eventName, (e) => {
            let value;

                if (input.classList.contains("ql-active")) {
                    value = false;
                } else {
                    value = input.value || !input.hasAttribute("value");
                }
                e.preventDefault();
            this.quill.focus();
            const [range ] = this.quill.selection.getRange();
            if (this.handlers[format] != null) {
                this.handlers[format].call(this, value);
            } else if (Parchment.query(format).prototype instanceof Parchment.Embed) {
                value = prompt(`Enter ${format}`);
                if (!value) {
                    return;
                }
                this.quill.updateContents(new Delta()
                    .retain(range.index)
                    .delete(range.length)
                    .insert({ [format]: value })
                    , Quill.sources.USER);
            } else {
                this.quill.format(format, value, Quill.sources.USER);
            }
            this.update(range);
        });

        // TODO use weakmap
        this.controls.push([format, input]);
    }

    update(range) {
        const formats = range == null ? {} : this.quill.getFormat(range);
        this.controls.forEach(function(pair) {
            const [format, input] = pair;

                if (range == null) {
                    input.classList.remove("ql-active");
                } else if (input.hasAttribute("value")) {

                    // both being null should match (default values)
                    // '1' should match with 1 (headers)
                    const isActive = formats[format] === input.getAttribute("value") ||
                        (formats[format] != null && formats[format].toString() === input.getAttribute("value")) ||
                        (formats[format] == null && !input.getAttribute("value"));
                    input.classList.toggle("ql-active", isActive);
                } else {
                    input.classList.toggle("ql-active", formats[format] != null);
                }
            }
        });
    }
}
Toolbar.DEFAULTS = {};


function addButton(container, format, value) {
    const input = document.createElement("button");
    input.setAttribute("type", "button");
    input.classList.add("ql-" + format);
    if (value != null) {
        input.value = value;
    }
    container.appendChild(input);
}

function addControls(container, groups) {
    if (!Array.isArray(groups[0])) {
        groups = [groups];
    }
    groups.forEach(function(controls) {
        const group = document.createElement("span");
        group.classList.add("ql-formats");
        controls.forEach(function(control) {
            if (typeof control === "string") {
                addButton(group, control);
            } else {
                const format = Object.keys(control)[0];
                const value = control[format];
                if (Array.isArray(value)) {
                    addSelect(group, format, value);
                } else {
                    addButton(group, format, value);
                }
            }
        });
        container.appendChild(group);
    });
}

function addSelect(container, format, values) {
    const input = document.createElement("select");
    input.classList.add("ql-" + format);
    values.forEach(function(value) {
        const option = document.createElement("option");
        if (value !== false) {
            option.setAttribute("value", value);
        } else {
            option.setAttribute("selected", "selected");
        }
        input.appendChild(option);
    });
    container.appendChild(input);
}

Toolbar.DEFAULTS = {
    container: null,
    handlers: {
        clean: function() {
            const range = this.quill.getSelection();
            if (range == null) {
                return;
            }
            if (range.length == 0) {
                const formats = this.quill.getFormat();
                Object.keys(formats).forEach((name) => {

                    // Clean functionality in existing apps only clean inline formats
                    if (Parchment.query(name, Parchment.Scope.INLINE) != null) {
                        this.quill.format(name, false);
                    }
                });
            } else {
                this.quill.removeFormat(range, Quill.sources.USER);
            }
        },
        direction: function(value) {
            const align = this.quill.getFormat()["align"];
            if (value === "rtl" && align == null) {
                this.quill.format("align", "right", Quill.sources.USER);
            } else if (!value && align === "right") {
                this.quill.format("align", false, Quill.sources.USER);
            }
            this.quill.format("direction", value, Quill.sources.USER);
        },
        indent: function(value) {
            const range = this.quill.getSelection();
            const formats = this.quill.getFormat(range);
            const indent = parseInt(formats.indent || 0);
            if (value === "+1" || value === "-1") {
                let modifier = (value === "+1") ? 1 : -1;
                if (formats.direction === "rtl") {
                    modifier *= -1;
                }
                this.quill.format("indent", indent + modifier, Quill.sources.USER);
            }
        },
        link: function(value) {
            if (value === true) {
                value = prompt("Enter link URL:");
            }
            this.quill.format("link", value, Quill.sources.USER);
        },
        list: function(value) {
            const range = this.quill.getSelection();
            const formats = this.quill.getFormat(range);
            if (value === "check") {
                if (formats["list"] === "checked" || formats["list"] === "unchecked") {
                    this.quill.format("list", false, Quill.sources.USER);
                } else {
                    this.quill.format("list", "unchecked", Quill.sources.USER);
                }
            } else {
                this.quill.format("list", value, Quill.sources.USER);
            }
        },
    },
};


export { Toolbar as default, addControls };
