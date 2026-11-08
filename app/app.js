// App State
const AppState = {
    currentUser: null,
    currentPage: 'home',
    map: null,
    userLocation: null,
    markers: [],
    places: [], // All places data
    favorites: new Set(),
    activeFilters: new Set(['all']),
    searchResults: [],
    notifications: [
        { id: 1, title: 'Special Offer', message: 'Get 20% off at selected restaurants', time: '2 hours ago', icon: 'gift' },
        { id: 2, title: 'New Place Added', message: 'Al Marbad Farm is now available', time: '1 day ago', icon: 'map-marker-alt' }
    ]
};

// Sample Places Data (RESTORED)
const samplePlaces = [
    {
        id: 1,
        name: 'Al-Masjid an-Nabawi',
        type: 'mosque',
        category: 'Sacred Site',
        lat: 24.4672,
        lng: 39.6111,
        description: 'The Prophet\'s Mosque, second holiest site in Islam. A place of immense spiritual significance with beautiful architecture.',
        address: 'Al Haram, Madinah 42311',
        rating: 5.0,
        icon: 'fas fa-mosque',
        openingHours: 'Open 24/7',
        features: ['Prayer Area', 'Wudu Facilities', 'Library', 'Cafeteria'],
       
        emoji: '🕌'
    },
    {
        id: 2,
        name: 'Al Marbad Farm',
        type: 'experience',
        category: 'Farm Experience',
        lat: 24.3930,
        lng: 39.5140,
        description: 'Beautiful farm experience with traditional activities, horse riding, and family-friendly environment.',
        address: 'Al Marbad Area',
        rating: 4.7,
        icon: 'fas fa-tractor',
        openingHours: '8:00 AM - 10:00 PM',
        features: ['Horse Riding', 'Family Activities', 'Traditional Food', 'Photo Spots'],
    
        emoji: '🚜'
    },
    {
        id: 3,
        name: 'Row Café',
        type: 'cafe',
        category: 'Premium Cafe',
        lat: 24.4700,
        lng: 39.6100,
        description: 'Modern cafe with specialty coffee and premium atmosphere near Haram. Perfect for relaxation.',
        address: 'Haram Area',
        rating: 4.8,
        icon: 'fas fa-coffee',
        openingHours: '6:00 AM - 2:00 AM',
        features: ['Specialty Coffee', 'WiFi', 'Outdoor Seating', 'Desserts'],
      
        emoji: '☕'
    },
    {
        id: 4,
        name: 'Cow Ribs',
        type: 'restaurant',
        category: 'Steakhouse',
        lat: 24.4750,
        lng: 39.6050,
        description: 'Premium steakhouse famous for delicious ribs and grilled meats. Authentic flavors.',
        address: 'Prince Naif Road',
        rating: 4.8,
        icon: 'fas fa-utensils',
        openingHours: '12:00 PM - 1:00 AM',
        features: ['Grilled Meats', 'Family Style', 'Premium Cuts', 'Desserts'],
   
        emoji: '🍖'
    },
    {
        id: 5,
        name: 'Quba Mosque',
        type: 'mosque',
        category: 'Historical Site',
        lat: 24.4392,
        lng: 39.6179,
        description: 'First mosque built in Islamic history. Significant historical and spiritual site.',
        address: 'Al Hijrah Rd',
        rating: 4.9,
        icon: 'fas fa-mosque',
        openingHours: 'Open 24/7',
        features: ['Historical Site', 'Prayer Area', 'Parking', 'Guided Tours'],
   
        emoji: '🕌'
    },
    {
        id: 6,
        name: 'Bloom Roastery',
        type: 'cafe',
        category: 'Specialty Coffee',
        lat: 24.4720,
        lng: 39.6080,
        description: 'Artisanal coffee roastery with premium blends and cozy atmosphere.',
        address: 'Prince Naif Road',
        rating: 4.7,
        icon: 'fas fa-mug-hot',
        openingHours: '7:00 AM - 12:00 AM',
        features: ['Coffee Roasting', 'Workshops', 'Local Beans', 'Pastries'],
     
        emoji: '☕'
    }
];

