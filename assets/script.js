// Mock User Data
const state = {
    isAuthenticated: false,
    user: null,
    walletBalance: 1450.00, // Example starting balance from sales
    lastSaleDate: new Date('2023-10-01'), // Set this date to test suspension logic
    storeStatus: 'Active'
};

// DOM Elements
const authContainer = document.getElementById('auth-container');
const dashboardContainer = document.getElementById('dashboard-container');
const walletDisplay = document.getElementById('wallet-balance');
const statusIndicator = document.getElementById('store-status-indicator');
const daysSinceDisplay = document.getElementById('days-since-sale');
const suspensionWarning = document.getElementById('suspension-warning');
const withdrawForm = document.getElementById('withdraw-form');
const withdrawInput = document.getElementById('withdraw-amount');
const withdrawMessage = document.getElementById('withdraw-message');

// --- Authentication Logic ---
function handleLogin(provider) {
    // Simulate Auth Provider Login
    console.log(`Logging in with ${provider}...`);
    
    state.isAuthenticated = true;
    state.user = { name: "VibeMerchant", provider: provider };
    
    updateUI();
}

function handleLogout() {
    state.isAuthenticated = false;
    state.user = null;
    updateUI();
}

// --- Business Logic ---

function checkSuspension() {
    const now = new Date();
    const diffTime = Math.abs(now - state.lastSaleDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 

    daysSinceDisplay.innerText = `Last sale: ${diffDays} days ago`;

    // Logic: Suspend if > 30 days
    if (diffDays > 30) {
        state.storeStatus = 'Suspended';
        statusIndicator.innerText = 'Suspended';
        statusIndicator.classList.remove('active');
        statusIndicator.classList.add('suspended');
        suspensionWarning.classList.remove('hidden');
    } else {
        state.storeStatus = 'Active';
        statusIndicator.innerText = 'Active';
        statusIndicator.classList.add('active');
        statusIndicator.classList.remove('suspended');
        suspensionWarning.classList.add('hidden');
    }
}

function handleWithdraw(e) {
    e.preventDefault();
    const amount = parseFloat(withdrawInput.value);

    if (isNaN(amount) || amount <= 0) {
        alert("Please enter a valid amount.");
        return;
    }

    if (amount > state.walletBalance) {
        alert("Insufficient funds. You cannot withdraw more than your wallet balance.");
        return;
    }

    // Process Withdrawal
    state.walletBalance -= amount;
    withdrawInput.value = '';
    alert(`Success! $${amount.toFixed(2)} has been requested for withdrawal.`);
    
    renderDashboard();
}

// --- UI Rendering ---
function updateUI() {
    if (state.isAuthenticated) {
        authContainer.classList.add('hidden');
        dashboardContainer.classList.remove('hidden');
        checkSuspension();
        renderDashboard();
    } else {
        authContainer.classList.remove('hidden');
        dashboardContainer.classList.add('hidden');
    }
}

function renderDashboard() {
    walletDisplay.innerText = state.walletBalance.toFixed(2);
}

// Event Listeners
withdrawForm.addEventListener('submit', handleWithdraw);

// Initial Check
updateUI();