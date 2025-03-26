<script>
	import { cn } from "$lib/utils.js";
	import { browser } from '$app/environment';
	import {
		SIDEBAR_COOKIE_MAX_AGE,
		SIDEBAR_COOKIE_NAME,
		SIDEBAR_WIDTH,
		SIDEBAR_WIDTH_ICON,
	} from "./constants.js";
	import { setSidebar } from "./context.svelte.js";
	import { onMount } from 'svelte';
	import TooltipProvider from '$lib/components/ui/tooltip/tooltip-provider.svelte';

	/**
	 * @typedef {import('svelte').ComponentProps<TooltipProvider>} TooltipProviderProps
	 */

	/** @type {TooltipProviderProps} */
	let {
		ref = $bindable(null),
		open = $bindable(true),
		onOpenChange = () => {},
		class: className,
		style,
		children,
		...restProps
	} = $props();

	let mounted = $state(false);

	// For SSR compatibility, we need to ensure consistent DOM structure
	// but delay loading bits-ui components until client-side

	onMount(() => {
		mounted = true; // Set mounted to true on client-side mount
	});

	// Check cookie in onMount instead of during initialization
	onMount(() => {
		if (browser) {
			const isOpenInCookie = document.cookie.includes(`${SIDEBAR_COOKIE_NAME}=true`);
			if (document.cookie.includes(SIDEBAR_COOKIE_NAME) && open !== isOpenInCookie) {
				open = isOpenInCookie;
				console.log(`[SidebarProvider] Initialized from cookie: ${open}`);
			}
		}
	});

	const sidebar = setSidebar({
		open: () => open,
		setOpen: (value) => {
			if (open !== value) {
				open = value;
				onOpenChange(value);

				if (browser) {
					document.cookie = `${SIDEBAR_COOKIE_NAME}=${value}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`;
					console.log(`[SidebarProvider] State updated: ${value}`);
				}
			}
		},
	});
</script>

<svelte:window onkeydown={sidebar.handleShortcutKeydown} />

<!-- Consistent outer DOM structure for both SSR and client -->
<div
	style="--sidebar-width: {SIDEBAR_WIDTH}; --sidebar-width-icon: {SIDEBAR_WIDTH_ICON}; {style}"
	class={cn(
		"group/sidebar-wrapper flex min-h-svh w-full",
		className
	)}
	bind:this={ref}
	data-sidebar-provider
>
	{#if mounted}
		<!-- Render TooltipProvider only after mounting on the client -->
		<TooltipProvider {...restProps}>
			{@render children?.()}
		</TooltipProvider>
	{:else}
		<!-- Render children directly during SSR or before mount -->
		{@render children?.()}
	{/if}
</div>
