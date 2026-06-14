# First, create the file
@"
import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Configuration
const BASE_URL = 'http://localhost/systemdihs';
const TEST_USERS = [
  { username: 'test_adviser', password: 'test123', role: 'adviser' },
  { username: 'test_registrar', password: 'test123', role: 'registrar' },
  { username: 'test_guidance', password: 'test123', role: 'guidance' }
];

// Custom metrics
const errorRate = new Rate('errors');
const responseTime = new Trend('response_time');
const pageLoadTime = new Trend('page_load_time');

// Combined test phases
export const options = {
  // Phase 1: Smoke test (quick health check)
  scenarios: {
    smoke: {
      executor: 'constant-vus',
      vus: 2,
      duration: '1m',
      gracefulStop: '30s',
      tags: { test_type: 'smoke' },
      exec: 'testSmoke',
    },
    // Phase 2: Load test (normal operation)
    load: {
      executor: 'ramping-vus',
      startVUs: 5,
      stages: [
        { duration: '2m', target: 20 },  // Ramp up to 20 users
        { duration: '5m', target: 20 },  // Stay at 20 users
        { duration: '2m', target: 0 },   // Ramp down
      ],
      gracefulRampDown: '30s',
      startTime: '1m',  // Start after smoke test
      tags: { test_type: 'load' },
      exec: 'testLoad',
    },
    // Phase 3: Stress test (break the system)
    stress: {
      executor: 'ramping-arrival-rate',
      preAllocatedVUs: 10,
      timeUnit: '1s',
      stages: [
        { duration: '2m', target: 50 },  // Ramp up to 50 iterations/s
        { duration: '5m', target: 50 },  // Stay at 50 iterations/s
        { duration: '2m', target: 0 },   // Ramp down
      ],
      gracefulStop: '30s',
      startTime: '10m',  // Start after load test
      tags: { test_type: 'stress' },
      exec: 'testStress',
    }
  },
  thresholds: {
    'http_req_duration{test_type:smoke}': ['p(95)<1000'],
    'http_req_duration{test_type:load}': ['p(95)<2000'],
    'http_req_duration{test_type:stress}': ['p(95)<5000'],
    'http_req_failed': ['rate<0.1'],
  }
};

// Helper functions
function getCsrfToken(html) {
  const match = html.match(/name=\"csrf_token\" value=\"([^\"]+)\"/);
  return match ? match[1] : '';
}

function login(user, params) {
  const loginPage = http.get(`${BASE_URL}/login/`, params);
  if (!loginPage) return null;
  
  const csrfToken = getCsrfToken(loginPage.body);
  const loginRes = http.post(
    `${BASE_URL}/login/process_login.php`,
    {
      username: user.username,
      password: user.password,
      csrf_token: csrfToken,
    },
    params
  );
  
  return loginRes.status === 200;
}

function testPage(url, params, checkFn) {
  const start = new Date();
  const res = http.get(url, params);
  const duration = new Date() - start;
  
  if (checkFn) {
    check(res, checkFn) || errorRate.add(1);
  }
  
  pageLoadTime.add(duration);
  responseTime.add(res.timings.duration);
  
  return res;
}

// Test scenarios
export function testSmoke() {
  const user = TEST_USERS[0]; // Test with first user
  const params = { tags: { name: 'smoke_test' } };
  
  // Test homepage
  testPage(BASE_URL, params, {
    'homepage status is 200': (r) => r.status === 200
  });
  
  // Test login
  const loggedIn = login(user, params);
  check(loggedIn, { 'login successful': (r) => r === true }) || errorRate.add(1);
}

export function testLoad() {
  const user = TEST_USERS[__VU % TEST_USERS.length];
  const params = { tags: { name: `load_test_${user.role}` } };
  
  // Test homepage
  testPage(BASE_URL, params, {
    'homepage status is 200': (r) => r.status === 200
  });
  
  // Test login and dashboard
  if (login(user, params)) {
    testPage(`${BASE_URL}/${user.role}/dashboard.php`, params, {
      'dashboard loaded': (r) => r.status === 200
    });
    
    // Test role-specific pages
    if (user.role === 'adviser') {
      testPage(`${BASE_URL}/adviser/adviser_sf9.php`, params);
      testPage(`${BASE_URL}/adviser/adviser_sf1.php`, params);
    }
  }
  
  sleep(Math.random() * 2 + 1); // Random think time 1-3s
}

export function testStress() {
  const user = TEST_USERS[__VU % TEST_USERS.length];
  const params = { 
    tags: { name: `stress_test_${user.role}` },
    timeout: '30s'  // Increase timeout for stress test
  };
  
  // Test multiple pages in parallel
  http.batch([
    ['GET', BASE_URL, null, params],
    ['GET', `${BASE_URL}/login/`, null, params],
    ['GET', `${BASE_URL}/${user.role}/dashboard.php`, null, params]
  ]);
  
  // Random sleep to simulate user think time
  sleep(Math.random() * 5);
}

// Main function (not used directly, but required by k6)
export default function () {
  // This is a fallback and won't be called directly
  // because we're using named scenarios
}

// Text summary
export function handleSummary(data) {
  const metrics = data.metrics;
  const result = {};
  
  // Generate a summary for each test type
  ['smoke', 'load', 'stress'].forEach(testType => {
    const prefix = testType === 'smoke' ? '' : `{test_type:${testType}}`;
    const reqDuration = metrics[`http_req_duration${prefix}`];
    
    if (reqDuration) {
      result[`${testType}-summary.txt`] = `
${testType.toUpperCase()} TEST RESULTS:
-----------------------------
HTTP Metrics:
  - Avg response time: ${reqDuration.values.avg.toFixed(2)}ms
  - p95 response time: ${reqDuration.values.p95.toFixed(2)}ms
  - Total requests: ${metrics.http_reqs.count}
  - Error rate: ${(metrics.http_req_failed.values.rate * 100).toFixed(2)}%
  
Iterations: ${metrics.iterations.count}
VUs: ${metrics.vus.values.max}
Data transferred: ${(metrics.data_received.values.count / 1024 / 1024).toFixed(2)} MB
      `;
    }
  });
  
  return result;
}
"@ | Out-File -FilePath "C:\xampp\htdocs\systemdihs\load-tests\combined-performance-test.js" -Encoding utf8

# Then run the test
cd C:\xampp\htdocs\systemdihs
k6 run load-tests/combined-performance-test.js