<?php
/**
 * Sustaina - Geofence & Live Map Module
 * Uses OpenStreetMap + Leaflet.js
 */
if (!defined('Sustaina_ENTRY')) {
    die("Direct access not permitted.");
}

// Fetch marketplace items with locations for map pins
$stmt = $pdo->prepare("SELECT * FROM inventory WHERE listed_on_market = 1 ORDER BY expiry_date ASC");
$stmt->execute();
$map_items = $stmt->fetchAll();
?>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .geofence-wrapper {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .map-controls-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .map-stats {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .map-stat-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text-secondary);
        box-shadow: var(--glass-shadow);
        border: 1px solid var(--border-color);
    }

    .map-stat-chip .chip-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

    .map-stat-chip .chip-dot.green { background: var(--primary); box-shadow: 0 0 6px rgba(6,78,59,0.4); }
    .map-stat-chip .chip-dot.orange { background: #f59e0b; box-shadow: 0 0 6px rgba(245,158,11,0.4); }
    .map-stat-chip .chip-dot.red { background: #ef4444; box-shadow: 0 0 6px rgba(239,68,68,0.4); }

    .geofence-radius-control {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .geofence-radius-control label {
        font-size: 0.8rem;
        font-weight: 700;
        color: var(--text-secondary);
    }

    .geofence-radius-control input[type="range"] {
        -webkit-appearance: none;
        appearance: none;
        width: 160px;
        height: 6px;
        border-radius: 3px;
        background: var(--border-color);
        outline: none;
    }

    .geofence-radius-control input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: var(--primary);
        cursor: pointer;
        box-shadow: 0 2px 6px rgba(6,78,59,0.3);
    }

    .radius-value {
        font-size: 0.8rem;
        font-weight: 800;
        color: var(--primary);
        min-width: 50px;
    }

    #geofence-map {
        width: 100%;
        height: 550px;
        border-radius: var(--radius-xl);
        border: 1px solid var(--border-color);
        overflow: hidden;
        box-shadow: 0 8px 30px rgba(0,0,0,0.06);
        z-index: 1;
    }

    .map-legend {
        display: flex;
        gap: 1.5rem;
        flex-wrap: wrap;
        padding: 0.75rem 0;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--text-muted);
    }

    .legend-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid;
    }

    .locate-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1rem;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: var(--radius-lg);
        font-weight: 700;
        font-size: 0.8rem;
        cursor: pointer;
        transition: var(--transition-fast);
        box-shadow: 0 2px 8px rgba(6,78,59,0.15);
    }

    .locate-btn:hover {
        background: var(--primary-hover);
        transform: translateY(-1px);
    }

    .leaflet-popup-content-wrapper {
        border-radius: 12px !important;
        box-shadow: 0 8px 24px rgba(0,0,0,0.12) !important;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
    }

    .leaflet-popup-content {
        margin: 12px 16px !important;
        font-size: 0.85rem !important;
        line-height: 1.5 !important;
    }

    .popup-title {
        font-weight: 800;
        font-size: 0.95rem;
        color: #111827;
        margin-bottom: 4px;
    }

    .popup-meta {
        font-size: 0.75rem;
        color: #6b7280;
    }

    .popup-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 6px;
        font-size: 0.65rem;
        font-weight: 700;
        margin-top: 4px;
    }
</style>

<div class="geofence-wrapper">
    <div class="card no-hover">
        <div class="map-controls-bar">
            <div class="map-stats">
                <div class="map-stat-chip">
                    <span class="chip-dot green"></span>
                    <span id="stat-listings"><?= count($map_items) ?> Listings Nearby</span>
                </div>
                <div class="map-stat-chip">
                    <span class="chip-dot orange"></span>
                    <span id="stat-location">Locating...</span>
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                <div class="geofence-radius-control">
                    <label>Geofence Radius:</label>
                    <input type="range" id="radius-slider" min="200" max="5000" step="100" value="1500">
                    <span class="radius-value" id="radius-display">1.5 km</span>
                </div>
                <button class="locate-btn" onclick="locateUser()">
                    <i data-lucide="crosshair" style="width:14px; height:14px;"></i> Re-center
                </button>
            </div>
        </div>
    </div>

    <div id="geofence-map"></div>

    <div class="map-legend">
        <div class="legend-item">
            <span class="legend-dot" style="background: rgba(6,78,59,0.2); border-color: var(--primary);"></span>
            Your Location
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background: rgba(239,68,68,0.2); border-color: #ef4444;"></span>
            Expiring Soon
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background: rgba(34,197,94,0.2); border-color: #22c55e;"></span>
            Fresh Surplus
        </div>
        <div class="legend-item">
            <span class="legend-dot" style="background: rgba(6,78,59,0.06); border-color: var(--primary);"></span>
            Geofence Zone
        </div>
    </div>
</div>

