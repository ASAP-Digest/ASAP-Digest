// This file provides all the common Lucide icons in a format compatible with Svelte 5 runes mode
// It's intended to be used as a drop-in replacement for lucide-svelte imports

import { createIconObject } from './icon-utils';

// Define the SVG paths for the most commonly used icons
// In a real implementation, these would be imported from @lucide/svelte

// Navigation Icons
export const Home = createIconObject('home', '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline>');
export const Search = createIconObject('search', '<circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line>');
export const Calendar = createIconObject('calendar', '<rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line>');
export const User = createIconObject('user', '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle>');
export const Bell = createIconObject('bell', '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path>');
export const Menu = createIconObject('menu', '<line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line>');
export const X = createIconObject('x', '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>');
export const Compass = createIconObject('compass', '<circle cx="12" cy="12" r="10"></circle><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>');
export const Languages = createIconObject('languages', '<path d="m5 8 6 6"></path><path d="m4 14 6-6 2-3"></path><path d="M2 5h12"></path><path d="M7 2h1"></path><path d="m22 22-5-10-5 10"></path><path d="M14 18h6"></path>');
export const HelpCircle = createIconObject('help-circle', '<circle cx="12" cy="12" r="10"></circle><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"></path><line x1="12" y1="17" x2="12.01" y2="17"></line>');
export const Bug = createIconObject('bug', '<rect width="8" height="14" x="8" y="6" rx="4"></rect><path d="m19 7-3 2"></path><path d="m5 7 3 2"></path><path d="m19 19-3-2"></path><path d="m5 19 3-2"></path><path d="M20 13h-4"></path><path d="M4 13h4"></path><path d="m4 8 2 2"></path><path d="m4 16 2-2"></path><path d="m20 8-2 2"></path><path d="m20 16-2-2"></path>');
export const Loader2 = createIconObject('loader2', '<path d="M21 12a9 9 0 1 1-6.219-8.56"></path>');

// Media Controls
export const Play = createIconObject('play', '<polygon points="5 3 19 12 5 21 5 3"></polygon>');
export const Pause = createIconObject('pause', '<rect x="6" y="4" width="4" height="16"></rect><rect x="14" y="4" width="4" height="16"></rect>');
export const Download = createIconObject('download', '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line>');
export const Share2 = createIconObject('share-2', '<circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line>');
export const Clock = createIconObject('clock', '<circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline>');

// Payment and Finance
export const CreditCard = createIconObject('credit-card', '<rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line>');

// Content Icons
export const BookOpen = createIconObject('book-open', '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>');
export const Bookmark = createIconObject('bookmark', '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path>');
export const BookmarkCheck = createIconObject('bookmark-check', '<path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"></path><path d="M9 10l2 2 4-4"></path>');

// Navigation Direction
export const ChevronLeft = createIconObject('chevron-left', '<polyline points="15 18 9 12 15 6"></polyline>');
export const ChevronRight = createIconObject('chevron-right', '<polyline points="9 18 15 12 9 6"></polyline>');
export const ChevronDown = createIconObject('chevron-down', '<polyline points="6 9 12 15 18 9"></polyline>');
export const ArrowRight = createIconObject('arrow-right', '<line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline>');
export const ArrowUpRight = createIconObject('arrow-up-right', '<line x1="7" y1="17" x2="17" y2="7"></line><polyline points="7 7 17 7 17 17"></polyline>');
export const ExternalLink = createIconObject('external-link', '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line>');

// Auth Icons
export const LogIn = createIconObject('log-in', '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line>');
export const LogOut = createIconObject('log-out', '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line>');
export const Mail = createIconObject('mail', '<path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline>');
export const Lock = createIconObject('lock', '<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path>');
export const UserPlus = createIconObject('user-plus', '<path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line>');

// Settings
export const Settings = createIconObject('settings', '<circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>');
export const Filter = createIconObject('filter', '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>');
export const Check = createIconObject('check', '<polyline points="20 6 9 17 4 12"></polyline>');

// Theme
export const Moon = createIconObject('moon', '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>');
export const Sun = createIconObject('sun', '<circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>');

// Misc
export const Maximize = createIconObject('maximize', '<path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>');
export const Plus = createIconObject('plus', '<line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line>');

// Alias for backward compatibility with lucide-svelte/icons imports
export const icons = {
  ExternalLink,
  // Add other icon exports as needed
};

export default {
  Home,
  Search,
  Calendar,
  User,
  Bell,
  Menu,
  X,
  Play,
  Pause,
  Download,
  Share2,
  Clock,
  BookOpen,
  Bookmark,
  BookmarkCheck,
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  ArrowRight,
  ArrowUpRight,
  ExternalLink,
  LogIn,
  LogOut,
  Mail,
  Lock,
  UserPlus,
  Settings,
  Filter,
  Check,
  Moon,
  Sun,
  Maximize,
  Plus,
  Compass,
  Languages,
  HelpCircle,
  CreditCard,
  Bug,
  Loader2
}; 