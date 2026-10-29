import './bootstrap';

import Alpine from 'alpinejs';
window.Alpine = Alpine;

import Chart from 'chart.js/auto';
window.Chart = Chart;

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';
window.L = L;

Alpine.start();

// Fix Leaflet icon paths when bundled with Vite
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

/**
 * Initialize a Leaflet world map with server markers.
 */
window.initServerMap = function (containerId, markers, tileUrl, playerMarkers, centerLat, centerLng, zoom) {
    const map = L.map(containerId, { scrollWheelZoom: false }).setView([centerLat || 46.6, centerLng || 1.9], zoom || 5);

    L.tileLayer(tileUrl || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map);

    const onlineIcon  = L.divIcon({ className: '', html: '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:var(--status-online);border:2px solid #fff;"></span>' });
    const offlineIcon = L.divIcon({ className: '', html: '<span style="display:inline-block;width:12px;height:12px;border-radius:50%;background:var(--status-offline);border:2px solid #fff;"></span>' });
    const playerIcon  = L.divIcon({ className: '', html: '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#58d9f0;border:1px solid #fff;opacity:0.85;"></span>' });

    markers.forEach(function (m) {
        const icon = m.online ? onlineIcon : offlineIcon;
        L.marker([m.lat, m.lng], { icon })
            .addTo(map)
            .bindPopup('<strong>' + m.name + '</strong><br>' + m.address);
    });

    if (playerMarkers && playerMarkers.length) {
        playerMarkers.forEach(function (p) {
            L.marker([p.lat, p.lng], { icon: playerIcon })
                .addTo(map)
                .bindPopup('<strong>' + p.name + '</strong>' + (p.country ? '<br>' + p.country : ''));
        });
    }
};

/**
 * Initialize a Chart.js activity line chart.
 */
window.initActivityChart = function (canvasId, labels, data, label) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;

    const style = getComputedStyle(document.documentElement);
    const lineColor = style.getPropertyValue('--chart-line').trim() || '#3fb950';
    const gridColor = style.getPropertyValue('--chart-grid').trim() || '#1e293b';

    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: label || 'Activity',
                data,
                borderColor: lineColor,
                backgroundColor: lineColor + '22',
                borderWidth: 1.5,
                fill: true,
                tension: 0.3,
                pointRadius: 2,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: '#8b949e', font: { size: 10 }, maxTicksLimit: 12 }, grid: { color: gridColor } },
                y: { ticks: { color: '#8b949e', font: { size: 10 } }, grid: { color: gridColor }, beginAtZero: true },
            },
        },
    });
};

window.initSkillChart = function (canvasId, labels, data) {
    window.initActivityChart(canvasId, labels, data, 'Skill');
};

function initSidebarNavigation() {
    const shell = document.getElementById('hlxAppShell');
    if (!shell) return;

    const sidebar = shell.querySelector('.hlx-app-sidebar');
    if (!sidebar) return;

    const mobileQuery = window.matchMedia('(max-width: 768px)');
    const storageKey = 'hlx_nav_collapsed';

    const setCollapsed = (collapsed) => {
        shell.classList.toggle('hlx-nav-collapsed', collapsed);
        sidebar.classList.toggle('is-collapsed', collapsed);
    };

    const setMobileOpen = (open) => {
        shell.classList.toggle('hlx-nav-open', open);
        sidebar.classList.toggle('is-open', open);
    };

    let savedCollapsed = false;
    try {
        savedCollapsed = localStorage.getItem(storageKey) === 'true';
    } catch {
        savedCollapsed = false;
    }

    setCollapsed(savedCollapsed);
    setMobileOpen(false);

    shell.querySelectorAll('[data-nav-collapse-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const collapsed = !shell.classList.contains('hlx-nav-collapsed');
            setCollapsed(collapsed);
            try {
                localStorage.setItem(storageKey, collapsed ? 'true' : 'false');
            } catch {
                // Ignore storage failures (private mode, blocked storage).
            }
        });
    });

    shell.querySelectorAll('[data-nav-mobile-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            setMobileOpen(!shell.classList.contains('hlx-nav-open'));
        });
    });

    shell.querySelectorAll('[data-nav-overlay]').forEach((overlay) => {
        overlay.addEventListener('click', () => setMobileOpen(false));
    });

    shell.querySelectorAll('.hlx-app-sidebar a, .hlx-app-sidebar form button').forEach((item) => {
        item.addEventListener('click', () => {
            if (mobileQuery.matches) {
                setMobileOpen(false);
            }
        });
    });

    const onViewportChange = (event) => {
        if (!event.matches) {
            setMobileOpen(false);
        }
    };

    if (mobileQuery.addEventListener) {
        mobileQuery.addEventListener('change', onViewportChange);
    } else {
        mobileQuery.addListener(onViewportChange);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSidebarNavigation);
} else {
    initSidebarNavigation();
}

/**
 * Cookie notice. Only strictly necessary cookies are set by the application,
 * so this banner informs the visitor and records the acknowledgement; nothing
 * on the page is blocked while it is displayed.
 */
function initCookieBanner() {
    const banner = document.querySelector('[data-cookie-banner]');
    if (!banner) return;

    const cookieName = 'hlx_consent';
    const maxAge = 60 * 60 * 24 * 180; // 180 days

    const hasConsent = document.cookie
        .split('; ')
        .some((entry) => entry.startsWith(cookieName + '='));

    if (!hasConsent) {
        banner.hidden = false;
    }

    banner.querySelectorAll('[data-cookie-accept]').forEach((button) => {
        button.addEventListener('click', () => {
            const secure = window.location.protocol === 'https:' ? '; Secure' : '';
            document.cookie = `${cookieName}=1; Max-Age=${maxAge}; Path=/; SameSite=Lax${secure}`;
            banner.hidden = true;
        });
    });

    // Footer link that lets the visitor read the notice again.
    document.querySelectorAll('[data-cookie-reopen]').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            banner.hidden = false;
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCookieBanner);
} else {
    initCookieBanner();
}
