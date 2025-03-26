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

	// For SSR compatibility, we need to ensure consistent DOM structure
	// but delay loading bits-ui components until client-side
	let TooltipProvider;

	onMount(() => {
		// Only import and use Tooltip in browser environment
		if (browser) {
			// Import directly from the .svelte file
			import("$lib/components/ui/tooltip/tooltip-provider.svelte").then(module => {
				TooltipProvider = module.default; // Assuming default export for provider
				console.log("[SidebarProvider] Tooltip provider loaded");
			});
		}
	});

	let {
		ref = $bindable(null),
		open = $bindable(true),
		onOpenChange = () => {},
		class: className,
		style,
		children,
		...restProps
	} = $props();

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

<!-- Consistent DOM structure for both SSR and client -->
<div
	style="--sidebar-width: {SIDEBAR_WIDTH}; --sidebar-width-icon: {SIDEBAR_WIDTH_ICON}; {style}"
	class={cn(
		"group/sidebar-wrapper flex min-h-svh w-full",
		className
	)}
	bind:this={ref}
	{...restProps}
	data-sidebar-provider
>
	{@render children?.()}
</div>
