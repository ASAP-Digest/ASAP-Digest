import { Dialog as DialogPrimitive } from "bits-ui";
// @ts-ignore - Svelte component import
import Title from "./dialog-title.svelte";
import Portal from "./dialog-portal.svelte";
// @ts-ignore - Svelte component import
import Footer from "./dialog-footer.svelte";
import Header from "./dialog-header.svelte";
// @ts-ignore - Svelte component import
import Overlay from "./dialog-overlay.svelte";
import Content from "./dialog-content.svelte";
// @ts-ignore - Svelte component import
import Description from "./dialog-description.svelte";
const Root = DialogPrimitive.Root;
const Trigger = DialogPrimitive.Trigger;
const Close = DialogPrimitive.Close;
export {
	Root,
	Title,
	Portal,
	Footer,
	Header,
	Trigger,
	Overlay,
	Content,
	Description,
	Close,
	//
	Root as Dialog,
	Title as DialogTitle,
	Portal as DialogPortal,
	Footer as DialogFooter,
	Header as DialogHeader,
	Trigger as DialogTrigger,
	Overlay as DialogOverlay,
	Content as DialogContent,
	Description as DialogDescription,
	Close as DialogClose,
};
