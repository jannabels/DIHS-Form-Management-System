import http from 'k6/http';
import { check, sleep } from 'k6';

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
  vus: 5,  // One virtual user per test account
  iterations: 10,  // 10 iterations per VU
  maxRedirects: 5,
  noConnectionReuse: true,
  discardResponseBodies: false, // Keep response bodies for debugging
  thresholds: {
    http_req_failed: ['rate<0.2'],  // Allow <20% failed requests
    http_req_duration: ['p(95)<3000'],  // 95% of requests below 3s
    checks: ['rate>0.8']  // At least 80% of checks must pass
  }
};

// Helper function to get CSRF token
function getCsrfToken(html) {
  try {
    const match = html.match(/name="csrf_token" value="([^"]+)"/i) || 
                 html.match(/name="_token" value="([^"]+)"/i) ||
                 html.match(/name="csrf-token" content="([^"]+)"/i);
    return match ? match[1] : null;
  } catch (error) {
    console.error('Error extracting CSRF token:', error);
    return null;
  }
}

// Helper function to log request errors
function logRequestError(operation, response) {
  console.error(`[ERROR] ${operation} failed with status ${response.status}:
    URL: ${response.url}
    Error: ${response.error || 'No error message'}
    Headers: ${JSON.stringify(response.headers, null, 2)}
    Response (first 200 chars): ${response.body ? response.body.substring(0, 200) : 'No response body'}`);
}

// Function to verify login redirection
function verifyLoginRedirect(loginRes, role) {
  const expectedRedirects = {
    'Super Admin': /ITpage\/indexit\.php$/i,
    'Adviser': /adviser\/adviser_sf2\.php$/i,
    'Registrar': /registrar\/sf10\.php$/i,
    'Guidance': /guidance\/guidance_sf1\.php$/i,
    'OIC': /oic\/dashboard\.php$/i
  };

  const expectedPattern = expectedRedirects[role];
  const redirectUrl = loginRes.url;
  const statusCheck = loginRes.status === 200 || 
                     loginRes.status === 302 || 
                     loginRes.status === 303;

  const redirectCheck = !expectedPattern || expectedPattern.test(redirectUrl);

  if (!statusCheck || !redirectCheck) {
    console.log(`Login redirect verification failed for ${role}:
      Status: ${loginRes.status}
      Expected URL pattern: ${expectedPattern}
      Actual URL: ${redirectUrl}`);
  }

  return check(loginRes, {
    'login status is 200 or redirect': (r) => statusCheck,
    'login redirects to correct page': () => redirectCheck
  });
}

