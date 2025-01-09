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
    return new Intl.DateTimeFormat("fr-FR", {
        hour: "numeric",
        minute: "numeric",
        year: "numeric",
        month: "long",
        day: "numeric",
    }).format(new Date(date));
}
