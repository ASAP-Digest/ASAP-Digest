<script>
	import { onMount } from 'svelte';
	import { Button } from '$lib/components/ui/button';
	import { Download, X } from 'lucide-svelte';
	
	/**
	 * Define the BeforeInstallPromptEvent interface missing from standard TS defs
	 * @typedef {Object} BeforeInstallPromptEvent
	 * @extends {Event}
	 * @property {() => Promise<void>} prompt
	 * @property {Promise<{outcome: 'accepted'|'dismissed'}>} userChoice
	 */
	
	/**
	 * Flag to track if the component has been shown before
	 * @type {boolean}
	 */
	let shown = $state(false);
	
	/**
	 * Flag to track if the PWA is already installed
	 * @type {boolean}
	 */
	let installed = $state(false);
	
	/**
	 * Flag to track if the promptable event is available
	 * @type {boolean}
	 */
	let promptable = $state(false);
	
	/**
	 * Flag to track if the prompt is currently visible
	 * @type {boolean}
	 */
	let visible = $state(false);
	
	/**
	 * Browser/OS information for customized installation instructions
	 * @type {{name: string, os: string}}
	 */
	let browser = $state({ name: 'unknown', os: 'unknown' });
	
	/**
	 * Store the install prompt event
	 * @type {BeforeInstallPromptEvent|null}
	 */
	let deferredPrompt = null;
	
	// Check if PWA is already installed
	function checkInstalled() {
		// Check if in standalone mode or display-mode is standalone
		if (window.matchMedia('(display-mode: standalone)').matches || 
			window.navigator.standalone === true) {
			installed = true;
			return true;
		}
		return false;
	}
	
	// Detect browser and OS for customized instructions
	function detectBrowser() {
		const userAgent = navigator.userAgent;
		
		// Detect OS
		if (/iphone|ipad|ipod/i.test(userAgent)) {
			browser.os = 'ios';
		} else if (/android/i.test(userAgent)) {
			browser.os = 'android';
		} else if (/windows/i.test(userAgent)) {
			browser.os = 'windows';
		} else if (/mac/i.test(userAgent)) {
			browser.os = 'mac';
		} else if (/linux/i.test(userAgent)) {
			browser.os = 'linux';
		}
		
		// Detect browser
		if (/CriOS/i.test(userAgent)) {
			browser.name = 'chrome'; // Chrome on iOS
		} else if (/chrome|chromium|crios/i.test(userAgent)) {
			browser.name = 'chrome';
		} else if (/firefox|fxios/i.test(userAgent)) {
			browser.name = 'firefox';
		} else if (/safari/i.test(userAgent) && !/chrome|chromium|crios/i.test(userAgent)) {
			browser.name = 'safari';
		} else if (/edg/i.test(userAgent)) {
			browser.name = 'edge';
		} else if (/opera|opr/i.test(userAgent)) {
			browser.name = 'opera';
		}
	}
	
	/**
	 * Handle installation request
	 */
	function handleInstall() {
		// Skip if already installed
		if (installed) return;
		
		// If we have the prompt event, use it
		if (deferredPrompt) {
			deferredPrompt.prompt();
			
			deferredPrompt.userChoice.then((choiceResult) => {
				if (choiceResult.outcome === 'accepted') {
					console.log('User accepted the install prompt');
					// Record analytics if available
					if (typeof window !== 'undefined' && 'gtag' in window && typeof window['gtag'] === 'function') {
						window['gtag']('event', 'pwa_install', { event_category: 'PWA', event_label: 'Prompt Accepted' });
					}
					installed = true;
				} else {
					console.log('User dismissed the install prompt');
					// Record analytics if available
					if (typeof window !== 'undefined' && 'gtag' in window && typeof window['gtag'] === 'function') {
						window['gtag']('event', 'pwa_install', { event_category: 'PWA', event_label: 'Prompt Dismissed' });
					}
				}
				
				deferredPrompt = null;
				visible = false;
				shown = true;
				
				// Store in localStorage that we've shown the prompt
				try {
					localStorage.setItem('pwa-prompt-shown', 'true');
				} catch (e) {
					console.debug('Unable to save prompt state to localStorage');
				}
			});
		} else {
			// No prompt available, show manual instructions
			visible = true;
		}
	}
	
	/**
	 * Dismiss the prompt
	 */
	function dismiss() {
		visible = false;
		shown = true;
		
		// Store in localStorage that we've shown the prompt
		try {
			localStorage.setItem('pwa-prompt-shown', 'true');
		} catch (e) {
			console.debug('Unable to save prompt state to localStorage');
		}
	}
	
	onMount(() => {
		// Check if already installed
		if (checkInstalled()) {
			return;
		}
		
		// Detect browser/OS
		detectBrowser();
		
		// Check if we've shown the prompt before
		try {
			shown = localStorage.getItem('pwa-prompt-shown') === 'true';
		} catch (e) {
			console.debug('Unable to access localStorage for prompt state');
		}
		
		// Listen for beforeinstallprompt event
		window.addEventListener('beforeinstallprompt', (e) => {
			// Prevent Chrome 67 and earlier from automatically showing the prompt
			e.preventDefault();
			
			// Store the event for later use
			deferredPrompt = e;
			promptable = true;
			
			// Only show after user has interacted with the site
			if (!shown) {
				// Wait a bit for better UX
				setTimeout(() => {
					if (!installed && !shown) {
						visible = true;
					}
				}, 3000);
			}
		});
		
		// Listen for appinstalled event
		window.addEventListener('appinstalled', () => {
			console.log('PWA was installed');
			installed = true;
			visible = false;
			
			// Record analytics if available
			if (typeof window !== 'undefined' && 'gtag' in window && typeof window['gtag'] === 'function') {
				window['gtag']('event', 'pwa_install', { event_category: 'PWA', event_label: 'Installed' });
			}
		});
	});
</script>

{#if visible && !installed}
	<div class="fixed bottom-4 left-1/2 transform -translate-x-1/2 z-50 max-w-md w-full mx-auto px-4">
		<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 p-4">
			<div class="flex justify-between items-start">
				<div class="flex items-center">
					<img src="/icons/icon-72x72.png" alt="ASAP Digest Logo" class="w-10 h-10 mr-3" />
					<div>
						<h3 class="font-medium text-sm">Install ASAP Digest</h3>
						<p class="text-xs text-gray-600 dark:text-gray-400">
							Get the best experience with our app
						</p>
					</div>
				</div>
				<button 
					aria-label="Close prompt" 
					class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
					onclick={dismiss}
				>
					<X class="w-5 h-5" />
				</button>
			</div>
			
			<div class="mt-3">
				{#if browser.os === 'ios'}
					<p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
						To install, tap the share icon and then "Add to Home Screen"
					</p>
				{:else if promptable}
					<Button class="w-full" onclick={handleInstall}>
						<Download class="w-4 h-4 mr-2" />
						Install App
					</Button>
				{:else}
					<p class="text-xs text-gray-600 dark:text-gray-400 mb-3">
						{#if browser.name === 'chrome' || browser.name === 'edge'}
							Tap the menu (⋮) and select "Install App" or "Add to Home screen"
						{:else if browser.name === 'firefox'}
							Tap the menu (⋮) and select "Install"
						{:else if browser.name === 'safari'}
							Tap the share icon and select "Add to Home Screen"
						{:else}
							Install this app on your device for the best experience
						{/if}
					</p>
				{/if}
			</div>
		</div>
	</div>
{/if} 