import http from 'k6/http';
import { check, sleep } from 'k6';

// Configuration
const BASE_URL = 'http://localhost';
const USERNAME = 'testuser';
const PASSWORD = 'testpass';

// Test configuration
export const options = {
  // Run 1 user for 1 minute
  vus: 1,
  duration: '1m',
  thresholds: {
    // 99% of requests must complete below 1.5s
    http_req_duration: ['p(99)<1500'],
    // 99% of requests must complete successfully
    http_req_failed: ['rate<0.01'],
  },
};

// Main test function
export default function () {
  // Test 1: Check main page
  const mainPageRes = http.get(BASE_URL);
  check(mainPageRes, {
    'main page status is 200': (r) => r.status === 200,
    'main page has title': (r) => r.body.includes('<title>'),
  });

  // Test 2: Test login
  const loginRes = http.post(`${BASE_URL}/login`, {
    username: USERNAME,
    password: PASSWORD,
  });

  check(loginRes, {
    'login status is 200': (r) => r.status === 200,
    'login successful': (r) => r.json('success') === true,
  });

  // Get auth token from login response
  const authToken = loginRes.json('token');
  
  // Set headers for authenticated requests
  const params = {
    headers: {
      'Authorization': `Bearer ${authToken}`,
      'Content-Type': 'application/json',
    },
  };

  // Test 3: Access protected route (example: get student grades)
  const gradesRes = http.get(`${BASE_URL}/api/student/grades`, params);
  check(gradesRes, {
    'grades status is 200': (r) => r.status === 200,
    'has grades data': (r) => r.json() !== null,
  });

  // Add a small delay between iterations
  sleep(1);
}

// Setup function (runs once before the test)
export function setup() {
  // You can add any setup code here, like creating test users
  console.log('Setting up test...');
  return { startTime: new Date() };
}

// Teardown function (runs once after the test)
export function teardown(data) {
  // You can add any cleanup code here
  console.log(`Test completed. Duration: ${new Date() - data.startTime}ms`);
}
