export function pdfUrl(baseUrl) {
    const sep = baseUrl.includes('?') ? '&' : '?';
    return `${baseUrl}${sep}v=${Date.now()}`;
}
