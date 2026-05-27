/**
 * Anjuman E Ezzy - Main frontend runtime.
 */

'use strict';

const Toast = (() => {
    let container = null;

    function getContainer() {
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container-custom';
            document.body.appendChild(container);
        }
        return container;
    }

    function show(type, title, message, duration = 4000) {
        const icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
        const toast = document.createElement('div');
        toast.className = `toast-custom toast-${type}`;
        toast.innerHTML = `
            <span class="toast-icon">${icons[type] || 'ℹ️'}</span>
            <div class="toast-body">
                <div class="toast-title">${title}</div>
                ${message ? `<div class="toast-message">${message}</div>` : ''}
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">✕</button>
        `;
        getContainer().appendChild(toast);
        window.setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease forwards';
            window.setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    return {
        success: (title, msg, duration) => show('success', title, msg, duration),
        error: (title, msg, duration) => show('error', title, msg, duration),
        info: (title, msg, duration) => show('info', title, msg, duration),
        warning: (title, msg, duration) => show('warning', title, msg, duration),
    };
})();

const Csrf = {
    get() {
        return document.querySelector('meta[name="csrf-token"]')?.content || '';
    },
};

function ajaxPost(url, data, successCb, errorCb) {
    const formData = new FormData();
    formData.append('_csrf_token', Csrf.get());
    Object.entries(data || {}).forEach(([key, value]) => formData.append(key, value));

    $.ajax({
        url,
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        success: successCb,
        error: (xhr) => {
            const response = xhr.responseJSON || {};
            if (errorCb) {
                errorCb(response);
                return;
            }
            Toast.error('Error', response.message || 'Something went wrong.');
        },
    });
}

function ajaxGet(url, data, successCb, errorCb) {
    $.ajax({
        url,
        method: 'GET',
        data,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': Csrf.get(),
        },
        success: successCb,
        error: (xhr) => {
            const response = xhr.responseJSON || {};
            if (errorCb) {
                errorCb(response);
            }
        },
    });
}

function fallbackCopy(text, callback) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.focus();
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    callback();
}

function copyToClipboard(text, btnEl) {
    const originalLabel = btnEl ? btnEl.textContent : '';
    const done = () => {
        if (btnEl) {
            btnEl.textContent = 'Copied!';
            window.setTimeout(() => {
                btnEl.textContent = originalLabel;
            }, 1500);
        }
        Toast.success('Copied!', 'Value copied to clipboard.');
    };

    if (navigator.clipboard?.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(() => fallbackCopy(text, done));
        return;
    }

    fallbackCopy(text, done);
}

function confirmAction(message, onConfirm) {
    const modal = document.getElementById('confirmModal');
    if (modal && window.bootstrap) {
        document.getElementById('confirmMsg').textContent = message;
        document.getElementById('confirmBtn').onclick = () => {
            bootstrap.Modal.getInstance(modal)?.hide();
            onConfirm();
        };
        new bootstrap.Modal(modal).show();
        return;
    }

    if (window.confirm(message)) {
        onConfirm();
    }
}

