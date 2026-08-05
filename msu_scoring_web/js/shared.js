/**
 * MSU Scoring — Shared Frontend JS Utilities
 * Centralized toast notifications (XSS safe), CSRF handling, and modal helpers.
 */

// ── 1. Toast Notification System (XSS Safe with textContent & ARIA) ─────────
function showToast(message, type = 'success') {
    let container = document.getElementById('toastContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toastContainer';
        container.setAttribute('role', 'alert');
        container.setAttribute('aria-live', 'polite');
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    // SVG Icon (Constant markup - safe)
    const iconSvg = type === 'success' 
        ? `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>`
        : `<svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>`;
    
    const iconWrapper = document.createElement('span');
    iconWrapper.innerHTML = iconSvg;
    
    const textSpan = document.createElement('span');
    textSpan.textContent = message; // Using textContent prevents XSS attacks
    
    toast.appendChild(iconWrapper.firstElementChild);
    toast.appendChild(textSpan);
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('toast-out');
        toast.addEventListener('animationend', () => toast.remove(), { once: true });
    }, 3500);
}

// ── 2. Modal Message Display Helper ──────────────────────────────────────────
function showModalMsg(el, text, isError) {
    if (!el) return;
    el.textContent = text;
    el.className = isError
        ? 'text-sm font-medium px-4 py-2.5 rounded-lg bg-red-50 text-red-700 border border-red-200'
        : 'text-sm font-medium px-4 py-2.5 rounded-lg bg-green-50 text-green-700 border border-green-200';
    el.classList.remove('hidden');
}

// ── 3. HTML Escaping Utility ──────────────────────────────────────────────────
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return String(unsafe)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// ── 4. CSRF Token Fetch Helper ────────────────────────────────────────────────
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

async function fetchApi(url, options = {}) {
    options.headers = options.headers || {};
    const token = getCsrfToken();
    if (token) {
        if (options.body instanceof FormData) {
            options.body.append('csrf_token', token);
        } else if (typeof options.body === 'string' && options.headers['Content-Type'] === 'application/x-www-form-urlencoded') {
            options.body += `&csrf_token=${encodeURIComponent(token)}`;
        } else {
            options.headers['X-CSRF-Token'] = token;
        }
    }
    return fetch(url, options);
}

// ── 5. Password Eye Toggle Binding ───────────────────────────────────────────
function bindPasswordToggles() {
    document.querySelectorAll('.password-toggle-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            if (!input) return;
            
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            
            // Eye vs Eye-Off SVG
            btn.innerHTML = isPassword
                ? `<svg class="w-5 h-5 text-gray-500 hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24M1 1l22 22"/></svg>`
                : `<svg class="w-5 h-5 text-gray-500 hover:text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>`;
        });
    });
}

// ── 6. Dialog Backdrop Click Close Helper ────────────────────────────────────
function enableDialogBackdropDismiss() {
    document.querySelectorAll('dialog').forEach(dialog => {
        dialog.addEventListener('click', (e) => {
            const rect = dialog.getBoundingClientRect();
            const isInDialog = (
                rect.top <= e.clientY && e.clientY <= rect.bottom &&
                rect.left <= e.clientX && e.clientX <= rect.right
            );
            if (!isInDialog) {
                dialog.close();
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    bindPasswordToggles();
    enableDialogBackdropDismiss();
});
