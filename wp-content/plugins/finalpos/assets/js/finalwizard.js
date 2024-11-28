// At the beginning of the file, ensure this function is defined
function nextStep(step) {
    const allSteps = document.querySelectorAll('.wizard-step');
    const wizardContainer = document.getElementById('final-pos-wizard');
    
    allSteps.forEach(el => el.classList.remove('active'));
    
    const targetStep = document.getElementById(`step${step}`);
    if (targetStep) {
        targetStep.classList.add('active');
        wizardContainer.classList.toggle('step2-active', step === 2);

        // Update URL without reloading the page
        const currentUrlParams = new URLSearchParams(window.location.search);
        currentUrlParams.set('step', step);
        currentUrlParams.set('_wpnonce', finalWizardData.nonce); // Füge Nonce hinzu
        history.pushState({step: step}, '', `${window.location.pathname}?${currentUrlParams}`);
    } else {
        console.error('Target step not found:', step);
    }
}

function prevStep(step) {
    nextStep(step);
}

function showUI(choice) {
    const uiPreview = document.getElementById('ui-preview');
    const pluginUrl = getPluginUrl();
    const imageUrl = choice === 'modern' ? 'wizard-step3-modern.jpg' : 'wizard-step3-legacy.jpg';
    uiPreview.style.backgroundImage = `url("${pluginUrl}assets/img/${imageUrl}")`;
}

function getPluginUrl() {
    const script = Array.from(document.getElementsByTagName('script')).find(script => script.src.includes('finalwizard.js'));
    return script ? script.src.replace(/assets\/js\/finalwizard\.js.*$/, '') : '/wp-content/plugins/final/'; // Fallback
}

// Global functions
let tokenProcessed = false;

// Neue Funktion zum Speichern des Access Tokens
function saveAccessToken(token, companyId) {
    console.log('Saving access token and company ID');
    
    return fetch(finalWizardData.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'save_access_token',
            'token': token,
            'company_id': companyId,
            'nonce': finalWizardData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Access token and company ID saved successfully');
            return data.data;
        } else {
            throw new Error('Failed to save access token and company ID: ' + data.data);
        }
    });
}

// Modifizierte processToken Funktion
function processToken(tokenData) {
    if (tokenProcessed) return;
    tokenProcessed = true;
    
    console.log('Processing token:', JSON.stringify(tokenData, null, 2));
    
    const token = tokenData.token.token || tokenData.token;
    const payload = tokenData.token.payload || tokenData.payload;

    console.log('Extracted token:', token);
    console.log('Extracted payload:', payload);

    if (!token || !payload) {
        console.error('Token or Payload is missing:', { token, payload });
        return;
    }

    // Extract the Company ID
    let companyId = null;
    if (payload.companies && payload.companies.length > 0) {
        companyId = payload.companies[0].$oid;
        if (typeof companyId !== 'string' || companyId.length !== 24) {
            console.error('Invalid Company ID format:', companyId);
            return;
        }
    } else {
        console.error('Company ID not found in token payload.');
        return;
    }

    // Save the Access Token and Company ID via AJAX
    saveAccessToken(token, companyId)
        .then(() => {
            console.log('Access token and company ID saved.');
            nextStep(3);
        })
        .catch(error => {
            console.error('Error processing the token:', error);
        });
}

function parseJwt(token) {
    try {
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(c => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2)).join(''));

        return JSON.parse(jsonPayload);
    } catch (error) {
        console.error('Invalid JWT format:', error);
        return null;
    }
}

// Event listener for messages from the iframe
window.addEventListener('message', event => {
    const allowedOrigins = [
        'https://hub.finalpos.com',
        'https://js.stripe.com',
        finalWizardData.storeUrl
    ];
    if (!allowedOrigins.includes(event.origin)) {
        return;
    }

    console.log('Message received:', event.data);

    if (event.data?.type === 'onboarding_complete' && event.data.token) {
        processToken(event.data);
    } else {
        console.warn('Unexpected message format:', event.data);
    }
});

function sendMessageToIframe(message) {
    const iframe = document.getElementById('final-pos-iframe');
    iframe?.contentWindow?.postMessage(message, "https://hub.finalpos.com");
}

function closeWizard() {
    window.location.href = '/wp-admin/';
}

// Global Logging Function
function logWizardData() {
    if (typeof finalWizardData !== 'undefined') {
        console.log('finalWizardData:', finalWizardData);
    } else {
        console.error('finalWizardData is not defined');
    }
}

// Flag to check if the code has already been executed
let initialized = false;