const StreamViewer = (() => {
    let timerId = null;
    let statusUrl = '';
    let isPolling = false;
    let pollDelay = 30000;

    function setBadge(status) {
        const badges = document.querySelectorAll('#stream-status-badge');
        if (!badges.length) return;

        badges.forEach((badge) => {
            if (status === 'live') {
                badge.className = 'badge-live';
                badge.innerHTML = '<span class="live-dot"></span> LIVE';
                return;
            }

            badge.className = 'badge-offline';
            badge.textContent = '⚫ OFFLINE';
        });
    }

    function setPlayer(embedUrl, status) {
        const frame = document.getElementById('streamPlayerFrame');
        const shell = document.getElementById('streamPlayerShell');
        const offline = document.getElementById('streamOfflineState');
        const mask = document.getElementById('streamChromeMask');
        const overlay = document.querySelector('.stream-live-overlay');
        const fullscreenBtn = document.getElementById('streamFullscreenBtn');

        if (!frame || !shell || !offline) return;

        const setFullscreenState = () => {
            const requestFullscreen = shell.requestFullscreen
                || shell.webkitRequestFullscreen
                || shell.mozRequestFullScreen
                || shell.msRequestFullscreen;

            if (requestFullscreen) {
                requestFullscreen.call(shell);
            }
        };

        const updateFullscreenLabel = () => {
            if (!fullscreenBtn) return;

            const active = document.fullscreenElement === shell
                || document.webkitFullscreenElement === shell
                || document.mozFullScreenElement === shell
                || document.msFullscreenElement === shell;

            fullscreenBtn.textContent = active ? '⤫ Exit fullscreen' : '⛶ Fullscreen';
            fullscreenBtn.setAttribute('aria-label', active ? 'Exit fullscreen' : 'Enter fullscreen');
        };

        const exitFullscreenState = () => {
            const exitFullscreen = document.exitFullscreen
                || document.webkitExitFullscreen
                || document.mozCancelFullScreen
                || document.msExitFullscreen;

            if (exitFullscreen) {
                exitFullscreen.call(document);
            }
        };

        if (fullscreenBtn && !fullscreenBtn.dataset.bound) {
            fullscreenBtn.dataset.bound = 'true';
            fullscreenBtn.addEventListener('click', (event) => {
                event.preventDefault();
                const active = document.fullscreenElement === shell
                    || document.webkitFullscreenElement === shell
                    || document.mozFullScreenElement === shell
                    || document.msFullscreenElement === shell;

                if (active) {
                    exitFullscreenState();
                    return;
                }

                setFullscreenState();
            });

            ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange']
                .forEach((eventName) => {
                    document.addEventListener(eventName, updateFullscreenLabel);
                });
        }

        if (status === 'live' && embedUrl) {
            frame.src = embedUrl;
            shell.style.display = 'block';
            offline.style.display = 'none';
            if (mask) mask.style.display = 'block';
            if (overlay) overlay.style.display = 'flex';
            updateFullscreenLabel();
            return;
        }

        frame.src = 'about:blank';
        shell.style.display = 'none';
        offline.style.display = 'flex';
        if (mask) mask.style.display = 'none';
        if (overlay) overlay.style.display = 'none';
        if (fullscreenBtn) fullscreenBtn.textContent = '⛶ Fullscreen';
    }

    function update(resp) {
        setBadge(resp.status || 'offline');
        setPlayer(resp.embed_url || '', resp.status || 'offline');
    }

    function poll() {
        if (!statusUrl || document.hidden || isPolling) return;

        isPolling = true;
        $.getJSON(statusUrl, { ts: Date.now() })
            .done((resp) => {
                if (resp.success) {
                    update(resp);
                    pollDelay = resp.status === 'live' ? 15000 : 30000;
                }
            })
            .fail(() => {
                pollDelay = Math.min(pollDelay * 2, 60000);
            })
            .always(() => {
                isPolling = false;
                if (timerId) {
                    window.clearTimeout(timerId);
                }
                timerId = window.setTimeout(poll, pollDelay);
            });
    }

    function init(url) {
        statusUrl = url || document.body.dataset.streamStatusUrl || '';
        if (!statusUrl) return;
        pollDelay = 30000;
        poll();

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                pollDelay = 15000;
                if (timerId) {
                    window.clearTimeout(timerId);
                    timerId = null;
                }
                poll();
            }
        });
    }

    function stop() {
        if (timerId) {
            window.clearTimeout(timerId);
            timerId = null;
        }
    }

    return { init, stop, refresh: poll };
})();

window.Toast = Toast;
window.Csrf = Csrf;
window.StreamViewer = StreamViewer;
window.ajaxPost = ajaxPost;
window.ajaxGet = ajaxGet;
window.confirmAction = confirmAction;
window.copyToClipboard = copyToClipboard;

function initScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in');
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('.fade-in, .slide-up').forEach((element) => observer.observe(element));
}

$(function () {
    initScrollAnimations();
    if (document.body.dataset.streamStatusUrl) {
        StreamViewer.init(document.body.dataset.streamStatusUrl);
    }

    if (document.body.dataset.preventBackNavigation === 'true') {
        history.pushState({ locked: true }, '', window.location.href);
        window.addEventListener('popstate', () => {
            history.pushState({ locked: true }, '', window.location.href);
        });
    }
});
