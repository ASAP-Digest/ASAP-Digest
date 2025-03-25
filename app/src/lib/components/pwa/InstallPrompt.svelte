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
	<div class="install-widget p-2.5 fixed bottom-[1.5rem] left-[1rem] right-[1rem] md:left-auto md:w-[24rem] md:right-[1.5rem] bg-white dark:bg-[hsl(var(--card))] rounded-[0.5rem] shadow-lg border border-[hsl(var(--border))] z-[99]">
		<div class="flex justify-between items-start mb-[0.75rem]">
			<h3 class="text-[1rem] font-semibold flex items-center gap-[0.5rem]">
				<Download size={18} />
				<span>Install ASAP Digest</span>
			</h3>
			
			<button 
				onclick={closePrompt}
				class="text-[hsl(var(--muted-foreground))] hover:text-[hsl(var(--foreground))]"
				aria-label="Close installation prompt"
			>
				<X size={18} />
			</button>
		</div>
			
		<p class="text-[0.875rem] text-[hsl(var(--muted-foreground))] mb-[1rem]">
			{#if deviceOS === 'ios'}
				Add ASAP Digest to your Home Screen for a better experience. Tap <span class="inline-block w-[1rem] h-[1rem] bg-[#1677ff] text-white text-center leading-[1rem] rounded-[0.25rem] mx-[0.25rem]">+</span> in your Safari browser and then "Add to Home Screen".
			{:else if deviceOS === 'android'}
				Install ASAP Digest as an app on your device for a better experience with offline access.
			{:else}
				Install ASAP Digest for offline access and a better experience.
			{/if}
		</p>
		
		{#if deferredPrompt || deviceOS === 'desktop'}
			<button 
				onclick={handleInstall}
				class="w-full py-[0.5rem] px-[1rem] bg-[hsl(var(--primary))] text-[hsl(var(--primary-foreground))] rounded-[0.375rem] flex items-center justify-center gap-[0.5rem]"
			>
				<Download size={16} />
				<span>Install Now</span>
			</button>
		{:else if deviceOS === 'ios'}
			<div class="flex items-center justify-center text-[0.875rem] text-[hsl(var(--muted-foreground))]">
				<span>Tap</span>
				<svg class="w-[1.25rem] h-[1.25rem] mx-[0.25rem]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
				<span>then "Add to Home Screen"</span>
			</div>
		{/if}

		{#if isTestMode}
			<div class="mt-2 pt-2 border-t border-[hsl(var(--border))]">
				<p class="text-[0.75rem] text-[hsl(var(--muted-foreground))]">Test Mode Active</p>
			</div>
		{/if}
	</div>
{/if}
<style>
	/* No styles needed - using Tailwind utility classes */
</style>