<script>
(function() {
    // Default location: center of Philippines (Manila-ish fallback)
    const defaultLat = 14.5995;
    const defaultLng = 120.9842;

    const map = L.map('geofence-map', {
        zoomControl: false
    }).setView([defaultLat, defaultLng], 14);

    // Add zoom control to top-right
    L.control.zoom({ position: 'topright' }).addTo(map);

    // OpenStreetMap tile layer with a clean style
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19,
    }).addTo(map);

    // Custom icons
    function createIcon(color, size = 12) {
        return L.divIcon({
            className: 'custom-marker',
            html: `<div style="
                width: ${size}px; height: ${size}px; 
                border-radius: 50%; 
                background: ${color}; 
                border: 3px solid white; 
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
            "></div>`,
            iconSize: [size + 6, size + 6],
            iconAnchor: [(size + 6)/2, (size + 6)/2],
            popupAnchor: [0, -(size + 6)/2]
        });
    }

    const userIcon = createIcon('#064e3b', 16);
    const freshIcon = createIcon('#22c55e', 12);
    const expireIcon = createIcon('#ef4444', 12);

    let userMarker = null;
    let geofenceCircle = null;
    let currentRadius = 1500;

    // Geofence circle
    function drawGeofence(lat, lng) {
        if (geofenceCircle) map.removeLayer(geofenceCircle);
        geofenceCircle = L.circle([lat, lng], {
            radius: currentRadius,
            color: '#064e3b',
            fillColor: '#064e3b',
            fillOpacity: 0.06,
            weight: 2,
            dashArray: '8, 6',
        }).addTo(map);
    }

    // Marketplace item data from PHP
    const items = <?= json_encode(array_map(function($item) {
        return [
            'name' => $item['name'],
            'category' => $item['category'],
            'seller' => $item['seller'],
            'qty' => $item['qty'],
            'expiry' => $item['expiry_date'],
            'price' => floatval($item['market_price']),
            'location' => $item['location'] ?? '',
        ];
    }, $map_items)) ?>;

    // Scatter marketplace items around user location with slight random offsets
    function placeMarketItems(centerLat, centerLng) {
        items.forEach(function(item, i) {
            // Spread items within the geofence radius with random offset
            const angle = (2 * Math.PI * i / items.length) + (Math.random() * 0.5);
            const dist = (Math.random() * 0.6 + 0.2) * (currentRadius / 111000); // deg offset
            const lat = centerLat + dist * Math.cos(angle);
            const lng = centerLng + dist * Math.sin(angle) / Math.cos(centerLat * Math.PI / 180);

            const today = new Date();
            const expiry = new Date(item.expiry);
            const daysLeft = Math.ceil((expiry - today) / (1000*60*60*24));
            const isExpiring = daysLeft <= 3;

            const icon = isExpiring ? expireIcon : freshIcon;
            const priceTag = item.price == 0 ? '<span style="color:#22c55e;font-weight:800;">FREE</span>' : '₱' + item.price.toFixed(2);
            const statusBadge = isExpiring 
                ? '<span class="popup-badge" style="background:rgba(239,68,68,0.1);color:#ef4444;">⏰ ' + (daysLeft <= 0 ? 'Expired' : daysLeft + 'd left') + '</span>'
                : '<span class="popup-badge" style="background:rgba(34,197,94,0.1);color:#22c55e;">✅ Fresh (' + daysLeft + 'd left)</span>';

            L.marker([lat, lng], { icon: icon })
                .addTo(map)
                .bindPopup(`
                    <div class="popup-title">${item.name}</div>
                    <div class="popup-meta">${item.seller} &bull; ${item.category} &bull; ${priceTag}</div>
                    <div class="popup-meta">Qty: ${item.qty} &bull; ${item.location}</div>
                    ${statusBadge}
                `);
        });
    }

    // Locate user
    function locateUser() {
        document.getElementById('stat-location').textContent = 'Locating...';
        if (!navigator.geolocation) {
            document.getElementById('stat-location').textContent = 'Geolocation not supported';
            initWithDefault();
            return;
        }

        navigator.geolocation.getCurrentPosition(function(pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;
            setUserPosition(lat, lng);
            document.getElementById('stat-location').textContent = 
                lat.toFixed(4) + ', ' + lng.toFixed(4);
        }, function(err) {
            console.warn('Geolocation error:', err.message);
            document.getElementById('stat-location').textContent = 'Using default (Manila)';
            initWithDefault();
        }, { enableHighAccuracy: true, timeout: 10000 });
    }

    function setUserPosition(lat, lng) {
        if (userMarker) map.removeLayer(userMarker);

        userMarker = L.marker([lat, lng], { icon: userIcon })
            .addTo(map)
            .bindPopup('<div class="popup-title">📍 Your Location</div><div class="popup-meta">You are here</div>')
            .openPopup();

        drawGeofence(lat, lng);
        map.setView([lat, lng], 14);
        placeMarketItems(lat, lng);
    }

    function initWithDefault() {
        setUserPosition(defaultLat, defaultLng);
    }

    // Radius slider
    document.getElementById('radius-slider').addEventListener('input', function(e) {
        currentRadius = parseInt(e.target.value);
        const km = (currentRadius / 1000).toFixed(1);
        document.getElementById('radius-display').textContent = km + ' km';

        if (userMarker) {
            const pos = userMarker.getLatLng();
            drawGeofence(pos.lat, pos.lng);
        }
    });

    // Make locateUser globally accessible
    window.locateUser = locateUser;

    // Init
    locateUser();

    // Re-init lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
})();
</script>
