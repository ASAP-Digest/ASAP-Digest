<script>
	import { onMount } from 'svelte';
	import { Button } from '$lib/components/ui/button';
	import { Download, X } from '$lib/utils/lucide-icons.js';
	
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
		console.log('Install button clicked');
		
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
					console.log('User accepted the install prompt');
					localStorage.setItem('appInstalled', 'true');
					isAppInstalled = true;
				} else {
					console.log('User dismissed the install prompt');
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
	
	// Initialize on mount
	onMount(() => {
		// Detect browser and OS
		detectBrowserAndOS();
		
		// Check if app is installed
		isAppInstalled = checkIfAppInstalled();
		
		// Check if prompt has been shown this session or in localStorage
		const promptShown = localStorage.getItem('installPromptShown') === 'true';
		hasShownPrompt = promptShown;
		
		// Add beforeinstallprompt listener
		window.addEventListener('beforeinstallprompt', (e) => {
			// Prevent Chrome 67+ from automatically showing the prompt
			e.preventDefault();
			
			// Stash the event so it can be triggered later
			deferredPrompt = e;
			
			// Update UI to show install button/banner
			if (!hasShownPrompt && !isAppInstalled) {
				// Show the prompt after a short delay
				setTimeout(() => {
					isPromptVisible = true;
				}, 3000);
			}
		});
		
		// Track when the app is installed
		window.addEventListener('appinstalled', () => {
			// Log app installed
			console.log('PWA was installed');
			
			// Update state
			isAppInstalled = true;
			isPromptVisible = false;
			
			// Save to localStorage
			localStorage.setItem('appInstalled', 'true');
		});
		
		// Check if we need to show the prompt (not installed and not shown yet)
		if (!isAppInstalled && !hasShownPrompt) {
			// Show prompt after a delay
			setTimeout(() => {
				isPromptVisible = true;
			}, 3000);
		}
	});
</script>

{#if isPromptVisible}
	<div class="fixed bottom-[1.5rem] left-[1rem] right-[1rem] md:left-auto md:w-[24rem] md:right-[1.5rem] p-[1rem] bg-white dark:bg-[hsl(var(--card))] rounded-[0.5rem] shadow-lg border border-[hsl(var(--border))] z-[99]">
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
	</div>
{/if} 