// DOM Elements
const elements = {
    screens: {
        splash: document.getElementById('splash-screen'),
        auth: document.getElementById('auth-screen'),
        app: document.getElementById('app-screen')
    },
    auth: {
        loginForm: document.getElementById('login-form'),
        signupForm: document.getElementById('signup-form'),
        showSignup: document.getElementById('show-signup'),
        showLogin: document.getElementById('show-login'),
        loginBtn: document.getElementById('login-btn'),
        signupBtn: document.getElementById('signup-btn')
    },
    app: {
        pages: {
            home: document.getElementById('home-page'),
            explore: document.getElementById('explore-page'),
            card: document.getElementById('card-page'),
            profile: document.getElementById('profile-page')
        },
        navItems: document.querySelectorAll('.nav-item'),
        greetingText: document.getElementById('greeting-text'),
        userName: document.getElementById('user-name'),
        profileName: document.getElementById('profile-name'),
        profileEmail: document.getElementById('profile-email'),
        welcomeTitle: document.getElementById('welcome-title'),
        notificationBtn: document.getElementById('notification-btn'),
        logoutBtn: document.getElementById('logout-btn'),
        visitedCount: document.getElementById('visited-count'),
        favoritesCount: document.getElementById('favorites-count'),
        // Explore page elements
        filterToggle: document.getElementById('filter-toggle'),
        filtersPanel: document.getElementById('filters-panel'),
        filterOptions: document.querySelectorAll('.filter-option'),
        applyFilters: document.getElementById('apply-filters'),
        clearFilters: document.getElementById('clear-filters'),
        placeSearch: document.getElementById('place-search'),
        searchResults: document.getElementById('search-results'),
        locateMe: document.getElementById('locate-me'),
        zoomIn: document.getElementById('zoom-in'),
        zoomOut: document.getElementById('zoom-out'),
        actionCards: document.querySelectorAll('.action-card'),
        notifyBtn: document.querySelector('.notify-btn'),
        // Place modal
        placeModal: document.getElementById('place-modal'),
        placeModalContent: document.getElementById('place-modal-content')
    },
    modals: {
        notifications: document.getElementById('notifications-modal'),
        comingSoon: document.getElementById('coming-soon-modal')
    }
};

// Initialize App
function initApp() {
    loadUserData();
    setupEventListeners();
    
    // Simulate splash screen
    setTimeout(() => {
        elements.screens.splash.classList.remove('active');
        
        if (AppState.currentUser) {
            showAppScreen();
            updateUserUI();
        } else {
            showAuthScreen();
        }
    }, 2000);
}

// Load User Data from LocalStorage
function loadUserData() {
    const savedUser = localStorage.getItem('halaCardUser');
    if (savedUser) {
        AppState.currentUser = JSON.parse(savedUser);
    }
    
    const savedFavorites = localStorage.getItem('halaCardFavorites');
    if (savedFavorites) {
        AppState.favorites = new Set(JSON.parse(savedFavorites));
    }
    
    updateProfileStats();
}

// Save User Data
function saveUserData() {
    if (AppState.currentUser) {
        localStorage.setItem('halaCardUser', JSON.stringify(AppState.currentUser));
    }
    localStorage.setItem('halaCardFavorites', JSON.stringify([...AppState.favorites]));
}

// Update Profile Stats
function updateProfileStats() {
    if (elements.app.visitedCount) {
        elements.app.visitedCount.textContent = AppState.favorites.size;
    }
    if (elements.app.favoritesCount) {
        elements.app.favoritesCount.textContent = AppState.favorites.size;
    }
}

