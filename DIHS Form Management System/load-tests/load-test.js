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
const pageLoadTime = new Trend('page_load_time');
const loginTime = new Trend('login_time');

// Test configuration
export const options = {
  stages: [
    { duration: '30s', target: 10 },  // Ramp up to 10 users
    { duration: '1m', target: 10 },   // Stay at 10 users
    { duration: '30s', target: 20 },  // Ramp up to 20 users
    { duration: '1m', target: 20 },   // Stay at 20 users
    { duration: '30s', target: 0 },   // Ramp down to 0 users
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'],  // 95% of requests should be below 2s
    http_req_failed: ['rate<0.1'],      // Less than 10% failed requests
    login_time: ['p(95)<3000'],         // Login should be faster than 3s
  },
};

// Helper function to get CSRF token
function getCsrfToken(html) {
  const match = html.match(/name="csrf_token" value="([^"]+)"/);
  return match ? match[1] : '';
}

// Main test function
export default function () {
  // Each VU will be a different user
  const user = TEST_USERS[__VU % TEST_USERS.length];
  const params = {
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    },
    tags: { name: user.role }
  };

  // Test homepage
  group('Homepage', () => {
    const res = http.get(BASE_URL, params);
    check(res, {
      'homepage status is 200': (r) => r.status === 200,
    }) || errorRate.add(1);
    pageLoadTime.add(res.timings.duration);
  });

  // Test login page
  group('Login Page', () => {
    const res = http.get(`${BASE_URL}/login/`, params);
    check(res, {
      'login page status is 200': (r) => r.status === 200,
      'login form exists': (r) => r.body.includes('login-form')
    }) || errorRate.add(1);
    pageLoadTime.add(res.timings.duration);

    // Test login
    const csrfToken = getCsrfToken(res.body);
    const loginStart = new Date();
    const loginRes = http.post(
      `${BASE_URL}/login/process_login.php`,
      {
        username: user.username,
        password: user.password,
        csrf_token: csrfToken,
      },
      params
    );
    loginTime.add(new Date() - loginStart);

    check(loginRes, {
      'login successful': (r) => r.status === 200,
      'redirected after login': (r) => r.url.includes('dashboard')
    }) || errorRate.add(1);

    // If login failed, stop the test for this VU
    if (loginRes.status !== 200) {
      errorRate.add(1);
      return;
    }

    // Test role-specific dashboard
    group(`${user.role} Dashboard`, () => {
      const dashboardRes = http.get(`${BASE_URL}/${user.role}/dashboard.php`, params);
      check(dashboardRes, {
        'dashboard loaded': (r) => r.status === 200,
        'dashboard contains user data': (r) => r.body.includes('Welcome')
      }) || errorRate.add(1);
      pageLoadTime.add(dashboardRes.timings.duration);

      // Test role-specific pages
      switch (user.role) {
        case 'adviser':
          testAdviserPages(params);
          break;
        case 'registrar':
          testRegistrarPages(params);
          break;
        case 'guidance':
          testGuidancePages(params);
          break;
      }
    });

    // Logout
    group('Logout', () => {
      const logoutRes = http.get(`${BASE_URL}/logout.php`, params);
      check(logoutRes, {
        'logout successful': (r) => r.status === 200
      }) || errorRate.add(1);
    });
  });

  // Add a small delay between iterations
  sleep(1);
}

// Test adviser-specific pages
function testAdviserPages(params) {
  // Test SF9 page
  const sf9Res = http.get(`${BASE_URL}/adviser/adviser_sf9.php`, params);
  check(sf9Res, {
    'SF9 page loaded': (r) => r.status === 200
  }) || errorRate.add(1);
  pageLoadTime.add(sf9Res.timings.duration);

  // Test SF1 page
  const sf1Res = http.get(`${BASE_URL}/adviser/adviser_sf1.php`, params);
  check(sf1Res, {
    'SF1 page loaded': (r) => r.status === 200
  }) || errorRate.add(1);
}

// Test registrar-specific pages
function testRegistrarPages(params) {
  // Test class sections page
  const sectionsRes = http.get(`${BASE_URL}/registrar/class_sections.php`, params);
  check(sectionsRes, {
    'Class sections page loaded': (r) => r.status === 200
  }) || errorRate.add(1);
  pageLoadTime.add(sectionsRes.timings.duration);
}

// Test guidance-specific pages
function testGuidancePages(params) {
  // Test reports page
  const reportsRes = http.get(`${BASE_URL}/guidance/reports.php`, params);
  check(reportsRes, {
    'Reports page loaded': (r) => r.status === 200
  }) || errorRate.add(1);
  pageLoadTime.add(reportsRes.timings.duration);
}

// Simple text summary
export function handleSummary(data) {
  return {
    stdout: JSON.stringify(data, null, 2) + '\n',
  };
}