// Function to test role-specific pages
function testRolePages(role, params) {
  let allChecksPassed = true;
  const results = [];
  
  const makeRequest = (url, checkName, expectedStatus = 200) => {
    const result = {
      url,
      checkName,
      success: false,
      status: 0,
      error: null
    };

    for (let attempt = 1; attempt <= 3; attempt++) {
      try {
        const res = http.get(url, {
          ...params,
          redirects: 5,
          timeout: '30s',
          tags: { ...params.tags, name: checkName.replace(/\s+/g, '_').toLowerCase() }
        });
        
        result.status = res.status;
        result.success = res.status === expectedStatus;
        
        if (result.success) {
          results.push({...result, success: true});
          return true;
        }

        if (attempt < 3) {
          console.log(`Retry ${attempt} for ${url} - Status: ${res.status}`);
          sleep(1);
        } else {
          result.error = `Status ${res.status} !== ${expectedStatus}`;
          results.push(result);
          logRequestError(checkName, res);
        }
      } catch (error) {
        result.error = error.message;
        if (attempt >= 3) {
          results.push(result);
          return false;
        }
        sleep(1);
      }
    }
    return false;
  };

  // Test dashboard based on role
  switch(role) {
    case 'Super Admin':
      allChecksPassed = makeRequest(
        `${BASE_URL}/ITpage/indexit.php`,
        'Admin Dashboard'
      ) && allChecksPassed;
      break;
      
    case 'Adviser':
      allChecksPassed = makeRequest(
        `${BASE_URL}/adviser/adviser_sf2.php`,
        'Adviser SF2'
      ) && allChecksPassed;
      
      allChecksPassed = makeRequest(
        `${BASE_URL}/adviser/adviser_sf9.php`,
        'Adviser SF9'
      ) && allChecksPassed;
      break;
      
    case 'Registrar':
      allChecksPassed = makeRequest(
        `${BASE_URL}/registrar/sf10.php`,
        'Registrar SF10'
      ) && allChecksPassed;
      
      allChecksPassed = makeRequest(
        `${BASE_URL}/registrar/class_sections.php`,
        'Class Sections'
      ) && allChecksPassed;
      break;
      
    case 'Guidance':
      allChecksPassed = makeRequest(
        `${BASE_URL}/guidance/guidance_sf1.php`,
        'Guidance SF1'
      ) && allChecksPassed;
      
      allChecksPassed = makeRequest(
        `${BASE_URL}/guidance/reports.php`,
        'Reports'
      ) && allChecksPassed;
      break;
      
    case 'OIC':
      allChecksPassed = makeRequest(
        `${BASE_URL}/oic/dashboard.php`,
        'OIC Dashboard'
      ) && allChecksPassed;
      break;
  }

  // Log detailed results for debugging
  console.log(`\nTest results for ${role}:`);
  results.forEach((r, i) => {
    const status = r.success ? '✓' : '✗';
    console.log(`${i + 1}. [${status}] ${r.checkName} - ${r.url} (${r.status || 'error'})`);
    if (r.error) console.log(`   Error: ${r.error}`);
  });

  return allChecksPassed;
}

export default function () {
  const user = TEST_USERS[__VU % TEST_USERS.length];
  const params = {
    headers: { 
      'Content-Type': 'application/x-www-form-urlencoded',
      'User-Agent': 'k6-load-test/1.0',
      'Connection': 'keep-alive',
      'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
      'Accept-Language': 'en-US,en;q=0.5',
      'Cache-Control': 'no-cache'
    },
    cookies: {},
    timeout: '30s',
    redirects: 5,
    tags: { 
      name: user.role.replace(/\s+/g, '_').toLowerCase(),
      vu: __VU,
      iter: __ITER
    }
  };

  // 1. Test homepage
  console.log(`\n[${user.role}] Starting test iteration ${__ITER}...`);
  const homeRes = http.get(BASE_URL, {
    ...params,
    tags: { ...params.tags, name: 'homepage' }
  });
  
  const homeCheck = check(homeRes, { 
    'homepage status is 200': (r) => r.status === 200 
  });
  
  if (!homeCheck) {
    logRequestError('Homepage access', homeRes);
    return false;
  }

  // 2. Get login page
  console.log(`[${user.role}] Loading login page...`);
  const loginPage = http.get(`${BASE_URL}/login/`, {
    ...params,
    tags: { ...params.tags, name: 'login_page' }
  });

  if (loginPage.status !== 200) {
    console.error(`[${user.role}] Login page failed: ${loginPage.status}`);
    logRequestError('Login page', loginPage);
    return false;
  }

  // 3. Extract CSRF token
  console.log(`[${user.role}] Extracting CSRF token...`);
  const csrfToken = getCsrfToken(loginPage.body);
  if (!csrfToken) {
    console.error(`[${user.role}] No CSRF token found in login form`);
    console.log(`[${user.role}] Login form HTML (first 500 chars):`, 
      loginPage.body.substring(0, 500));
    return false;
  }

  // 4. Attempt login
  console.log(`[${user.role}] Attempting login...`);
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
      },
      tags: { ...params.tags, name: 'login_attempt' }
    }
  );

  // 5. Verify login was successful
  console.log(`[${user.role}] Verifying login...`);
  const loginSuccess = verifyLoginRedirect(loginRes, user.role);
  
  if (!loginSuccess) {
    console.error(`[${user.role}] Login failed - Status: ${loginRes.status}`);
    console.log(`[${user.role}] Login response URL: ${loginRes.url}`);
    console.log(`[${user.role}] Response headers:`, JSON.stringify(loginRes.headers, null, 2));
    console.log(`[${user.role}] Response body (first 500 chars):`, 
      loginRes.body ? loginRes.body.substring(0, 500) : 'No response body');
    return false;
  }

  // 6. Follow redirect if needed
  let dashboardRes = loginRes;
  if ([301, 302, 303, 307, 308].includes(loginRes.status) && loginRes.headers['Location']) {
    console.log(`[${user.role}] Following redirect to: ${loginRes.headers['Location']}`);
    dashboardRes = http.get(loginRes.headers['Location'], {
      ...params,
      tags: { ...params.tags, name: 'post_login_redirect' }
    });
  }

  // 7. Test role-specific pages
  console.log(`[${user.role}] Testing role-specific pages...`);
  const roleTestSuccess = testRolePages(user.role, {
    ...params,
    cookies: {
      ...params.cookies,
      ...dashboardRes.cookies
    }
  });

  // 8. Logout
  console.log(`[${user.role}] Logging out...`);
  try {
    const logoutRes = http.get(`${BASE_URL}/logout.php`, {
      ...params,
      tags: { ...params.tags, name: 'logout' }
    });
    check(logoutRes, { 'logout successful': (r) => r.status === 200 });
  } catch (error) {
    console.error(`[${user.role}] Logout failed:`, error.message);
  }

  // Add a small delay between iterations
  sleep(1);
  return roleTestSuccess;
}