// Setup Event Listeners
function setupEventListeners() {
    // Auth navigation
    elements.auth.showSignup?.addEventListener('click', (e) => {
        e.preventDefault();
        elements.auth.loginForm.classList.remove('active');
        elements.auth.signupForm.classList.add('active');
    });
    
    elements.auth.showLogin?.addEventListener('click', (e) => {
        e.preventDefault();
        elements.auth.signupForm.classList.remove('active');
        elements.auth.loginForm.classList.add('active');
    });
    
    // Login
    elements.auth.loginBtn?.addEventListener('click', handleLogin);
    
    // Signup
    elements.auth.signupBtn?.addEventListener('click', handleSignup);
    
    // Navigation
    elements.app.navItems.forEach(item => {
        item.addEventListener('click', () => {
            const page = item.dataset.page;
            navigateTo(page);
        });
    });
    
    // Action Cards
    elements.app.actionCards?.forEach(card => {
        card.addEventListener('click', () => {
            const action = card.dataset.action;
            handleAction(action);
        });
    });
    
    // Notifications
    elements.app.notificationBtn?.addEventListener('click', showNotifications);
    
    // Logout
    elements.app.logoutBtn?.addEventListener('click', handleLogout);
    
    // Explore Page Events
    // Filter toggle
    elements.app.filterToggle?.addEventListener('click', (e) => {
        e.stopPropagation();
        elements.app.filtersPanel.classList.toggle('active');
    });
    
    // Close filters when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.map-header') && elements.app.filtersPanel?.classList.contains('active')) {
            elements.app.filtersPanel.classList.remove('active');
        }
    });
    
    // Filter options
    elements.app.filterOptions?.forEach(option => {
        option.addEventListener('click', () => {
            // For "all" filter, clear others
            if (option.dataset.filter === 'all') {
                elements.app.filterOptions.forEach(opt => opt.classList.remove('active'));
                option.classList.add('active');
                AppState.activeFilters.clear();
                AppState.activeFilters.add('all');
            } else {
                // Remove "all" if other filters selected
                const allFilter = document.querySelector('[data-filter="all"]');
                if (allFilter) allFilter.classList.remove('active');
                AppState.activeFilters.delete('all');
                
                // Toggle this filter
                option.classList.toggle('active');
                if (option.classList.contains('active')) {
                    AppState.activeFilters.add(option.dataset.filter);
                } else {
                    AppState.activeFilters.delete(option.dataset.filter);
                }
                
                // If no filters selected, select "all"
                if (AppState.activeFilters.size === 0) {
                    allFilter.classList.add('active');
                    AppState.activeFilters.add('all');
                }
            }
        });
    });
    
    // Apply filters (RESTORED)
    elements.app.applyFilters?.addEventListener('click', () => {
        applyFilters();
        elements.app.filtersPanel.classList.remove('active');
    });
    
    // Clear filters (RESTORED)
    elements.app.clearFilters?.addEventListener('click', () => {
        clearFilters();
        elements.app.filtersPanel.classList.remove('active');
    });
    
    // Search functionality (RESTORED)
    let searchTimeout;
    elements.app.placeSearch?.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(e.target.value);
        }, 300);
    });
    
    // Close search results when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.search-input') && elements.app.searchResults) {
            elements.app.searchResults.classList.remove('active');
        }
    });
    
    // Map controls
    elements.app.locateMe?.addEventListener('click', locateUser);
    elements.app.zoomIn?.addEventListener('click', zoomIn);
    elements.app.zoomOut?.addEventListener('click', zoomOut);
    
    // Notify button
    elements.app.notifyBtn?.addEventListener('click', () => {
        alert('We\'ll notify you when the card feature is ready!');
    });
    
    // Password toggle
    document.addEventListener('click', (e) => {
        if (e.target.closest('.toggle-password')) {
            const button = e.target.closest('.toggle-password');
            const input = button.parentElement.querySelector('input');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    });
    
    // Close modals
    document.addEventListener('click', (e) => {
        if (e.target.closest('.close-modal') || e.target.classList.contains('modal')) {
            closeModals();
        }
        
        if (e.target.closest('.modal-close')) {
            elements.modals.comingSoon.classList.remove('active');
        }
        
        // Close place modal
        if (e.target.closest('.close-place') || (e.target === elements.app.placeModal)) {
            closePlaceModal();
        }
    });
    
    // Prevent form submission
    document.addEventListener('submit', (e) => e.preventDefault());
}

