(() => {
    'use strict';

    const POLL_DELAY_MS = 5000;
    const REQUEST_TIMEOUT_MS = 15000;
    const COMMON_REGION_NAMES = ['last-updated', 'logs-source', 'database-source', 'notices'];
    const controls = document.querySelector('[data-dashboard-refresh-controls]');
    const toggle = document.getElementById('dashboard-refresh-toggle');
    const status = document.getElementById('dashboard-refresh-status');

    function collectRegions(root) {
        const regions = new Map();
        for (const element of root.querySelectorAll('[data-dashboard-refresh-region]')) {
            const name = element.getAttribute('data-dashboard-refresh-region');
            if (!name || regions.has(name)) {
                throw new Error('Invalid refresh region.');
            }
            regions.set(name, element);
        }
        return regions;
    }

    if (!controls || !toggle || !status) {
        return;
    }

    let currentRegions;
    let regionNames;
    try {
        currentRegions = collectRegions(document);
        const hasInteractions = currentRegions.has('interactions');
        const hasDiagnostics = currentRegions.has('download') && currentRegions.has('diagnostics');
        regionNames = [
            ...COMMON_REGION_NAMES,
            ...(hasInteractions ? ['interactions'] : hasDiagnostics ? ['download', 'diagnostics'] : []),
        ];
        if (hasInteractions === hasDiagnostics
            || currentRegions.size !== regionNames.length
            || COMMON_REGION_NAMES.some(name => !currentRegions.has(name))
            || [...currentRegions.keys()].some(name => !regionNames.includes(name))) {
            throw new Error('Incomplete refresh regions.');
        }
    } catch (error) {
        return;
    }

    let userPaused = false;
    let interactionPaused = false;
    let timer = null;
    let activeRequest = null;
    let requestGeneration = 0;
    let lastRefreshFailed = false;

    function setStatus(message) {
        if (status.textContent !== message) {
            status.textContent = message;
        }
    }

    function clearTimer() {
        if (timer !== null) {
            clearTimeout(timer);
            timer = null;
        }
    }

    function canRefresh() {
        return !userPaused && !interactionPaused && !document.hidden;
    }

    function schedule(delay) {
        clearTimer();
        if (!canRefresh() || activeRequest !== null) {
            return;
        }
        timer = setTimeout(() => {
            timer = null;
            refresh();
        }, delay);
    }

    function invalidateRequest() {
        requestGeneration++;
        if (activeRequest !== null) {
            activeRequest.controller.abort();
        }
    }

    function regionContaining(element) {
        if (!element) {
            return null;
        }
        for (const region of currentRegions.values()) {
            if (region.contains(element)) {
                return region;
            }
        }
        return null;
    }

    function focusedDetailKey() {
        const active = document.activeElement;
        const region = regionContaining(active);
        if (!region) {
            return null;
        }
        const summary = active.closest ? active.closest('summary') : null;
        const detail = summary && summary.parentElement && summary.parentElement.closest
            ? summary.parentElement.closest('details[data-refresh-key]')
            : null;
        return detail && region.contains(detail) ? detail.getAttribute('data-refresh-key') : undefined;
    }

    function hasSelectionInRegions() {
        const selection = window.getSelection ? window.getSelection() : null;
        if (!selection || selection.isCollapsed) {
            return false;
        }
        return Array.from(currentRegions.values()).some(region =>
            region.contains(selection.anchorNode) || region.contains(selection.focusNode)
        );
    }

    function hasUnpreservableFocus() {
        const active = document.activeElement;
        if (!regionContaining(active)) {
            return false;
        }
        return focusedDetailKey() === undefined;
    }

    function syncInteractionPause() {
        const next = hasSelectionInRegions() || hasUnpreservableFocus();
        if (next === interactionPaused) {
            return;
        }
        interactionPaused = next;
        if (interactionPaused) {
            clearTimer();
            invalidateRequest();
            setStatus('Refresh paused while you interact with live data.');
        } else if (!userPaused && !document.hidden) {
            setStatus('Automatic refresh resumed.');
            schedule(0);
        }
    }

    function safeRegion(region, pageUrl) {
        const forbidden = new Set(['script', 'style', 'iframe', 'object', 'embed', 'form', 'base', 'meta', 'link']);
        const elements = [region, ...region.querySelectorAll('*')];
        for (const element of elements) {
            if (forbidden.has(element.localName)) {
                return false;
            }
            for (const attribute of Array.from(element.attributes)) {
                const name = attribute.name.toLowerCase();
                if (name.startsWith('on') || name === 'srcdoc') {
                    return false;
                }
                if (name === 'href' || name === 'src' || name === 'action' || name === 'formaction') {
                    try {
                        if (new URL(attribute.value, pageUrl).origin !== window.location.origin) {
                            return false;
                        }
                    } catch (error) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    function applyPage(html) {
        const parsed = new DOMParser().parseFromString(html, 'text/html');
        if (!parsed.documentElement || !parsed.body) {
            throw new Error('Invalid refresh response.');
        }
        const nextRegions = collectRegions(parsed);
        if (nextRegions.size !== regionNames.length
            || regionNames.some(name => !nextRegions.has(name))
            || [...nextRegions.keys()].some(name => !regionNames.includes(name))) {
            throw new Error('Refresh regions do not match the current view.');
        }
        const operations = [];
        for (const name of regionNames) {
            const current = currentRegions.get(name);
            const next = nextRegions.get(name);
            if (current.localName !== next.localName || !safeRegion(next, window.location.href)) {
                throw new Error('Unsafe refresh response.');
            }
            operations.push({
                current,
                className: next.getAttribute('class'),
                children: Array.from(next.childNodes, child => child.cloneNode(true)),
            });
        }

        const openKeys = new Set();
        for (const region of currentRegions.values()) {
            for (const detail of region.querySelectorAll('details[data-refresh-key][open]')) {
                openKeys.add(detail.getAttribute('data-refresh-key'));
            }
        }
        const focusKey = focusedDetailKey();
        const scrollX = window.scrollX;
        const scrollY = window.scrollY;

        for (const operation of operations) {
            if (operation.className === null) {
                operation.current.removeAttribute('class');
            } else {
                operation.current.setAttribute('class', operation.className);
            }
            operation.current.replaceChildren(...operation.children);
        }

        for (const region of currentRegions.values()) {
            for (const detail of region.querySelectorAll('details[data-refresh-key]')) {
                detail.open = openKeys.has(detail.getAttribute('data-refresh-key'));
            }
        }
        if (focusKey !== null && focusKey !== undefined) {
            for (const region of currentRegions.values()) {
                const detail = Array.from(region.querySelectorAll('details[data-refresh-key]'))
                    .find(element => element.getAttribute('data-refresh-key') === focusKey);
                const summary = detail && detail.querySelector('summary');
                if (summary) {
                    summary.focus({ preventScroll: true });
                    break;
                }
            }
        }
        window.scrollTo(scrollX, scrollY);
    }

    async function refresh() {
        if (!canRefresh() || activeRequest !== null) {
            return;
        }
        const request = {
            controller: new AbortController(),
            generation: ++requestGeneration,
        };
        activeRequest = request;
        request.timeout = setTimeout(() => {
            request.timedOut = true;
            request.controller.abort();
        }, REQUEST_TIMEOUT_MS);
        try {
            const pageUrl = new URL(window.location.href);
            pageUrl.searchParams.delete('download');
            if (pageUrl.origin !== window.location.origin) {
                throw new Error('Cross-origin refresh rejected.');
            }
            const response = await fetch(pageUrl.href, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                redirect: 'error',
                headers: { Accept: 'text/html' },
                signal: request.controller.signal,
            });
            const contentType = response.headers.get('content-type') || '';
            if (!response.ok || !contentType.toLowerCase().includes('text/html')) {
                throw new Error('Refresh response unavailable.');
            }
            const html = await response.text();
            if (request.timedOut) {
                throw new Error('Refresh request timed out.');
            }
            if (activeRequest !== request || request.generation !== requestGeneration || !canRefresh()) {
                return;
            }
            applyPage(html);
            if (lastRefreshFailed) {
                setStatus('Automatic refresh resumed.');
            }
            lastRefreshFailed = false;
        } catch (error) {
            if ((!request.controller.signal.aborted || request.timedOut)
                && activeRequest === request
                && request.generation === requestGeneration
                && canRefresh()) {
                lastRefreshFailed = true;
                setStatus(request.timedOut
                    ? 'Refresh timed out; showing the last successful data.'
                    : 'Refresh failed; showing the last successful data.');
            }
        } finally {
            clearTimeout(request.timeout);
            if (activeRequest === request) {
                activeRequest = null;
            }
            if (canRefresh()) {
                schedule(POLL_DELAY_MS);
            }
        }
    }

    toggle.addEventListener('click', () => {
        userPaused = !userPaused;
        toggle.setAttribute('aria-pressed', String(userPaused));
        toggle.textContent = userPaused ? 'Resume updates' : 'Pause updates';
        if (userPaused) {
            clearTimer();
            invalidateRequest();
            setStatus('Automatic refresh paused.');
        } else {
            setStatus('Automatic refresh resumed.');
            schedule(0);
        }
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            clearTimer();
            invalidateRequest();
        } else if (!userPaused && !interactionPaused) {
            setStatus('Automatic refresh resumed.');
            schedule(0);
        }
    });
    document.addEventListener('focusin', syncInteractionPause);
    document.addEventListener('focusout', () => setTimeout(syncInteractionPause, 0));
    document.addEventListener('selectionchange', syncInteractionPause);

    controls.hidden = false;
    setStatus('Automatic refresh is on.');
    schedule(POLL_DELAY_MS);
})();
