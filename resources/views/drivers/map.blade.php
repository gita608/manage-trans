@extends('layouts.app')

@section('title', 'Driver Locations Map | ' . config('app.name'))

@push('styles')
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""/>
<style>
    /* Layout & Container */
    .map-wrapper {
        display: flex;
        height: calc(100vh - 140px); /* Adjust based on header/footer height */
        min-height: 600px;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border: 1px solid #eef0f3;
    }

    /* Sidebar Styles */
    .map-sidebar {
        width: 320px;
        flex-shrink: 0;
        display: flex;
        flex-direction: column;
        border-right: 1px solid #eef0f3;
        background: #fff;
        z-index: 2;
    }

    .sidebar-header {
        padding: 20px;
        border-bottom: 1px solid #eef0f3;
        background: #fff;
    }

    .stats-row {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
    }

    .stat-card {
        flex: 1;
        background: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        text-align: center;
    }

    .stat-value {
        font-size: 18px;
        font-weight: 700;
        color: #1f2937;
        line-height: 1.2;
    }

    .stat-label {
        font-size: 11px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .search-box {
        position: relative;
    }

    .search-box i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
    }

    .search-input {
        width: 100%;
        padding: 10px 10px 10px 36px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s;
    }

    .search-input:focus {
        outline: none;
        border-color: #405189;
        box-shadow: 0 0 0 3px rgba(64, 81, 137, 0.1);
    }

    .driver-list {
        flex: 1;
        overflow-y: auto;
        padding: 10px;
    }

    .driver-item {
        display: flex;
        align-items: center;
        padding: 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
        margin-bottom: 4px;
    }

    .driver-item:hover {
        background-color: #f3f4f6;
    }

    .driver-item.active {
        background-color: #eff6ff;
        border-color: #bfdbfe;
    }

    .driver-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-weight: 600;
        color: #4b5563;
        flex-shrink: 0;
        position: relative;
    }

    .status-dot {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid #fff;
    }

    .status-dot.online { background-color: #10b981; }
    .status-dot.offline { background-color: #ef4444; }

    .driver-details {
        flex: 1;
        min-width: 0; /* For text truncation */
    }

    .driver-name {
        font-weight: 600;
        color: #1f2937;
        font-size: 14px;
        margin-bottom: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .driver-meta {
        font-size: 12px;
        color: #6b7280;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Map Area */
    .map-container {
        flex: 1;
        position: relative;
        z-index: 1;
    }

    #map {
        width: 100%;
        height: 100%;
    }

    /* Map Controls Overlay */
    .map-controls {
        position: absolute;
        top: 20px;
        right: 20px;
        z-index: 1000;
        background: white;
        padding: 8px;
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* Custom Popup */
    .leaflet-popup-content-wrapper {
        border-radius: 8px;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        padding: 0;
        overflow: hidden;
    }
    
    .leaflet-popup-content {
        margin: 0;
        width: 260px !important;
    }

    .popup-header {
        background: #f8f9fa;
        padding: 12px 16px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .popup-title {
        font-weight: 600;
        color: #111827;
        margin: 0;
        font-size: 14px;
    }

    .popup-body {
        padding: 16px;
    }

    .popup-row {
        display: flex;
        margin-bottom: 8px;
        font-size: 13px;
    }

    .popup-label {
        color: #6b7280;
        width: 80px;
        flex-shrink: 0;
    }

    .popup-value {
        color: #374151;
        font-weight: 500;
    }

    .popup-actions {
        padding: 12px 16px;
        background: #f9fafb;
        border-top: 1px solid #e5e7eb;
    }

    /* Custom Marker */
    .custom-marker-pin {
        width: 36px;
        height: 36px;
        border-radius: 50% 50% 50% 0;
        background: #405189;
        position: absolute;
        transform: rotate(-45deg);
        left: 50%;
        top: 50%;
        margin: -15px 0 0 -15px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        border: 2px solid white;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .custom-marker-pin::after {
        content: '';
        width: 10px;
        height: 10px;
        margin: 3px 0 0 3px;
        background: #fff;
        position: absolute;
        border-radius: 50%;
    }

    .marker-icon {
        transform: rotate(45deg);
        color: white;
        font-size: 16px;
    }
    
    /* Auto refresh indicator */
    .refresh-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
        transition: all 0.3s;
    }
    
    .refresh-badge.active {
        background-color: #ecfdf5;
        color: #059669;
    }
    
    .refresh-badge.inactive {
        background-color: #f3f4f6;
        color: #6b7280;
    }

    .pulse-dot {
        width: 6px;
        height: 6px;
        background-color: currentColor;
        border-radius: 50%;
        margin-right: 6px;
    }
    
    .refresh-badge.active .pulse-dot {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.4; }
        100% { opacity: 1; }
    }

    /* Driver Name Tooltip */
     .driver-name-tooltip {
         background: transparent;
         border: none;
         box-shadow: none;
         font-weight: 700;
         color: #1f2937;
         text-shadow: 
             -1px -1px 0 #fff,  
              1px -1px 0 #fff,
             -1px  1px 0 #fff,
              1px  1px 0 #fff;
         font-size: 12px;
         margin-top: 0;
     }

     /* Theme Selector */
     .theme-selector {
         position: relative;
     }

     .theme-selector select {
         appearance: none;
         background: white;
         border: 1px solid #e5e7eb;
         border-radius: 6px;
         padding: 6px 28px 6px 10px;
         font-size: 12px;
         font-weight: 500;
         color: #374151;
         cursor: pointer;
         transition: all 0.2s;
     }

     .theme-selector select:hover {
         border-color: #405189;
     }

     .theme-selector select:focus {
         outline: none;
         border-color: #405189;
         box-shadow: 0 0 0 3px rgba(64, 81, 137, 0.1);
     }

     .theme-selector::after {
         content: '\ea4e';
         font-family: 'remixicon';
         position: absolute;
         right: 8px;
         top: 50%;
         transform: translateY(-50%);
         pointer-events: none;
         color: #6b7280;
         font-size: 14px;
     }
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
                <div class="sidebar-header">
                    <div class="stats-row">
                        <div class="stat-card">
                            <div class="stat-value" id="totalDrivers">0</div>
                            <div class="stat-label">Total</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value text-success" id="onlineDrivers">0</div>
                            <div class="stat-label">Online</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value text-danger" id="offlineDrivers">0</div>
                            <div class="stat-label">Offline</div>
                        </div>
                    </div>
                    <div class="search-box">
                        <i class="ri-search-line"></i>
                        <input type="text" id="driverSearch" class="search-input" placeholder="Search drivers...">
                    </div>
                </div>
                <div class="driver-list" id="driverList">
                    <!-- Driver items will be populated here -->
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
                    <div class="theme-selector">
                        <select id="themeSelector" title="Change Map Theme">
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                            <option value="voyager">Voyager</option>
                            <option value="osm">OpenStreetMap</option>
                        </select>
                    </div>
                    
                    <div class="refresh-badge active" id="autoRefreshBadge">
                        <div class="pulse-dot"></div>
                        <span>Live Updates</span>
                    </div>
                    
                    <div class="form-check form-switch mb-0 ms-2">
                        <input class="form-check-input" type="checkbox" id="autoRefresh" checked>
                    </div>
                    
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
    const REFRESH_INTERVAL = 30000; // 30 seconds

    // Map theme configurations
    const mapThemes = {
        light: {
            url: 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
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
        const savedTheme = localStorage.getItem('mapTheme') || 'light';
        changeMapTheme(savedTheme);
        document.getElementById('themeSelector').value = savedTheme;

        // Load initial driver locations
        loadDriverLocations();
    }

    // Change map theme
    function changeMapTheme(theme) {
        if (!mapThemes[theme]) {
            theme = 'light'; // Fallback to light if invalid theme
        }

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
            const isOnline = isRecent(driver.updated_at);

            // Determine marker color
            const markerColor = isOnline ? '#ef4444' : '#6b7280'; // Red for Online, Gray for Offline

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
                    <span class="badge ${isOnline ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'}">
                        ${isOnline ? 'Online' : 'Offline'}
                    </span>
                </div>
                <div class="popup-body">
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
        const searchTerm = document.getElementById('driverSearch').value.toLowerCase();
        
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
            const isOnline = isRecent(driver.updated_at);
            const initials = driver.name.substring(0, 2).toUpperCase();
            
            html += `
                <div class="driver-item" onclick="focusDriver(${driver.id})">
                    <div class="driver-avatar">
                        ${initials}
                        <span class="status-dot ${isOnline ? 'online' : 'offline'}"></span>
                    </div>
                    <div class="driver-details">
                        <div class="driver-name">${driver.name}</div>
                        <div class="driver-meta">
                            <span>${driver.type_label}</span>
                            <span>•</span>
                            <span>${driver.updated_at_human}</span>
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
        const online = drivers.filter(d => isRecent(d.updated_at)).length;
        const offline = total - online;

        document.getElementById('totalDrivers').textContent = total;
        document.getElementById('onlineDrivers').textContent = online;
        document.getElementById('offlineDrivers').textContent = offline;
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

    // Check if location is recent (within last 5 minutes)
    function isRecent(updatedAt) {
        const updated = new Date(updatedAt);
        const now = new Date();
        const diffMinutes = (now - updated) / (1000 * 60);
        return diffMinutes < 5;
    }

    // Search functionality
    document.getElementById('driverSearch').addEventListener('input', function() {
        updateDriverList(driversData);
    });

    // Theme selector
    document.getElementById('themeSelector').addEventListener('change', function() {
        changeMapTheme(this.value);
    });

    // Auto Refresh Logic
    function setupAutoRefresh() {
        const checkbox = document.getElementById('autoRefresh');
        const badge = document.getElementById('autoRefreshBadge');

        checkbox.addEventListener('change', function() {
            if (this.checked) {
                startAutoRefresh();
                badge.classList.remove('inactive');
                badge.classList.add('active');
                badge.querySelector('span').textContent = 'Live Updates';
            } else {
                stopAutoRefresh();
                badge.classList.remove('active');
                badge.classList.add('inactive');
                badge.querySelector('span').textContent = 'Updates Paused';
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
    document.getElementById('refreshBtn').addEventListener('click', function() {
        const icon = this.querySelector('i');
        icon.classList.add('ri-spin-line'); // Add spin animation
        loadDriverLocations();
        setTimeout(() => icon.classList.remove('ri-spin-line'), 1000);
    });

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
        setupAutoRefresh();
    });
</script>
@endpush

