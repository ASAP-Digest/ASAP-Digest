import { tv } from "tailwind-variants";
import Root from "./alert.svelte";
import Description from "./alert-description.svelte";
import Title from "./alert-title.svelte";

export const alertVariants = tv({
	base: "[&>svg]:text-[hsl(var(--text-1))] relative w-full rounded-[var(--radius-lg)] border border-[hsl(var(--border))] px-4 py-3 text-[var(--font-size-base)] [&>svg+div]:translate-y-[-3px] [&>svg]:absolute [&>svg]:left-4 [&>svg]:top-4 [&>svg~*]:pl-7",
	variants: {
		variant: {
			default: "bg-[hsl(var(--surface-1))] text-[hsl(var(--text-1))]",
			destructive:
				"border-[hsl(var(--functional-error)/0.5)] text-[hsl(var(--functional-error))] [&>svg]:text-[hsl(var(--functional-error))]",
			success:
				"border-[hsl(var(--functional-success)/0.5)] text-[hsl(var(--functional-success))] [&>svg]:text-[hsl(var(--functional-success))]",
		},
	},
	defaultVariants: {
		variant: "default",
	},
});

export {
	Root,
	Description,
	Title,
	//
	Root as Alert,
	Description as AlertDescription,
	Title as AlertTitle,
};