// Handle Login
function handleLogin() {
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    if (!email || !password) {
        alert('Please enter email and password');
        return;
    }
    
    // Simulate API call
    setTimeout(() => {
        AppState.currentUser = {
            id: 'user_' + Date.now(),
            firstName: email.split('@')[0],
            lastName: 'User',
            email: email,
            fullName: email.split('@')[0].charAt(0).toUpperCase() + email.split('@')[0].slice(1) + ' User',
            memberSince: new Date().toISOString()
        };
        
        saveUserData();
        showAppScreen();
        updateUserUI();
        updateProfileStats();
    }, 800);
}

// Handle Signup
function handleSignup() {
    const firstName = document.getElementById('first-name').value;
    const lastName = document.getElementById('last-name').value;
    const email = document.getElementById('signup-email').value;
    const password = document.getElementById('signup-password').value;
    
    if (!firstName || !lastName || !email || !password) {
        alert('Please fill in all fields');
        return;
    }
    
    if (password.length < 6) {
        alert('Password must be at least 6 characters');
        return;
    }
    
    // Simulate API call
    setTimeout(() => {
        AppState.currentUser = {
            id: 'user_' + Date.now(),
            firstName: firstName,
            lastName: lastName,
            email: email,
            fullName: `${firstName} ${lastName}`,
            memberSince: new Date().toISOString()
        };
        
        saveUserData();
        elements.auth.signupForm.classList.remove('active');
        elements.auth.loginForm.classList.add('active');
        document.getElementById('email').value = email;
        alert('Account created successfully! Please sign in.');
    }, 800);
}

// Show/Hide Screens
function showAuthScreen() {
    elements.screens.auth.classList.add('active');
}

function showAppScreen() {
    elements.screens.auth.classList.remove('active');
    elements.screens.app.classList.add('active');
    navigateTo('home');
}

// Update User UI
function updateUserUI() {
    if (!AppState.currentUser) return;
    
    const hour = new Date().getHours();
    let greeting = 'Welcome';
    
    if (hour < 12) greeting = 'Welcome';
    else if (hour < 18) greeting = 'Welcome';
    else greeting = 'Welcome';
    
    if (elements.app.greetingText) {
        elements.app.greetingText.textContent = greeting;
    }
    
    if (elements.app.userName) {
        elements.app.userName.textContent = AppState.currentUser.firstName;
    }
    
    if (elements.app.welcomeTitle) {
        elements.app.welcomeTitle.textContent = `${greeting}, ${AppState.currentUser.firstName}`;
    }
    
    if (elements.app.profileName) {
        elements.app.profileName.textContent = AppState.currentUser.fullName;
    }
    
    if (elements.app.profileEmail) {
        elements.app.profileEmail.textContent = AppState.currentUser.email;
    }
}

// Navigation
function navigateTo(page) {
    // Update active nav item
    elements.app.navItems.forEach(item => {
        item.classList.remove('active');
        if (item.dataset.page === page) {
            item.classList.add('active');
        }
    });
    
    // Hide all pages
    Object.values(elements.app.pages).forEach(pageEl => {
        pageEl?.classList.remove('active');
    });
    
    // Show selected page
    const targetPage = elements.app.pages[page];
    if (targetPage) {
        targetPage.classList.add('active');
        AppState.currentPage = page;
        
        // Initialize page-specific features
        switch (page) {
            case 'explore':
                initMap();
                break;
            case 'home':
                updateUserUI();
                break;
            case 'profile':
                updateProfileStats();
                break;
        }
    }
}

// Handle Actions
function handleAction(action) {
    switch (action) {
        case 'explore':
            navigateTo('explore');
            break;
        case 'favorites':
            showFavorites();
            break;
        case 'offers':
        case 'guide':
            showComingSoon();
            break;
    }
}

// Show Favorites
function showFavorites() {
    if (AppState.favorites.size === 0) {
        alert('You haven\'t saved any favorites yet. Explore places and click the heart icon!');
        return;
    }
    
    let message = 'Your Favorite Places:\n\n';
    AppState.places
        .filter(place => AppState.favorites.has(place.id))
        .forEach((place, index) => {
            message += `${index + 1}. ${place.name} (${place.category})\n`;
        });
    
    alert(message);
}

