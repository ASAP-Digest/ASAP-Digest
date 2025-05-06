<script>
	import { onMount } from 'svelte';
	import { Download, X } from '@lucide/svelte';
	
	/**
	 * Define the BeforeInstallPromptEvent interface missing from standard TS defs
	 * @typedef {Object} BeforeInstallPromptEvent
	 * @extends {Event}
	 * @property {() => Promise<void>} prompt
	 * @property {Promise<{outcome: 'accepted'|'dismissed'}>} userChoice
	 */
	
	// State variables
	/** @type {boolean} */
	let hasShownPrompt = $state(false);
	
	/** @type {boolean} */
	let isAppInstalled = $state(false);
	
	/** @type {boolean} */
	let isPromptVisible = $state(false);
	
	/** @type {boolean} */
	let isApple = $state(false);
	
	/** @type {boolean} */
	let isChrome = $state(false);
	
	/** @type {boolean} */
	let isSafari = $state(false);
	
	/** @type {boolean} */
	let isFirefox = $state(false);
	
	/** @type {'ios'|'android'|'desktop'|null} */
	let deviceOS = $state(null);
	
	// Reference to the beforeinstallprompt event
	/** @type {any} */
	let deferredPrompt = $state(null);

	// Determine if we're in test mode
	/** @type {boolean} */
	let isTestMode = $state(false);
	
	/**
	 * Check if app is already installed
	 * @returns {boolean} - Whether app is installed
	 */
	function checkIfAppInstalled() {
		if (typeof window === 'undefined') return false;
		
		// Check display-mode
		const isStandalone = window.matchMedia('(display-mode: standalone)').matches;
		
		// Check for iOS specific properties
		// @ts-ignore - Navigator.standalone is a non-standard Safari property
		const isIOSInstalled = 
			(navigator.standalone === true) || 
			window.matchMedia('(display-mode: standalone)').matches;
		
		// Check localStorage to prevent repeated prompts
		const appInstalled = localStorage.getItem('appInstalled') === 'true';
		
		return isStandalone || isIOSInstalled || appInstalled;
	}
	
	/**
	 * Detect browser and OS
	 */
	function detectBrowserAndOS() {
		if (typeof window === 'undefined') return;
		
		const ua = navigator.userAgent;
		
		// Detect browsers
		isChrome = /Chrome/.test(ua) && !/Edge|Edg/.test(ua);
		isSafari = /Safari/.test(ua) && !/Chrome/.test(ua);
		isFirefox = /Firefox/.test(ua);
		
		// Detect OS
		if (/iPad|iPhone|iPod/.test(ua)) {
			deviceOS = 'ios';
		} else if (/Android/.test(ua)) {
			deviceOS = 'android';
		} else {
			deviceOS = 'desktop';
		}
	}
	
	/**
	 * Handle install button click
	 * @param {MouseEvent} event - Mouse event object 
	 */
	function handleInstall(event) {
		console.log('[InstallPrompt] Install button clicked');
		
		// Hide prompt
		isPromptVisible = false;
		
		// Mark as shown in session and localStorage
		hasShownPrompt = true;
		localStorage.setItem('installPromptShown', 'true');
		
		// Use native install prompt if available
		if (deferredPrompt) {
			// @ts-ignore - BeforeInstallPromptEvent is a non-standard API
			deferredPrompt.prompt();
			
			// @ts-ignore - BeforeInstallPromptEvent is a non-standard API
			deferredPrompt.userChoice.then(/** @param {any} choiceResult */ (choiceResult) => {
				if (choiceResult.outcome === 'accepted') {
					console.log('[InstallPrompt] User accepted the install prompt');
					localStorage.setItem('appInstalled', 'true');
					isAppInstalled = true;
				} else {
					console.log('[InstallPrompt] User dismissed the install prompt');
				}
				
				// Clear the saved prompt
				deferredPrompt = null;
			});
		}
	}
	
	/**
	 * Close the install prompt
	 * @param {MouseEvent} event - Mouse event object
	 */
	function closePrompt(event) {
		isPromptVisible = false;
		hasShownPrompt = true;
		localStorage.setItem('installPromptShown', 'true');
	}

	/**
	 * Reset prompt settings (for testing)
	 */
	function resetPromptSettings() {
		localStorage.removeItem('installPromptShown');
		localStorage.removeItem('appInstalled');
		hasShownPrompt = false;
		isAppInstalled = false;
		if (isTestMode) {
			// In test mode, we show the prompt immediately
			setTimeout(() => {
				isPromptVisible = true;
			}, 500);
		}
	}

	/**
	 * Custom event handler for communication with test controls
	 * @param {CustomEvent} event - Custom event with test control data
	 */
	function handlePwaTest(event) {
		if (event.detail?.type === 'reset-install-prompt') {
			resetPromptSettings();
		} else if (event.detail?.type === 'force-show-prompt') {
			isPromptVisible = true;
		} else if (event.detail?.type === 'force-hide-prompt') {
			isPromptVisible = false;
		}
	}
	
	// Initialize on mount
	onMount(() => {
		try {
			// Check if we're in test mode
			isTestMode = window.location.search.includes('pwa-test');

			// Detect browser and OS
			detectBrowserAndOS();
			
			// Check if app is installed
			isAppInstalled = checkIfAppInstalled();
			
			// Check if prompt has been shown this session or in localStorage
			// In test mode, we ignore the localStorage setting
			const promptShown = isTestMode ? false : localStorage.getItem('installPromptShown') === 'true';
			hasShownPrompt = promptShown;
			
			// Add beforeinstallprompt listener
			window.addEventListener('beforeinstallprompt', (e) => {
				try {
					// Prevent Chrome 67+ from automatically showing the prompt
					e.preventDefault();
					
					// Stash the event so it can be triggered later
					deferredPrompt = e;
					
					// Dispatch event for test controls
					window.dispatchEvent(new CustomEvent('pwa-install-prompt-available', {
						detail: { event: e }
					}));
					
					// Update UI to show install button/banner
					if ((!hasShownPrompt && !isAppInstalled) || isTestMode) {
						// Show the prompt after a short delay
						// In test mode, wait for explicit action
						if (!isTestMode) {
							setTimeout(() => {
								isPromptVisible = true;
							}, 3000);
						}
					}
				} catch (error) {
					console.error('[InstallPrompt] Error handling install prompt:', error);
				}
			});
			
			// Track when the app is installed
			window.addEventListener('appinstalled', () => {
				try {
					// Log app installed
					console.log('[InstallPrompt] PWA was installed');
					
					// Update state
					isAppInstalled = true;
					isPromptVisible = false;
					
					// Save to localStorage
					localStorage.setItem('appInstalled', 'true');

					// Dispatch event for test controls
					window.dispatchEvent(new CustomEvent('pwa-app-installed'));
				} catch (error) {
					console.error('[InstallPrompt] Error handling app installed event:', error);
				}
			});
			
			// Listen for test control events
			window.addEventListener('pwa-test-control', handlePwaTest);
			
			// Check if we need to show the prompt (not installed and not shown yet)
			if ((!isAppInstalled && !hasShownPrompt) || isTestMode) {
				// In test mode, wait for explicit action
				if (!isTestMode) {
					// Show prompt after a delay
					setTimeout(() => {
						isPromptVisible = true;
					}, 3000);
				}
			}

			// Clean up event listeners on unmount
			return () => {
				window.removeEventListener('pwa-test-control', handlePwaTest);
			};
		} catch (error) {
			console.error('[InstallPrompt] Error initializing component:', error);
		}
	});
