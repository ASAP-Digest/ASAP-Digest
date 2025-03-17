import { clsx } from "clsx";
import { twMerge } from "tailwind-merge";

/**
 * Combines multiple class values into a single className string
 * @param {...string} inputs - Class names or conditional class objects
 * @returns {string} - Combined class string
 */
export function cn(...inputs) {
	return twMerge(clsx(inputs));
}
