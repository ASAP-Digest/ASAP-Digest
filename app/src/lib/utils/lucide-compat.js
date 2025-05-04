// lucide-compat.js
// This file provides a compatibility layer for the transition from lucide-svelte to @lucide/svelte
// It allows existing imports from 'lucide-svelte' to continue working with Svelte 5 runes

import { createIconObject } from './icon-utils.js';
// Correct the import source based on package.json
// import { /* ...icons... */ } from 'lucide-svelte'; // Incorrect source

// Import raw SVG paths for common icons
// In a full implementation, you would import all icons from @lucide/svelte
// For now, we define paths directly or use placeholders
const iconPaths = {
	home: '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>',
	user: '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>',
	settings: '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>',
	loader2: '<path d="M21 12a9 9 0 1 1-6.219-8.56"></path>',
	mail: '<rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>',
	arrowLeft: '<path d="M19 12H5M12 19l-7-7 7-7"></path>',
	logIn: '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line>',
	logOut: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>',
	check: '<polyline points="20 6 9 17 4 12"></polyline>',
	x: '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>',
	alertCircle: '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line>',
	info: '<circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line>',
	circleUser: '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="10" r="3"/><path d="M7 20.662V19a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v1.662"/>',
	shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>',
	bell: '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path>',
	menu: '<line x1="4" x2="20" y1="12" y2="12"></line><line x1="4" x2="20" y1="6" y2="6"></line><line x1="4" x2="20" y1="18" y2="18"></line>',
	search: '<circle cx="11" cy="11" r="8"></circle><line x1="21" x2="16.65" y1="21" y2="16.65"></line>',
	layoutDashboard: '<rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect>',
	bookOpen: '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>',
	share2: '<circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"></line><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"></line>',
	creditCard: '<rect width="20" height="14" x="2" y="5" rx="2"></rect><line x1="2" x2="22" y1="10" y2="10"></line>',
	chevronDown: '<path d="m6 9 6 6 6-6"></path>',
	chevronLeft: '<path d="M15 18l-6-6 6-6"/>',
	chevronRight: '<path d="M9 18l6-6-6-6"/>',
	compass: '<circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>',
	newspaper: '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2"></path><path d="M8 6h8"></path><path d="M8 10h8"></path><path d="M8 14h4"></path>',
	lifeBuoy: '<circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="4"></circle><line x1="4.93" x2="9.17" y1="4.93" y2="9.17"></line><line x1="14.83" x2="19.07" y1="9.17" y2="4.93"></line><line x1="14.83" x2="19.07" y1="14.83" y2="19.07"></line><line x1="4.93" x2="9.17" y1="19.07" y2="14.83"></line>',
	moon: '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
	sun: '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
	globe: '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20z"/><path d="M2 12h20"/>',
	calendar: '<rect width="18" height="18" x="3" y="4" rx="2" ry="2"></rect><line x1="16" x2="16" y1="2" y2="6"></line><line x1="8" x2="8" y1="2" y2="6"></line><line x1="3" x2="21" y1="10" y2="10"></line>',
	download: '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" x2="12" y1="15" y2="3"></line>',
	play: '<polygon points="5 3 19 12 5 21 5 3"></polygon>',
	clock: '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>',
	bookmark: '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>',
	barChart2: '<line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line>',
	activity: '<polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>',
	// Add FileText icon path
	fileText: '<path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><line x1="10" y1="9" x2="8" y2="9"></line>',
	// Add Plus icon path
	plus: '<line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>',
	// Add Circle icon path
	circle: '<circle cx="12" cy="12" r="10"></circle>'
};

// Revert exports to use the original pattern
export const Home = createIconObject('home', iconPaths.home);
export const User = createIconObject('user', iconPaths.user);
export const Settings = createIconObject('settings', iconPaths.settings);
export const Loader2 = createIconObject('loader2', iconPaths.loader2);
export const Mail = createIconObject('mail', iconPaths.mail);
export const ArrowLeft = createIconObject('arrowLeft', iconPaths.arrowLeft);
export const LogIn = createIconObject('logIn', iconPaths.logIn);
export const LogOut = createIconObject('logOut', iconPaths.logOut);
export const Check = createIconObject('check', iconPaths.check);
export const X = createIconObject('x', iconPaths.x);
export const AlertCircle = createIconObject('alertCircle', iconPaths.alertCircle);
export const Info = createIconObject('info', iconPaths.info);
export const CircleUser = createIconObject('circleUser', iconPaths.circleUser);
export const Shield = createIconObject('shield', iconPaths.shield);
export const Bell = createIconObject('bell', iconPaths.bell);
export const Menu = createIconObject('menu', iconPaths.menu);
export const Search = createIconObject('search', iconPaths.search);
export const LayoutDashboard = createIconObject('layoutDashboard', iconPaths.layoutDashboard);
export const BookOpen = createIconObject('bookOpen', iconPaths.bookOpen);
export const Share2 = createIconObject('share2', iconPaths.share2);
export const CreditCard = createIconObject('creditCard', iconPaths.creditCard);
export const ChevronDown = createIconObject('chevronDown', iconPaths.chevronDown);
export const ChevronLeft = createIconObject('chevronLeft', iconPaths.chevronLeft);
export const ChevronRight = createIconObject('chevronRight', iconPaths.chevronRight);
export const Compass = createIconObject('compass', iconPaths.compass);
export const Newspaper = createIconObject('newspaper', iconPaths.newspaper);
export const LifeBuoy = createIconObject('lifeBuoy', iconPaths.lifeBuoy);
export const Moon = createIconObject('moon', iconPaths.moon);
export const Sun = createIconObject('sun', iconPaths.sun);
export const Globe = createIconObject('globe', iconPaths.globe);
export const Calendar = createIconObject('calendar', iconPaths.calendar);
export const Download = createIconObject('download', iconPaths.download);
export const Play = createIconObject('play', iconPaths.play);
export const Clock = createIconObject('clock', iconPaths.clock);
export const Bookmark = createIconObject('bookmark', iconPaths.bookmark);
export const BarChart2 = createIconObject('barChart2', iconPaths.barChart2);
// Add BookmarkIcon and Activity exports
export const BookmarkIcon = createIconObject('bookmark', iconPaths.bookmark); // Using the same path as Bookmark
export const Activity = createIconObject('activity', iconPaths.activity);
// Add FileText export
export const FileText = createIconObject('fileText', iconPaths.fileText);
// Add Plus export
export const Plus = createIconObject('plus', iconPaths.plus);
// Add Circle export
export const Circle = createIconObject('circle', iconPaths.circle);

/**
 * Create a custom icon component from a name and SVG content
 * @param {string} name The name of the icon
 * @param {string} svgContent The SVG content as a string 
 * @returns {object} A Svelte component that renders the icon
 */
export function createCustomIcon(name, svgContent) {
	return createIconObject(name, svgContent);
} 