// Enhanced text summary
export function handleSummary(data) {
  const totalChecks = data.metrics.checks.values.count;
  const passedChecks = data.metrics.checks.passes;
  const successRate = totalChecks > 0 ? (passedChecks / totalChecks * 100).toFixed(2) : 0;
  const failureRate = (data.metrics.http_req_failed.values.rate * 100).toFixed(2);
  const avgResponseTime = data.metrics.http_req_duration.values.avg.toFixed(2);
  const p95ResponseTime = data.metrics.http_req_duration.values.p95.toFixed(2);
  const maxResponseTime = data.metrics.http_req_duration.values.max.toFixed(2);
  const dataReceivedKB = (data.metrics.data_received.values.count / 1024).toFixed(2);
  const dataSentKB = (data.metrics.data_sent.values.count / 1024).toFixed(2);
  
  // Get test duration in seconds
  const testDuration = (data.state.testRunDurationMs / 1000).toFixed(2);
  
  // Calculate requests per second
  const rps = data.metrics.http_reqs.rate.toFixed(2);
  
  // Create a detailed summary
  const summary = `
RELIABILITY TEST REPORT
======================
Test Configuration:
- Virtual Users: ${options.vus}
- Iterations: ${data.metrics.iterations.count}
- Test Duration: ${testDuration}s
- Requests per Second: ${rps}

Success Metrics:
- Success Rate: ${successRate}%
- Total Checks: ${totalChecks}
- Passed Checks: ${passedChecks}
- Failed Checks: ${data.metrics.checks.fails}
- Failed Requests: ${data.metrics.http_req_failed.count} (${failureRate}%)

Performance Metrics:
- Average Response Time: ${avgResponseTime}ms
- 95th Percentile: ${p95ResponseTime}ms
- Maximum Response Time: ${maxResponseTime}ms

Network:
- Total Requests: ${data.metrics.http_reqs.count}
- Data Received: ${dataReceivedKB} KB
- Data Sent: ${dataSentKB} KB

Thresholds:
- http_req_failed: ${failureRate}% (threshold: <20%)
- http_req_duration: p95=${p95ResponseTime}ms (threshold: <3000ms)
- checks: ${successRate}% (threshold: >80%)
`;

  // Save detailed results to a file
  const date = new Date().toISOString().replace(/[:.]/g, '-');
  const filename = `test-results/report-${date}.txt`;
  
  return {
    'stdout': summary,
    [filename]: summary
  };
}