</script>

{#if isPromptVisible}
	<div 
		class="install-widget p-2.5 fixed bottom-6 left-4 right-4 md:left-auto md:w-96 md:right-6 bg-white dark:bg-[hsl(var(--card))] rounded-lg shadow-lg border border-[hsl(var(--border))]"
		transition:slide={{ duration: 300, y: 20 }}
	>
		<div class="flex justify-between items-start mb-3">
			<h3 class="text-[var(--font-size-base)] font-semibold flex items-center gap-2">
				<Download size={18} />
				Install ASAP Digest
			</h3>
			<button
				onclick={closePrompt}
				class="text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))] transition-colors"
				aria-label="Close installation prompt"
			>
				<X size={18} />
			</button>
		</div>
		
		<p class="text-[var(--font-size-sm)] text-[hsl(var(--muted-foreground))] mb-4">
			Install ASAP Digest for the best experience. Get offline access, faster loading, and desktop shortcuts.
			{#if deviceOS === 'ios'}
				Tap <span class="inline-block bg-gray-200 dark:bg-gray-700 w-5 h-5 text-center leading-5 rounded mx-1">+</span> in your Safari browser and then "Add to Home Screen".
			{:else if deviceOS === 'android'}
				Tap the menu button in your browser and select "Install" or "Add to Home Screen".
			{/if}
		</p>
		
		{#if deferredPrompt && !isChrome && !isIOSPWA}
			<button 
				class="w-full py-2 px-4 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] rounded-md flex items-center justify-center gap-2"
				onclick={handleInstall}
			>
				<Download size={18} />
				Install Now
			</button>
		{:else if isIOSPWA}
			<button 
				class="w-full py-2 px-4 bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] rounded-md flex items-center justify-center gap-2"
			>
				<svg class="w-5 h-5 mx-1" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
				Add to Home Screen
			</button>
		{/if}
		
		<div class="mt-2 pt-2 border-t border-[hsl(var(--border))]">
			<label class="flex items-center justify-between">
				<span class="text-[var(--font-size-sm)] text-[hsl(var(--muted-foreground))]">Don't show again</span>
				<input 
					type="checkbox" 
					checked={dontShowAgain}
					onclick={() => dontShowAgain = !dontShowAgain}
					class="form-checkbox h-4 w-4 text-[hsl(var(--primary))] rounded border-[hsl(var(--border))]"
				/>
			</label>
		</div>
	</div>
{/if}
<style>
	/* No styles needed - using Tailwind utility classes */
</style>