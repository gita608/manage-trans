@extends('layouts.app')

@section('title', 'Driver Locations Map | ' . config('app.name'))

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""/>
<style>
    /* ===== LAYOUT ===== */
    .map-page-wrapper {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .map-wrapper {
        display: flex;
        height: calc(100vh - 155px);
        min-height: 620px;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04);
        border: 1px solid #e2e8f0;
    }

    /* ===== SIDEBAR ===== */
    .map-sidebar {
        width: 340px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        background: #f8fafc;
        border-right: 1px solid #e2e8f0;
        z-index: 2;
    }

    .sidebar-brand {
        padding: 18px 20px 14px;
        background: linear-gradient(135deg, #405189 0%, #2d3e73 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .sidebar-brand-title {
        font-size: 15px;
        font-weight: 700;
        color: #fff;
        letter-spacing: -0.01em;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sidebar-brand-title i {
        font-size: 18px;
        opacity: 0.9;
    }
    .sidebar-brand-sub {
        font-size: 11px;
        color: rgba(255,255,255,0.65);
        margin-top: 2px;
        font-weight: 400;
    }

    /* ===== STAT CARDS ===== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
        background: linear-gradient(135deg, #405189 0%, #2d3e73 100%);
        padding: 0 16px 16px;
    }
    .stat-card {
        background: rgba(255,255,255,0.12);
        backdrop-filter: blur(8px);
        border-radius: 10px;
        padding: 12px 10px;
        text-align: center;
        border: 1px solid rgba(255,255,255,0.18);
        transition: background 0.2s;
    }
    .stat-card:hover {
        background: rgba(255,255,255,0.18);
    }
    .stat-value {
        font-size: 22px;
        font-weight: 800;
        color: #fff;
        line-height: 1;
        margin-bottom: 4px;
    }
    .stat-value.free  { color: #6ee7b7; }
    .stat-value.busy  { color: #fca5a5; }
    .stat-label {
        font-size: 10px;
        color: rgba(255,255,255,0.7);
        text-transform: uppercase;
        letter-spacing: 0.07em;
        font-weight: 600;
    }
    .stat-dot {
        display: inline-block;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        margin-right: 3px;
        vertical-align: middle;
    }
    .stat-dot.free { background: #6ee7b7; }
    .stat-dot.busy { background: #fca5a5; }

    /* ===== SIDEBAR BODY ===== */
    .sidebar-body {
        padding: 14px 16px;
        border-bottom: 1px solid #e2e8f0;
        background: #fff;
    }

    /* ===== SEARCH ===== */
    .search-box {
        position: relative;
    }
    .search-box i {
        position: absolute;
        left: 11px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        font-size: 15px;
        pointer-events: none;
    }
    .search-input {
        width: 100%;
        padding: 9px 12px 9px 34px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        font-size: 13.5px;
        background: #f8fafc;
        color: #1e293b;
        transition: all 0.2s;
    }
    .search-input:focus {
        outline: none;
        border-color: #405189;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(64,81,137,0.1);
    }
    .search-input::placeholder { color: #94a3b8; }

    /* ===== STATUS LEGEND ===== */
    .status-legend {
        margin-top: 12px;
        padding: 10px 13px;
        background: #f0f9ff;
        border-radius: 10px;
        border: 1px solid #bae6fd;
    }
    .legend-title {
        font-size: 10.5px;
        font-weight: 700;
        color: #0369a1;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .legend-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 5px;
        font-size: 12px;
        line-height: 1.45;
        color: #374151;
    }
    .legend-item:last-child { margin-bottom: 0; }
    .legend-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 3px;
        box-shadow: 0 0 0 2px rgba(255,255,255,0.8);
    }
    .legend-dot.free { background: #10b981; box-shadow: 0 0 0 2px rgba(16,185,129,0.2); }
    .legend-dot.busy { background: #ef4444; box-shadow: 0 0 0 2px rgba(239,68,68,0.2); }

    /* ===== DRIVER LIST ===== */
    .driver-list-header {
        padding: 10px 16px 4px;
        font-size: 10.5px;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
    }

    .driver-list {
        flex: 1;
        overflow-y: auto;
        padding: 8px;
        background: #f8fafc;
    }
    .driver-list::-webkit-scrollbar { width: 4px; }
    .driver-list::-webkit-scrollbar-track { background: transparent; }
    .driver-list::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

    .driver-item {
        display: flex;
        align-items: center;
        padding: 11px 12px;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.18s ease;
        border: 1px solid transparent;
        margin-bottom: 4px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .driver-item:hover {
        background: #eff6ff;
        border-color: #bfdbfe;
        box-shadow: 0 2px 8px rgba(64,81,137,0.1);
        transform: translateX(2px);
    }
    .driver-item.active {
        background: #eff6ff;
        border-color: #93c5fd;
        box-shadow: 0 2px 8px rgba(64,81,137,0.12);
    }

    /* ===== DRIVER AVATAR ===== */
    .driver-avatar {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-weight: 700;
        font-size: 14px;
        color: #fff;
        flex-shrink: 0;
        position: relative;
        background: linear-gradient(135deg, #405189 0%, #667eea 100%);
        box-shadow: 0 2px 6px rgba(64,81,137,0.25);
    }
    .driver-avatar.avatar-busy {
        background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
        box-shadow: 0 2px 6px rgba(239,68,68,0.25);
    }
    .driver-avatar.avatar-free {
        background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        box-shadow: 0 2px 6px rgba(16,185,129,0.25);
    }

    .status-dot {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 11px;
        height: 11px;
        border-radius: 50%;
        border: 2px solid #f8fafc;
    }
    .status-dot.free { background: #10b981; }
    .status-dot.busy { background: #ef4444; }

    .driver-details { flex: 1; min-width: 0; }
    .driver-name {
        font-weight: 600;
        color: #0f172a;
        font-size: 13.5px;
        margin-bottom: 3px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .driver-meta {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: wrap;
    }
    .driver-type-tag {
        font-size: 10.5px;
        color: #64748b;
        font-weight: 500;
    }

    .availability-badge {
        display: inline-flex;
        align-items: center;
        gap: 3px;
        font-size: 10px;
        font-weight: 700;
        padding: 1px 7px;
        border-radius: 9999px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }
    .availability-badge.free { background: #d1fae5; color: #047857; }
    .availability-badge.busy { background: #fee2e2; color: #dc2626; }

    /* ===== MAP AREA ===== */
    .map-container {
        flex: 1;
        position: relative;
        z-index: 1;
    }
    #map { width: 100%; height: 100%; }

    /* ===== MAP CONTROLS TOOLBAR ===== */
    .map-controls {
        position: absolute;
        top: 16px;
        right: 16px;
        z-index: 1000;
        background: rgba(255,255,255,0.95);
        backdrop-filter: blur(8px);
        padding: 8px 12px;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.12), 0 1px 4px rgba(0,0,0,0.06);
        display: flex;
        align-items: center;
        gap: 10px;
        border: 1px solid rgba(0,0,0,0.06);
    }
    .controls-divider {
        width: 1px;
        height: 20px;
        background: #e2e8f0;
    }

    /* ===== POPUP STYLING ===== */
    .leaflet-popup-content-wrapper {
        border-radius: 14px;
        box-shadow: 0 12px 32px rgba(0,0,0,0.14), 0 2px 8px rgba(0,0,0,0.06);
        padding: 0;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    .leaflet-popup-content { margin: 0; width: 270px !important; }
    .leaflet-popup-tip-container { margin-top: -1px; }

    .popup-header {
        padding: 14px 16px 12px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #f1f5f9;
    }
    .popup-header.header-busy  { background: linear-gradient(135deg, #fff5f5 0%, #fff 100%); }
    .popup-header.header-free  { background: linear-gradient(135deg, #f0fdf4 0%, #fff 100%); }

    .popup-driver-info { display: flex; align-items: center; gap: 10px; }
    .popup-avatar {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        color: #fff;
        flex-shrink: 0;
    }
    .popup-avatar.busy-av { background: linear-gradient(135deg, #ef4444, #f87171); }
    .popup-avatar.free-av  { background: linear-gradient(135deg, #10b981, #34d399); }

    .popup-title {
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 2px;
        font-size: 14px;
    }
    .popup-body { padding: 12px 16px; }
    .popup-row {
        display: flex;
        align-items: center;
        margin-bottom: 7px;
        font-size: 12.5px;
        gap: 8px;
    }
    .popup-row:last-child { margin-bottom: 0; }
    .popup-row i { color: #94a3b8; font-size: 14px; flex-shrink: 0; }
    .popup-label { color: #64748b; width: 70px; flex-shrink: 0; font-size: 12px; }
    .popup-value { color: #1e293b; font-weight: 600; }
    .popup-actions {
        padding: 10px 16px;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
    }

    /* ===== CUSTOM MARKER ===== */
    .custom-marker-pin {
        width: 34px;
        height: 34px;
        border-radius: 50% 50% 50% 0;
        position: absolute;
        transform: rotate(-45deg);
        left: 50%;
        top: 50%;
        margin: -17px 0 0 -17px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        border: 2.5px solid rgba(255,255,255,0.9);
        transition: transform 0.2s;
    }
    .custom-marker-pin::after {
        content: '';
        width: 10px;
        height: 10px;
        margin: 3px 0 0 3px;
        background: rgba(255,255,255,0.8);
        position: absolute;
        border-radius: 50%;
    }

    /* ===== REFRESH BADGE ===== */
    .refresh-badge {
        display: inline-flex;
        align-items: center;
        padding: 5px 10px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        transition: all 0.25s;
        gap: 6px;
        white-space: nowrap;
    }
    .refresh-badge.active  { background: #ecfdf5; color: #059669; }
    .refresh-badge.inactive { background: #f1f5f9; color: #6b7280; }
    .pulse-dot {
        width: 6px; height: 6px;
        background: currentColor;
        border-radius: 50%;
        flex-shrink: 0;
    }
    .refresh-badge.active .pulse-dot { animation: pulse 1.8s ease-in-out infinite; }
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: 0.35; transform: scale(0.7); }
    }

    /* ===== DRIVER TOOLTIP ===== */
    .driver-name-tooltip {
        background: rgba(15,23,42,0.85) !important;
        border: none !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2) !important;
        border-radius: 6px !important;
        font-weight: 600;
        color: #fff !important;
        font-size: 11.5px;
        padding: 3px 8px !important;
        margin-top: 4px !important;
        white-space: nowrap;
    }
    .driver-name-tooltip::before { display: none !important; }

    /* ===== THEME SELECTOR ===== */
    .theme-selector { position: relative; }
    .theme-selector select {
        appearance: none;
        background: transparent;
        border: none;
        padding: 4px 22px 4px 6px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
        cursor: pointer;
    }
    .theme-selector select:focus { outline: none; }
    .theme-selector::after {
        content: '\ea4e';
        font-family: 'remixicon';
        position: absolute;
        right: 4px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
        color: #94a3b8;
        font-size: 13px;
    }

    /* ===== EMPTY STATE ===== */
    .driver-empty {
        padding: 30px 20px;
        text-align: center;
        color: #94a3b8;
    }
    .driver-empty i { font-size: 32px; margin-bottom: 8px; opacity: 0.5; display: block; }
    .driver-empty p { font-size: 13px; margin: 0; }
</style>
@endpush

@section('content')
<!-- start page title -->
<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Driver Locations Map</h4>

            <div class="page-title-right">
                <ol class="breadcrumb m-0">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('drivers.index') }}">Drivers</a></li>
                    <li class="breadcrumb-item active">Locations Map</li>
                </ol>
            </div>
        </div>
    </div>
</div>
<!-- end page title -->

<div class="row">
    <div class="col-12">
        <div class="map-wrapper">
            <!-- Sidebar -->
            <div class="map-sidebar">

                <!-- Brand Header -->
                <div class="sidebar-brand">
                    <div>
                        <div class="sidebar-brand-title">
                            <i class="ri-map-pin-2-fill"></i> Driver Locations
                        </div>
                        <div class="sidebar-brand-sub">Live availability — updates every 30s</div>
                    </div>
                    <i class="ri-live-line text-white opacity-75 fs-18"></i>
                </div>

                <!-- Stat Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value" id="totalDrivers">0</div>
                        <div class="stat-label">Total</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value free" id="freeDrivers">0</div>
                        <div class="stat-label"><span class="stat-dot free"></span>Free</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value busy" id="busyDrivers">0</div>
                        <div class="stat-label"><span class="stat-dot busy"></span>Busy</div>
                    </div>
                </div>

                <!-- Search & Legend -->
                <div class="sidebar-body">
                    <div class="search-box">
                        <i class="ri-search-line"></i>
                        <input type="text" id="driverSearch" class="search-input" placeholder="Search drivers...">
                    </div>

                    <!-- Status Criteria Legend -->
                    <div class="status-legend">
                        <div class="legend-title">
                            <i class="ri-information-line"></i> Status Criteria
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot free"></span>
                            <span><strong>Free</strong> — No active trips today, or all trips completed / cancelled</span>
                        </div>
                        <div class="legend-item">
                            <span class="legend-dot busy"></span>
                            <span><strong>Busy</strong> — Has at least one trip today that is <em>Assigned</em> or <em>In Progress</em></span>
                        </div>
                    </div>
                </div>

                <!-- Driver List -->
                <div class="driver-list-header">
                    <i class="ri-group-line me-1"></i> Drivers on Map
                </div>
                <div class="driver-list" id="driverList">
                    <div class="text-center p-4 text-muted">
                        <div class="spinner-border text-primary spinner-border-sm mb-2" role="status"></div>
                        <p class="mb-0 small">Loading drivers...</p>
                    </div>
                </div>
            </div>

            <!-- Map -->
            <div class="map-container">
                <div id="map"></div>
                
                <!-- Map Controls -->
                <div class="map-controls">
                    <i class="ri-map-2-line text-muted fs-16"></i>
                    <div class="theme-selector">
                        <select id="themeSelector" title="Change Map Theme">
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                            <option value="voyager">Voyager</option>
                            <option value="osm">OpenStreetMap</option>
                        </select>
                    </div>

                    <div class="controls-divider"></div>

                    <div class="refresh-badge active" id="autoRefreshBadge">
                        <div class="pulse-dot"></div>
                        <span>Live</span>
                    </div>
                    
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                    </div>
                    
                    <div class="controls-divider"></div>

                    <button type="button" class="btn btn-icon btn-sm btn-light shadow-none border" id="refreshBtn" title="Refresh Now">
                        <i class="ri-refresh-line"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""></script>

<script>
    let map;
    let markers = {};
    let driversData = [];
    let autoRefreshInterval = null;
    let currentTileLayer = null;
    let savedTheme = localStorage.getItem('mapTheme') || 'light';
    const REFRESH_INTERVAL = 30000; // 30 seconds

    // Map theme configurations
    const mapThemes = {
        light: {
            url: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}',
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri, DeLorme, NAVTEQ, USGS, Intermap, iPC, NRCAN, Esri Japan, METI, Esri China (Hong Kong), Esri (Thailand), TomTom, 2012',
            subdomains: '',
            maxZoom: 19
        },
        dark: {
            url: 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        },
        voyager: {
            url: 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        },
        osm: {
            url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            subdomains: '',
            maxZoom: 19
        }
    };

    // Initialize map
    function initMap() {
        // Default center (Dubai)
        map = L.map('map', {
            zoomControl: false
        }).setView([25.2048, 55.2708], 11);

        // Add Zoom Control to bottom right
        L.control.zoom({
            position: 'bottomright'
        }).addTo(map);

        // Load saved theme or default to light
        const themeSel = document.getElementById('themeSelector');
        if (themeSel) themeSel.value = savedTheme;
        changeMapTheme(savedTheme);

        // Load initial driver locations
        loadDriverLocations();
    }

    // Change map theme
    function changeMapTheme(theme) {
        if (!mapThemes[theme]) {
            theme = 'light'; // Fallback to light if invalid theme
        }

        localStorage.setItem('mapTheme', theme);
        const config = mapThemes[theme];
        
        // Remove current tile layer
        if (currentTileLayer) {
            map.removeLayer(currentTileLayer);
        }

        // Add new tile layer
        currentTileLayer = L.tileLayer(config.url, {
            attribution: config.attribution,
            subdomains: config.subdomains || 'abcd',
            maxZoom: config.maxZoom || 20
        }).addTo(map);

        // Save theme preference
        localStorage.setItem('mapTheme', theme);
    }

    // Load driver locations from API
    function loadDriverLocations() {
        fetch('{{ route("api.drivers.locations") }}')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    driversData = data.drivers;
                    updateMapMarkers(driversData);
                    updateDriverList(driversData);
                    updateStats(driversData);
                }
            })
            .catch(error => {
                console.error('Error loading driver locations:', error);
            });
    }

    // Update map markers
    function updateMapMarkers(drivers) {
        // Remove old markers
        Object.keys(markers).forEach(driverId => {
            map.removeLayer(markers[driverId]);
        });
        markers = {};

        if (drivers.length === 0) return;

        const bounds = [];

        drivers.forEach(driver => {
            const lat = driver.latitude;
            const lng = driver.longitude;
            const isBusy = driver.is_busy;

            // Determine marker color: red = busy, green = free
            const markerColor = isBusy ? '#ef4444' : '#10b981';

            // Create custom icon
            const customIcon = L.divIcon({
                className: 'custom-marker',
                html: `<div class="custom-marker-pin" style="background-color: ${markerColor}"></div>`,
                iconSize: [30, 42],
                iconAnchor: [15, 42],
                popupAnchor: [0, -35]
            });

            // Create marker
            const marker = L.marker([lat, lng], { icon: customIcon }).addTo(map);

            // Add permanent tooltip with driver name
            marker.bindTooltip(driver.name, {
                permanent: true, 
                direction: 'bottom',
                className: 'driver-name-tooltip',
                offset: [0, 5]
            });

            // Create popup content
            const popupContent = `
                <div class="popup-header">
                    <h6 class="popup-title">${driver.name}</h6>
                    <span class="availability-badge ${isBusy ? 'busy' : 'free'}">
                        ${isBusy ? '🔴 Busy' : '🟢 Free'}
                    </span>
                </div>
                <div class="popup-body">
                    <div class="popup-row">
                        <span class="popup-label">Status:</span>
                        <span class="popup-value" style="font-weight:600;color:${isBusy ? '#dc2626' : '#047857'}">
                            ${isBusy ? 'On Active Trip' : 'Available'}
                        </span>
                    </div>
                    <div class="popup-row">
                        <span class="popup-label">Type:</span>
                        <span class="popup-value">${driver.type_label}</span>
                    </div>
                    <div class="popup-row">
                        <span class="popup-label">Contact:</span>
                        <span class="popup-value">${driver.contact || 'N/A'}</span>
                    </div>
                    <div class="popup-row">
                        <span class="popup-label">Updated:</span>
                        <span class="popup-value">${driver.updated_at_human}</span>
                    </div>
                </div>
                <div class="popup-actions">
                    <a href="{{ url('drivers') }}/${driver.id}" class="btn btn-sm btn-primary w-100">
                        View Profile
                    </a>
                </div>
            `;

            marker.bindPopup(popupContent);
            markers[driver.id] = marker;
            bounds.push([lat, lng]);
        });

        // Fit map to show all markers only on first load or manual refresh if needed
        // For now, we keep the user's view unless it's the very first load
        if (bounds.length > 0 && !map.hasLayer(Object.values(markers)[0])) {
             map.fitBounds(bounds, { padding: [50, 50] });
        }
    }

    // Update Driver List Sidebar
    function updateDriverList(drivers) {
        const listContainer = document.getElementById('driverList');
        if (!listContainer) return;

        const searchInput = document.getElementById('driverSearch');
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        
        const filteredDrivers = drivers.filter(driver => 
            driver.name.toLowerCase().includes(searchTerm) || 
            (driver.contact && driver.contact.includes(searchTerm))
        );

        if (filteredDrivers.length === 0) {
            listContainer.innerHTML = `
                <div class="text-center p-4 text-muted">
                    <i class="ri-user-unfollow-line fs-24 mb-2"></i>
                    <p class="mb-0">No drivers found</p>
                </div>
            `;
            return;
        }

        let html = '';
        filteredDrivers.forEach(driver => {
            const isBusy = driver.is_busy;
            const initials = driver.name.substring(0, 2).toUpperCase();
            
            html += `
                <div class="driver-item" onclick="focusDriver(${driver.id})">
                    <div class="driver-avatar">
                        ${initials}
                        <span class="status-dot ${isBusy ? 'busy' : 'free'}"></span>
                    </div>
                    <div class="driver-details">
                        <div class="driver-name">${driver.name}</div>
                        <div class="driver-meta">
                            <span>${driver.type_label}</span>
                            <span>•</span>
                            <span class="availability-badge ${isBusy ? 'busy' : 'free'}" style="font-size:10px;padding:1px 6px;">
                                ${isBusy ? '🔴 Busy' : '🟢 Free'}
                            </span>
                        </div>
                    </div>
                    <div class="ms-2">
                        <i class="ri-arrow-right-s-line text-muted"></i>
                    </div>
                </div>
            `;
        });

        listContainer.innerHTML = html;
    }

    // Update Stats
    function updateStats(drivers) {
        const total = drivers.length;
        const busy  = drivers.filter(d => d.is_busy).length;
        const free  = total - busy;

        const elTotal = document.getElementById('totalDrivers');
        const elFree  = document.getElementById('freeDrivers');
        const elBusy  = document.getElementById('busyDrivers');

        if (elTotal) elTotal.textContent = total;
        if (elFree)  elFree.textContent  = free;
        if (elBusy)  elBusy.textContent  = busy;
    }

    // Focus on a specific driver
    window.focusDriver = function(driverId) {
        const marker = markers[driverId];
        if (marker) {
            map.flyTo(marker.getLatLng(), 15, {
                duration: 1.5
            });
            setTimeout(() => {
                marker.openPopup();
            }, 1500);
            
            // Highlight list item
            // (Optional: add active class logic here if needed)
        }
    };

    // Check if location is recent (within last 24 hours / 1 day)
    function isRecent(updatedAt) {
        const updated = new Date(updatedAt);
        const now = new Date();
        const diffHours = (now - updated) / (1000 * 60 * 60); // Convert to hours
        return diffHours < 24; // 24 hours = 1 day
    }

    // Search functionality
    document.getElementById('driverSearch')?.addEventListener('input', function() {
        updateDriverList(driversData);
    });

    // Theme selector
    document.getElementById('themeSelector')?.addEventListener('change', function() {
        changeMapTheme(this.value);
    });

    // Auto Refresh Logic
    function setupAutoRefresh() {
        const checkbox = document.getElementById('autoRefresh');
        const badge = document.getElementById('autoRefreshBadge');
        if (!checkbox) return;

        checkbox.addEventListener('change', function() {
            if (this.checked) {
                startAutoRefresh();
                if (badge) {
                    badge.classList.remove('inactive');
                    badge.classList.add('active');
                    badge.querySelector('span').textContent = 'Live Updates';
                }
            } else {
                stopAutoRefresh();
                if (badge) {
                    badge.classList.remove('active');
                    badge.classList.add('inactive');
                    badge.querySelector('span').textContent = 'Updates Paused';
                }
            }
        });

        if (checkbox.checked) {
            startAutoRefresh();
        }
    }

    function startAutoRefresh() {
        stopAutoRefresh();
        autoRefreshInterval = setInterval(() => {
            loadDriverLocations();
        }, REFRESH_INTERVAL);
    }

    function stopAutoRefresh() {
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
        }
    }

    // Manual Refresh
    document.getElementById('refreshBtn')?.addEventListener('click', function() {
        const icon = this.querySelector('i');
        if (icon) icon.classList.add('ri-spin-line');
        loadDriverLocations();
        setTimeout(() => icon && icon.classList.remove('ri-spin-line'), 1000);
    });

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        setupAutoRefresh();
    });
</script>
@endpush