document.addEventListener('DOMContentLoaded', () => {
    if (initialized) return;
    initialized = true;

    // Log the data once
    logWizardData();
    
    const iframe = document.getElementById('final-pos-iframe');
    
    if (iframe) {
        if (!iframe.src) {
            iframe.src = finalWizardData.iframeSrc;
        }

        iframe.addEventListener('load', () => {
            // iFrame fully loaded
        });
    }

    // Add store_url to all AJAX requests
    const originalFetch = window.fetch;
    window.fetch = function(url, options) {
        if (typeof url === 'string' && url.includes(finalWizardData.ajaxurl)) {
            const modifiedUrl = new URL(url, window.location.origin);
            modifiedUrl.searchParams.set('store_url', finalWizardData.storeUrl);
            url = modifiedUrl.toString();
        }
        return originalFetch.call(this, url, options);
    };

    const radioButtons = document.querySelectorAll('input[name="ui-choice"]');
    radioButtons.forEach(radio => {
        radio.addEventListener('change', () => showUI(radio.value));
    });

    // Set initial UI preview
    showUI('modern');

    // Configure link click handlers
    const configureLinks = document.querySelectorAll('.configure-link');
    configureLinks.forEach(link => {
        link.addEventListener('click', e => {
            e.preventDefault();
            const popupId = `${link.getAttribute('data-popup')}Popup`;
            const popup = document.getElementById(popupId);
            popup.style.display = 'block';
        });
    });

    // Close popup handlers
    const closeButtons = document.querySelectorAll('.close-popup');
    closeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const popup = button.closest('.setup-popup');
            popup.style.display = 'none';
        });
    });

    // Close popup when clicking outside
    window.addEventListener('click', event => {
        if (event.target.classList.contains('setup-popup')) {
            event.target.style.display = 'none';
        }
    });

    // Category search functionality
    const categorySearch = document.getElementById('categorySearch');
    const categoryItems = document.querySelectorAll('.category-item');

    if (categorySearch) {
        categorySearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            categoryItems.forEach(item => {
                const categoryName = item.querySelector('.category-name').textContent.toLowerCase();
                item.style.display = categoryName.includes(searchTerm) ? '' : 'none';
            });
        });
    }

    // "All categories" checkbox functionality
    const allCategoriesCheckbox = document.getElementById('allCategories');
    const categoryCheckboxes = document.querySelectorAll('.category-item input[type="checkbox"]:not(#allCategories)');

    if (allCategoriesCheckbox) {
        allCategoriesCheckbox.addEventListener('change', () => {
            categoryCheckboxes.forEach(checkbox => {
                checkbox.checked = allCategoriesCheckbox.checked;
            });
        });
    }

    categoryCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            allCategoriesCheckbox.checked = Array.from(categoryCheckboxes).every(cb => cb.checked);
        });
    });

    // Save categories button functionality
    const saveButton = document.querySelector('.save-categories');
    if (saveButton) {
        saveButton.addEventListener('click', () => {
            saveAllWizardSettings().then(() => {
                document.getElementById('productsPopup').style.display = 'none';
            });
        });
    }

    // Save timeframe button functionality
    const saveTimeframeButton = document.querySelector('.save-timeframe');
    if (saveTimeframeButton) {
        saveTimeframeButton.addEventListener('click', () => {
            saveAllWizardSettings().then(() => {
                document.getElementById('ordersPopup').style.display = 'none';
            });
        });
    }

    // Save UI choice
    const uiChoiceRadios = document.querySelectorAll('input[name="ui-choice"]');
    uiChoiceRadios.forEach(radio => {
        radio.addEventListener('change', saveAllWizardSettings);
    });

    // Save sync toggles
    const syncToggles = document.querySelectorAll('#products, #orders, #customers');
    syncToggles.forEach(toggle => {
        toggle.addEventListener('change', saveAllWizardSettings);
    });

    // Highlight the selected timeframe
    const timeframeItems = document.querySelectorAll('.timeframe-item input[type="radio"]');
    timeframeItems.forEach(radio => {
        radio.addEventListener('change', () => {
            timeframeItems.forEach(item => item.parentElement.classList.remove('selected'));
            radio.parentElement.classList.add('selected');
        });
    });

    // Event listener for the Sign Up link
    const signUpLink = document.getElementById('signUpLink');
    if (signUpLink) {
        signUpLink.addEventListener('click', e => {
            e.preventDefault();
            loadIframe(finalWizardData.registrationUrl);
        });
    }

    // Event listener for the Sign In button
    const signInBtn = document.querySelector('.signin-btn');
    if (signInBtn) {
        signInBtn.addEventListener('click', e => {
            e.preventDefault();
            loadIframe(finalWizardData.loginUrl);
        });
    }

    // Modify the event listener for the "Authorize WooCommerce" button
    const authorizeWcButton = document.querySelector('.next-btn[onclick="redirectToWcAuth()"]');
    if (authorizeWcButton) {
        authorizeWcButton.removeAttribute('onclick');
        authorizeWcButton.addEventListener('click', (e) => {
            e.preventDefault();
            redirectToWcAuth();
        });
    }

    const closeButton = document.querySelector('.close-icon');
    if (closeButton) {
        closeButton.addEventListener('click', (e) => {
            e.preventDefault();
            closeWizard();
        });
    }
});