// Initialize Map
function initMap() {
    if (!elements.app.pages.explore) return;
    
    // Default to Madinah
    const defaultLocation = { lat: 24.4672, lng: 39.6111 };
    
    try {
        AppState.map = new google.maps.Map(document.getElementById('map'), {
            center: defaultLocation,
            zoom: 14,
            zoomControl: false,
            streetViewControl: false,
            mapTypeControl: false,
            fullscreenControl: false,
            styles: [
                {
                    "featureType": "poi",
                    "elementType": "labels",
                    "stylers": [{ "visibility": "off" }]
                },
                {
                    "featureType": "transit",
                    "elementType": "labels",
                    "stylers": [{ "visibility": "off" }]
                }
            ]
        });
        
        // Set places data
        AppState.places = samplePlaces;
        
        // Get user location
        getUserLocation();
        
        // Add places to map
        addPlacesToMap(AppState.places);
        
    } catch (error) {
        console.error('Error initializing map:', error);
    }
}

// Get User Location - ADD DISTANCE CALCULATION
function getUserLocation() {
    if (!navigator.geolocation) return;
    
    navigator.geolocation.getCurrentPosition(
        (position) => {
            AppState.userLocation = {
                lat: position.coords.latitude,
                lng: position.coords.longitude
            };
            
            // Update location info
            const locationInfo = document.getElementById('location-info');
            if (locationInfo) {
                const accuracy = Math.round(position.coords.accuracy);
                locationInfo.innerHTML = `
                    <i class="fas fa-location-dot"></i>
                    <span>Accurate within ${accuracy}m</span>
                `;
                
                // Hide after 5 seconds
                setTimeout(() => {
                    locationInfo.style.opacity = '0';
                    setTimeout(() => {
                        locationInfo.style.display = 'none';
                    }, 300);
                }, 5000);
            }
            
            // 🔥 ADD THIS: Update distances for all places
            updatePlaceDistances();
            
            // 🔥 ADD THIS: Re-add places to map with updated distances
            addPlacesToMap(AppState.places);
            
        },
        (error) => {
            console.log('Geolocation error:', error);
            const locationInfo = document.getElementById('location-info');
            if (locationInfo) {
                locationInfo.innerHTML = `
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>Location not available</span>
                `;
            }
        }
    );
}

// Add Places to Map
function addPlacesToMap(places) {
    // Clear existing markers
    AppState.markers.forEach(marker => marker.setMap(null));
    AppState.markers = [];
    
    places.forEach(place => {
        const marker = new google.maps.Marker({
            position: { lat: place.lat, lng: place.lng },
            map: AppState.map,
            title: place.name,
            icon: getMarkerIcon(place.type)
        });
        
        AppState.markers.push(marker);
        
        // Add click listener for place details (RESTORED)
        marker.addListener('click', () => {
            showPlaceDetails(place);
        });
    });
}

// Get Marker Icon
function getMarkerIcon(type) {
    const colors = {
        mosque: '#1e6b45',
        restaurant: '#FF8E53',
        cafe: '#A0522D',
        experience: '#9C27B0'
    };
    
    const color = colors[type] || '#6c757d';
    
    return {
        url: `data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 36 36">
            <circle cx="18" cy="18" r="16" fill="${encodeURIComponent(color)}" stroke="white" stroke-width="2"/>
            <text x="18" y="24" font-family="Arial" font-size="14" fill="white" text-anchor="middle" font-weight="bold">📍</text>
        </svg>`,
        scaledSize: new google.maps.Size(36, 36)
    };
}

// Calculate distance between two coordinates in km
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth's radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    const distance = R * c;
    return distance;
}

// Format distance display
function formatDistance(distance) {
    if (distance < 1) {
        return `${Math.round(distance * 1000)}m`;
    } else {
        return `${distance.toFixed(1)}km`;
    }
}

// Update all places with distances from user location
function updatePlaceDistances() {
    if (!AppState.userLocation) return;
    
    AppState.places = AppState.places.map(place => {
        const distance = calculateDistance(
            AppState.userLocation.lat,
            AppState.userLocation.lng,
            place.lat,
            place.lng
        );
        
        return {
            ...place,
            distance: formatDistance(distance),
            rawDistance: distance // Keep for sorting/filtering
        };
    });
}

