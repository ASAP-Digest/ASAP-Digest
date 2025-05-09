import { writable } from 'svelte/store';

/**
 * @typedef {Object} User
 * @property {string} id
 * @property {string=} displayName
 * @property {string[]=} roles
 * @property {string} [avatarUrl]
 * @property {string} [plan]
 * @property {string} [email]
 */

/** @type {import('svelte/store').Writable<User|null>} */
export const user = writable(null); 