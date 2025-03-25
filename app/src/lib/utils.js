import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";
import { cubicOut } from "svelte/easing";
import { fly, scale } from "svelte/transition";

export function cn(...inputs) {
	return twMerge(clsx(inputs));
}

/**
 * Combines fly and scale transitions for a smooth dialog animation
 * @param {HTMLElement} node - The DOM node to animate
 * @param {Object} [params] - Animation parameters
 * @param {number} [params.y=8] - Vertical offset
 * @param {number} [params.x=0] - Horizontal offset
 * @param {number} [params.start=0.95] - Starting scale value
 * @param {number} [params.duration=200] - Animation duration in ms
 * @returns {import('svelte/transition').TransitionConfig}
 */
export function flyAndScale(
	node,
	{ y = 8, x = 0, start = 0.95, duration = 200 } = {}
) {
	const style = getComputedStyle(node);
	const transform = style.transform === "none" ? "" : style.transform;

	const scaleConf = {
		duration,
		easing: cubicOut,
		start,
	};

	const flyConf = {
		duration,
		easing: cubicOut,
		y,
		x,
	};

	return {
		duration,
		css: (t, u) => {
			const fly = `translate(${(1 - t) * x}px, ${(1 - t) * y}px)`;
			const scale = `scale(${1 - (u * (1 - start))})`;
			return `transform: ${transform} ${fly} ${scale};`;
		},
	};
}