// Show Place Details - USE CALCULATED DISTANCE
function showPlaceDetails(place) {
    const isFavorite = AppState.favorites.has(place.id);
    
    const modalHTML = `
        <div class="place-header">
            <h3>Place Details</h3>
            <button class="close-place">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="place-image">
            ${place.emoji}
        </div>
        
        <div class="place-details">
            <div class="place-title">
                <h2>${place.name}</h2>
                <span class="place-rating">${place.rating} ★</span>
            </div>
            
            <div class="place-category">${place.category}</div>
            
            <p class="place-description">${place.description}</p>
            
            <div class="place-info-grid">
                <div class="place-info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>${place.address}</span>
                    <span class="distance-badge">${place.distance || 'Calculating...'}</span>
                </div>
                
                <div class="place-info-item">
                    <i class="fas fa-clock"></i>
                    <span>${place.openingHours}</span>
                </div>
                
                <div class="place-info-item">
                    <i class="fas ${isFavorite ? 'fa-heart' : 'fa-heart'}"></i>
                    <span>${isFavorite ? 'Saved to Favorites' : 'Add to Favorites'}</span>
                </div>
                
                <div class="place-info-item">
                    <i class="fas fa-tag"></i>
                    <span>${place.type.charAt(0).toUpperCase() + place.type.slice(1)}</span>
                </div>
            </div>
        </div>
        
        <div class="place-actions">
            <button class="place-action-btn" onclick="getDirections(${place.lat}, ${place.lng})">
                <i class="fas fa-directions"></i> Directions
            </button>
            <button class="place-action-btn save" onclick="toggleFavorite(${place.id})">
                <i class="fas ${isFavorite ? 'fa-heart' : 'fa-bookmark'}"></i> ${isFavorite ? 'Saved' : 'Save'}
            </button>
        </div>
    `;
    
    if (elements.app.placeModalContent) {
        elements.app.placeModalContent.innerHTML = modalHTML;
    }
    
    if (elements.app.placeModal) {
        elements.app.placeModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

// Close Place Modal
function closePlaceModal() {
    if (elements.app.placeModal) {
        elements.app.placeModal.classList.remove('active');
        document.body.style.overflow = 'auto';
    }
}

// Get Directions (RESTORED)
function getDirections(lat, lng) {
    if (!AppState.userLocation) {
        alert('Please enable location services to get directions.');
        return;
    }
    
    const url = `https://www.google.com/maps/dir/?api=1&origin=${AppState.userLocation.lat},${AppState.userLocation.lng}&destination=${lat},${lng}&travelmode=driving`;
    window.open(url, '_blank');
    closePlaceModal();
}

// Toggle Favorite (RESTORED)
function toggleFavorite(placeId) {
    if (AppState.favorites.has(placeId)) {
        AppState.favorites.delete(placeId);
    } else {
        AppState.favorites.add(placeId);
    }
    
    saveUserData();
    updateProfileStats();
    
    // Reopen modal with updated info
    const place = AppState.places.find(p => p.id === placeId);
    if (place) {
        showPlaceDetails(place);
    }
}

// Apply Filters (RESTORED)
function applyFilters() {
    if (AppState.activeFilters.has('all')) {
        addPlacesToMap(AppState.places);
        return;
    }
    
    const filteredPlaces = AppState.places.filter(place => 
        AppState.activeFilters.has(place.type)
    );
    
    addPlacesToMap(filteredPlaces);
    
    // Fit map to filtered bounds
    if (filteredPlaces.length > 0 && AppState.map) {
        const bounds = new google.maps.LatLngBounds();
        filteredPlaces.forEach(place => {
            bounds.extend({ lat: place.lat, lng: place.lng });
        });
        AppState.map.fitBounds(bounds);
    }
}

// Clear Filters (RESTORED)
function clearFilters() {
    AppState.activeFilters.clear();
    AppState.activeFilters.add('all');
    
    // Update UI
    elements.app.filterOptions.forEach(option => {
        option.classList.remove('active');
        if (option.dataset.filter === 'all') {
            option.classList.add('active');
        }
    });
    
    // Show all places
    addPlacesToMap(AppState.places);
}

// Perform Search - UPDATE TO USE CALCULATED DISTANCES
function performSearch(query) {
    if (!query || query.trim().length < 2) {
        if (elements.app.searchResults) {
            elements.app.searchResults.classList.remove('active');
        }
        addPlacesToMap(AppState.places);
        return;
    }
    
    const searchQuery = query.trim().toLowerCase();
    AppState.searchResults = AppState.places.filter(place => {
        const searchText = `${place.name} ${place.description} ${place.category} ${place.type}`.toLowerCase();
        return searchText.includes(searchQuery);
    });
    
    // Update search results dropdown - USE CALCULATED DISTANCE
    if (elements.app.searchResults) {
        if (AppState.searchResults.length > 0) {
            elements.app.searchResults.innerHTML = AppState.searchResults
                .map(place => `
                    <div class="search-result-item" data-place-id="${place.id}">
                        <i class="${place.icon}"></i>
                        <div>
                            <h4>${place.name}</h4>
                            <p>${place.category} • ${place.distance || 'Calculating...'}</p>
                        </div>
                    </div>
                `)
                .join('');
            
            elements.app.searchResults.classList.add('active');
            
            // Add click listeners to search results
            elements.app.searchResults.querySelectorAll('.search-result-item').forEach(item => {
                item.addEventListener('click', () => {
                    const placeId = parseInt(item.dataset.placeId);
                    const place = AppState.places.find(p => p.id === placeId);
                    if (place) {
                        showPlaceDetails(place);
                        elements.app.searchResults.classList.remove('active');
                        elements.app.placeSearch.value = '';
                    }
                });
            });
        } else {
            elements.app.searchResults.classList.remove('active');
        }
    }
    
    // Filter map markers
    addPlacesToMap(AppState.searchResults);
    
    // Fit map to search results
    if (AppState.searchResults.length > 0 && AppState.map) {
        const bounds = new google.maps.LatLngBounds();
        AppState.searchResults.forEach(place => {
            bounds.extend({ lat: place.lat, lng: place.lng });
        });
        AppState.map.fitBounds(bounds);
    }
}
// Map Controls
function locateUser() {
    if (AppState.userLocation && AppState.map) {
        AppState.map.setCenter(AppState.userLocation);
        AppState.map.setZoom(15);
    }
}

function zoomIn() {
    if (AppState.map) {
        AppState.map.setZoom(AppState.map.getZoom() + 1);
    }
}

function zoomOut() {
    if (AppState.map) {
        AppState.map.setZoom(AppState.map.getZoom() - 1);
    }
}

// Notifications
function showNotifications() {
    elements.modals.notifications.classList.add('active');
}

// Coming Soon
function showComingSoon() {
    elements.modals.comingSoon.classList.add('active');
}

function closeModals() {
    Object.values(elements.modals).forEach(modal => {
        modal?.classList.remove('active');
    });
}

// Logout
function handleLogout() {
    if (confirm('Are you sure you want to log out?')) {
        AppState.currentUser = null;
        localStorage.removeItem('halaCardUser');
        
        elements.screens.app.classList.remove('active');
        elements.screens.auth.classList.add('active');
        elements.auth.loginForm.classList.add('active');
        elements.auth.signupForm.classList.remove('active');
        
        // Clear forms
        document.getElementById('email').value = '';
        document.getElementById('password').value = '';
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initApp);

// Make functions available globally
window.getDirections = getDirections;
window.toggleFavorite = toggleFavorite;
window.closePlaceModal = closePlaceModal;

// PWA Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('ServiceWorker registered');
            })
            .catch(error => {
                console.log('ServiceWorker registration failed:', error);
            });
    });
}

// Install Prompt
let deferredPrompt;
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Show install prompt after 5 seconds
    setTimeout(() => {
        if (deferredPrompt && confirm('Install Hala Card for better experience?')) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('User installed the app');
                }
                deferredPrompt = null;
            });
        }
    }, 5000);
});