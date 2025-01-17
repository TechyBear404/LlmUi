import MarkdownIt from "markdown-it";
import hljs from "highlight.js";

export function formatDate(date) {
    return new Intl.DateTimeFormat("fr-FR").format(new Date(date));
}

export function formatTime(date) {
    return new Intl.DateTimeFormat("fr-FR", {
        hour: "numeric",
        minute: "numeric",
    }).format(new Date(date));
}

export function formatDateTime(date) {
    if (!date) return "";

    try {
        const dateObj = new Date(date);
        if (isNaN(dateObj.getTime())) {
            return ""; // Return empty string for invalid dates
        }

        return new Intl.DateTimeFormat("fr-FR", {
            hour: "numeric",
            minute: "numeric",
            year: "numeric",
            month: "long",
            day: "numeric",
        }).format(dateObj);
    } catch (e) {
        console.error("Date formatting error:", e);
        return "";
    }
}

const md = new MarkdownIt({
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return `<pre class="hljs"><code>${
                    hljs.highlight(str, { language: lang }).value
                }</code></pre>`;
            } catch (__) {}
        }
        return `<pre class="hljs"><code>${md.utils.escapeHtml(
            str
        )}</code></pre>`;
    },
    linkify: true,
    breaks: true,
});

export function renderMarkdown(content) {
    try {
        return md.render(content);
    } catch (e) {
        return content;
    }
}

export async function copyToClipboard(text) {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            return true;
        } else {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                document.execCommand("copy");
                return true;
            } catch (e) {
                console.error("Fallback: Copy failed", e);
                return false;
            } finally {
                textArea.remove();
            }
        }
    } catch (err) {
        console.error("Copy failed:", err);
        return false;
    }
}
