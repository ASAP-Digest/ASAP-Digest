/**
 * Test script for V5 Auto-Login flow
 * 
 * This script tests the V5 Auto-Login flow by simulating:
 * 1. A fetch to /api/auth/check-wp-session with WordPress cookies
 * 2. Processing the response and verifying Set-Cookie header
 */

// Replace with your actual WordPress cookies
const WP_COOKIES = 'wordpress_logged_in_xyz=sample-cookie-value';
const API_URL = 'http://localhost:5173/api/auth/check-wp-session';

async function testV5AutoLogin() {
  console.log('🧪 Testing V5 Auto-Login Flow');
  console.log('📡 Sending request to:', API_URL);
  
  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Cookie': WP_COOKIES
      },
      credentials: 'include'
    });
    
    console.log('📥 Response status:', response.status);
    
    // Check for Set-Cookie header
    const cookies = response.headers.get('set-cookie');
    if (cookies) {
      console.log('🍪 Set-Cookie header found:', cookies.substring(0, 20) + '...');
    } else {
      console.log('❌ No Set-Cookie header found');
    }
    
    // Parse JSON response
    const result = await response.json();
    console.log('📋 Response body:', JSON.stringify(result, null, 2));
    
    if (result.success) {
      console.log('✅ V5 Auto-Login test passed!');
      console.log('👤 User:', result.user);
    } else {
      console.log('❌ V5 Auto-Login test failed:', result.error);
    }
    
  } catch (error) {
    console.error('💥 Error during test:', error);
  }
}

// Run the test
testV5AutoLogin();

/*
HOW TO RUN THIS TEST:
1. Log into WordPress admin
2. Get your WordPress cookies by visiting asapdigest.local and inspecting cookies
3. Update the WP_COOKIES value in this script
4. Run: node app/scripts/test-v5-autologin.js
*/ 