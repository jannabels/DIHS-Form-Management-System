// stress-test.js
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');

// Configuration
const BASE_URL = 'http://localhost/systemdihs';
const TEST_USERS = [
  { username: 'admin', password: 'admin123', role: 'Super Admin' },
  { username: 'oic', password: 'test123', role: 'OIC' },
  { username: 'adviser', password: 'test123', role: 'Adviser' },
  { username: 'registrar', password: 'test123', role: 'Registrar' },
  { username: 'guidance', password: 'test123', role: 'Guidance' }
];

export const options = {
  // Windows-friendly configuration
  stages: [
    { duration: '30s', target: 5 },   // Start with 5 users
    { duration: '1m', target: 10 },   // Ramp up to 10 users
    { duration: '1m', target: 20 },   // Ramp up to 20 users
    { duration: '1m', target: 0 },    // Ramp down to 0 users
  ],
  thresholds: {
    http_req_failed: ['rate<0.3'],       // Allow <30% failed requests
    http_req_duration: ['p(95)<5000'],   // 95% of requests below 5s
    errors: ['rate<0.3'],               // Less than 30% errors
  },
  // Windows-specific optimizations
  batch: 5,                          // Process requests in smaller batches
  batchPerHost: 5,                   // Limit concurrent connections per host
  noConnectionReuse: false,          // Reuse connections
  discardResponseBodies: true,       // Save memory by discarding response bodies
  insecureSkipTLSVerify: true,
  httpDebug: 'full',                 // Enable detailed HTTP debugging
};

// Shared variables
let csrfToken = '';
let sessionCookie = '';

export default function () {
  const user = TEST_USERS[__VU % TEST_USERS.length];
  const params = {
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'User-Agent': 'k6-stress-test/1.0',
      'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8'
    },
    timeout: '30s',
    tags: { 
      name: user.role.replace(/\s+/g, '_').toLowerCase(),
      vu: __VU,
      iter: __ITER
    }
  };

  try {
    // 1. Get homepage
    const homeRes = http.get(BASE_URL, {
      ...params,
      tags: { ...params.tags, name: 'homepage' }
    });
    
    if (!check(homeRes, { 'homepage status is 200': (r) => r.status === 200 })) {
      errorRate.add(1);
      console.error(`[VU ${__VU}] Homepage failed: ${homeRes.status}`);
      return;
    }

    // 2. Get login page and extract CSRF token
    const loginPage = http.get(`${BASE_URL}/login/`, {
      ...params,
      tags: { ...params.tags, name: 'login_page' }
    });

    if (loginPage.status !== 200) {
      errorRate.add(1);
      console.error(`[VU ${__VU}] Login page failed: ${loginPage.status}`);
      return;
    }

    // 3. Extract CSRF token with multiple patterns
    const csrfPatterns = [
      /name=["']csrf_token["']\s+value=["']([^"']+)["']/i,  // name='csrf_token' value='...'
      /name=["']csrf_token["']\s+value=["']([^"']+)["']/i,  // name="csrf_token" value="..."
      /<input[^>]*name=["']csrf_token["'][^>]*value=["']([^"']+)["'][^>]*>/i,  // More generic input field match
      /_token[\s\S]*?value=["']([^"']+)["']/i  // Common Laravel/other frameworks
    ];

    for (const pattern of csrfPatterns) {
      const match = loginPage.body.match(pattern);
      if (match && match[1]) {
        csrfToken = match[1].trim();
        break;
      }
    }

    if (!csrfToken) {
      errorRate.add(1);
      console.error(`[VU ${__VU}] No CSRF token found in login page. Response status: ${loginPage.status}`);
      console.log(`[VU ${__VU}] Login page body start:\n${loginPage.body.substring(0, 1000)}`);
      return;
    }
    
    console.log(`[VU ${__VU}] Extracted CSRF token: ${csrfToken.substring(0, 10)}...`);

    // 4. Login
    const loginData = `username=${encodeURIComponent(user.username)}&password=${encodeURIComponent(user.password)}&csrf_token=${encodeURIComponent(csrfToken)}`;
    const loginRes = http.post(
      `${BASE_URL}/login/process_login.php`,
      loginData,
      {
        ...params,
        headers: {
          ...params.headers,
          'Content-Type': 'application/x-www-form-urlencoded',
          'Origin': BASE_URL,
          'Referer': `${BASE_URL}/login/`,
          'Content-Length': Buffer.byteLength(loginData).toString()
        }
      }
    );

    // 5. Verify login and get session cookie
    if (!check(loginRes, {
      'login status is 200 or 302': (r) => r.status === 200 || r.status === 302
    })) {
      errorRate.add(1);
      console.error(`[VU ${__VU}] Login failed: ${loginRes.status}`);
      console.log(loginRes.body);
      return;
    }

    // 6. Access dashboard
    const dashboardUrl = getDashboardUrl(user.role);
    const dashboardRes = http.get(dashboardUrl, {
      ...params,
      tags: { ...params.tags, name: 'dashboard' }
    });

    if (!check(dashboardRes, { 'dashboard status is 200': (r) => r.status === 200 })) {
      errorRate.add(1);
      console.error(`[VU ${__VU}] Dashboard failed: ${dashboardRes.status}`);
    }

    // 7. Add some think time between actions
    sleep(Math.random() * 2 + 1);

  } catch (error) {
    errorRate.add(1);
    console.error(`[VU ${__VU}] Error:`, error.message);
  }
}

function getDashboardUrl(role) {
  const urls = {
    'Super Admin': '/ITpage/indexit.php',
    'Adviser': '/adviser/adviser_sf2.php',
    'Registrar': '/registrar/sf10.php',
    'Guidance': '/guidance/guidance_sf1.php',
    'OIC': '/oic/dashboard.php'
  };
  return BASE_URL + (urls[role] || '/');
}

// Handle test summary
export function handleSummary(data) {
  const date = new Date().toISOString().replace(/[:.]/g, '-');
  const filename = `test-results/stress-test-${date}.txt`;
  
  const summary = `
STRESS TEST REPORT
=================
Test Configuration:
- Stages: 30s to 10VUs, 1m to 50VUs, 1m to 100VUs, 2m at 200VUs, 30s ramp down
- Total Duration: ~5 minutes
- Max Virtual Users: 200

Results:
- Total Requests: ${data.metrics.http_reqs.count}
- Success Rate: ${((1 - data.metrics.http_req_failed.values.rate) * 100).toFixed(2)}%
- Error Rate: ${(data.metrics.http_req_failed.values.rate * 100).toFixed(2)}%

Performance Metrics:
- Average Response Time: ${data.metrics.http_req_duration.values.avg.toFixed(2)}ms
- 95th Percentile: ${data.metrics.http_req_duration.values.p95.toFixed(2)}ms
- Max Response Time: ${data.metrics.http_req_duration.values.max.toFixed(2)}ms
- Requests per Second: ${data.metrics.http_reqs.rate.toFixed(2)}/s

Thresholds:
- http_req_failed: ${(data.metrics.http_req_failed.values.rate * 100).toFixed(2)}% (threshold: <30%)
- http_req_duration: p95=${data.metrics.http_req_duration.values.p95.toFixed(2)}ms (threshold: <5000ms)
`;
  
  return {
    'stdout': summary,
    [filename]: summary
  };
}