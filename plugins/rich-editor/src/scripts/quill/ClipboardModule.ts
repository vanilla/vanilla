import ClipboardBase from "quill/modules/clipboard";
import Delta from "quill-delta";
import Quill from "quill/core";

export default class ClipboardModule extends ClipboardBase {
    public container: HTMLElement;

    /**
     * Override the paste event to not jump around on paste in a cross-browser manor.
     *
     * Override https://github.com/quilljs/quill/blob/master/modules/clipboard.js#L108-L123
     * Because of https://github.com/quilljs/quill/issues/1374
     *
     * Hopefully this will be fixed in Quill 2.0
     */
    public onPaste(e: Event) {
        if (e.defaultPrevented || !(this.quill as any).isEnabled()) {
            return;
        }
        const range = this.quill.getSelection();
        let delta = new Delta().retain(range.index);

        // THIS IS WHAT IS DIFFERENT
        const scrollTop = document.documentElement.scrollTop || document.body.scrollTop;
        this.container.focus();
        (this.quill as any).selection.update(Quill.sources.SILENT);
        setTimeout(() => {
            delta = delta.concat((this as any).convert()).delete(range.length);
            this.quill.updateContents(delta, Quill.sources.USER);
            // range.length contributes to delta.length()
            this.quill.setSelection((delta.length() - range.length) as any, Quill.sources.SILENT);

            // THIS IS WHAT IS DIFFERENT
            document.documentElement.scrollTop = document.body.scrollTop = scrollTop;
            this.quill.focus();
        }, 1);
    }
}
