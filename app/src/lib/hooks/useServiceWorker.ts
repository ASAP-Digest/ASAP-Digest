import { browser } from '$app/environment';
import { writable } from 'svelte/store';

export const swUpdateAvailable = writable(false);
export const isOnline = writable(true);

let registration: ServiceWorkerRegistration | null = null;

export function registerServiceWorker() {
  if (!browser || !('serviceWorker' in navigator)) {
    return { swRegistration: null };
  }

  // Update online status
  isOnline.set(navigator.onLine);
  window.addEventListener('online', () => isOnline.set(true));
  window.addEventListener('offline', () => isOnline.set(false));

  // Register the service worker
  navigator.serviceWorker.register('/service-worker.js')
    .then((reg) => {
      registration = reg;

      // Check for updates
      const installingWorker = reg.installing;
      if (installingWorker) {
        installingWorker.addEventListener('statechange', () => {
          if (installingWorker.state === 'installed' && navigator.serviceWorker.controller) {
            swUpdateAvailable.set(true);
          }
        });
      }

      // Listen for new workers
      reg.onupdatefound = () => {
        const newWorker = reg.installing;
        if (newWorker) {
          newWorker.onstatechange = () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              swUpdateAvailable.set(true);
            }
          };
        }
      };
    })
    .catch((error) => {
      console.error('Service worker registration failed:', error);
    });

  // Add controller change listener for refreshing the page after update
  navigator.serviceWorker.addEventListener('controllerchange', () => {
    window.location.reload();
  });

  return {
    updateServiceWorker: async () => {
      if (registration && registration.waiting) {
        registration.waiting.postMessage({ type: 'SKIP_WAITING' });
      }
    },
    swRegistration: () => registration
  };
}

// Function to request permission for push notifications
export async function requestNotificationPermission() {
  if (!browser || !('Notification' in window)) {
    return false;
  }

  let permission = Notification.permission;
  
  if (permission !== 'granted') {
    permission = await Notification.requestPermission();
  }
  
  return permission === 'granted';
}

// Function to subscribe to push notifications
export async function subscribeToPushNotifications(serverPublicKey: string) {
  if (!registration || !registration.pushManager) {
    return null;
  }

  try {
    const subscription = await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: urlBase64ToUint8Array(serverPublicKey)
    });

    return subscription;
  } catch (error) {
    console.error('Failed to subscribe to push notifications:', error);
    return null;
  }
}

// Helper function to convert base64 string to Uint8Array
function urlBase64ToUint8Array(base64String: string) {
  const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding)
    .replace(/-/g, '+')
    .replace(/_/g, '/');

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  
  return outputArray;
} 