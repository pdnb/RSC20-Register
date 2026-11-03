import { Html5Qrcode } from 'html5-qrcode';

// Camera QR scanner for the check-in page. Each decoded token is handed to the
// Livewire component; repeated reads of the same code are ignored for 3 seconds.
window.startCheckinScanner = (elementId, onToken) => {
    const scanner = new Html5Qrcode(elementId);
    let last = null;
    let lastAt = 0;

    scanner
        .start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 240, height: 240 } },
            (text) => {
                const token = text.includes('/ticket/') ? text.split('/ticket/').pop() : text;
                const now = Date.now();
                if (token === last && now - lastAt < 3000) return;
                last = token;
                lastAt = now;
                onToken(token.trim());
            },
        )
        .catch((err) => console.warn('Camera unavailable', err));

    return scanner;
};