// Function to calculate the start timestamp
function calculateStartTimestamp(days) {
    return new Date(Date.now() - (days * 24 * 60 * 60 * 1000)).toISOString();
}

// Get all WooCommerce categories
function getAllWooCategories() {
    const categoryCheckboxes = document.querySelectorAll('.category-item input[type="checkbox"]:not(#allCategories)');
    return Array.from(categoryCheckboxes).map(checkbox => checkbox.value);
}

// New function to save all wizard settings at once
function saveAllWizardSettings() {
    // Get selected categories or all categories if none selected
    const selectedCategories = Array.from(document.querySelectorAll('.category-item input[type="checkbox"]:checked:not(#allCategories)'))
        .map(checkbox => checkbox.value);
    
    // If no categories are selected or "All categories" is checked, get all categories
    const allCategoriesChecked = document.getElementById('allCategories')?.checked;
    const categorySync = (selectedCategories.length === 0 || allCategoriesChecked) ? 
        getAllWooCategories() : 
        selectedCategories;

    // Get selected timeframe or use default (60 days)
    const selectedTimeframe = document.querySelector('input[name="order_timeframe"]:checked')?.value || '60';
    
    const settings = {
        category_sync: categorySync,
        order_timeframe: selectedTimeframe,
        order_timeframe_start: calculateStartTimestamp(parseInt(selectedTimeframe)),
        ui_choice: document.querySelector('input[name="ui-choice"]:checked')?.value || 'modern',
        sync_status: {
            products: document.getElementById('products')?.checked ?? true,
            orders: document.getElementById('orders')?.checked ?? true,
            customers: document.getElementById('customers')?.checked ?? true
        }
    };

    console.log('Saving all wizard settings:', settings);

    return fetch(finalWizardData.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'save_all_wizard_settings',
            'settings': JSON.stringify(settings),
            'nonce': finalWizardData.nonce
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('All settings saved successfully:', data.data);
        } else {
            throw new Error('Failed to save settings: ' + data.data);
        }
    })
    .catch(error => {
        console.error('Error saving settings:', error);
    });
}

// Modify the redirectToWcAuth function
function redirectToWcAuth() {
    const params = new URLSearchParams({
        'app_name': 'FinalPOS',
        'scope': 'read_write',
        'user_id': finalWizardData.userId,
        'return_url': `${finalWizardData.returnUrl}&_wpnonce=${finalWizardData.nonce}`, // Füge Nonce hinzu
        'callback_url': finalWizardData.callbackUrl
    });

    // Save wizard status before redirecting
    saveWizardStatus('done')
        .then(() => {
            const wcAuthUrl = `${finalWizardData.storeUrl}wc-auth/v1/authorize?${params.toString()}`;
            window.location.href = wcAuthUrl;
        })
        .catch(error => {
            console.error('Failed to save wizard status:', error);
        });
}

// Add a new function to save the wizard status
function saveWizardStatus(status) {
    console.log('Saving wizard status:', status); // Debug log
    console.log('AJAX URL:', finalWizardData.ajaxurl); // Debug log
    console.log('Nonce:', finalWizardData.nonce); // Debug log

    return fetch(finalWizardData.ajaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            'action': 'save_wizard_status',
            'status': status,
            'nonce': finalWizardData.nonce
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Server response:', data); // Debug log
        if (data.success) {
            console.log('Wizard status saved successfully');
        } else {
            throw new Error(data.data || 'Failed to save wizard status');
        }
    })
    .catch(error => {
        console.error('Error saving wizard status:', error);
        throw error;
    });
}

// Function to load the iFrame and move to the next step
function loadIframe(url) {
    const iframe = document.getElementById('final-pos-iframe');
    if (iframe) {
        iframe.src = url;
        nextStep(2);
    } else {
        console.error('iFrame not found.');
    }
}

// At the beginning of the file, add this function
function resetWizard() {
    nextStep(1);
    history.replaceState(null, '', window.location.pathname);
}

// Modify the nextStep function
function nextStep(step) {
    const allSteps = document.querySelectorAll('.wizard-step');
    const wizardContainer = document.getElementById('final-pos-wizard');
    
    allSteps.forEach(el => el.classList.remove('active'));
    
    const targetStep = document.getElementById(`step${step}`);
    if (targetStep) {
        targetStep.classList.add('active');
        wizardContainer.classList.toggle('step2-active', step === 2);

        // Update URL without reloading the page
        const currentUrlParams = new URLSearchParams(window.location.search);
        currentUrlParams.set('step', step);
        currentUrlParams.set('_wpnonce', finalWizardData.nonce); // Füge Nonce hinzu
        history.pushState({step: step}, '', `${window.location.pathname}?${currentUrlParams}`);
    } else {
        console.error('Target step not found:', step);
    }
}

// Add this event listener at the end of the DOMContentLoaded event
window.addEventListener('popstate', function(event) {
    if (event.state && event.state.step) {
        nextStep(event.state.step);
    } else {
        resetWizard();
    }
});









































