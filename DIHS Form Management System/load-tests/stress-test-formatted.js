import http from 'k6/http';
import { check, sleep } from 'k6';

// Configuration
const BASE_URL = 'http://localhost/systemdihs';
const TEST_USERS = [
  { username: 'test_adviser', password: 'test123' },
  { username: 'test_registrar', password: 'test123' },
  { username: 'test_guidance', password: 'test123' }
];

export const options = {
  stages: [
    { duration: '30s', target: 6 },  // Ramp up to 6 users
    { duration: '1m', target: 6 },   // Stay at 6 users
    { duration: '30s', target: 0 },  // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<1000'],
    http_req_failed: ['rate<0.1'],
  },
  discardResponseBodies: true,
};

export default function () {
  const user = TEST_USERS[__VU % TEST_USERS.length];
  
  // Homepage
  const homeRes = http.get(BASE_URL);
  check(homeRes, { 'status is 200': (r) => r.status === 200 });
  
  // Login page
  const loginPage = http.get(`${BASE_URL}/login/`);
  check(loginPage, { 'login page loaded': (r) => r.status === 200 });
  
  // Login
  const loginRes = http.post(
    `${BASE_URL}/login/process_login.php`,
    `username=${user.username}&password=${user.password}`,
    { headers: { 'Content-Type': 'application/x-www-form-urlencoded' } }
  );
  
  // If login successful, access dashboard
  if (loginRes.status === 200) {
    http.get(`${BASE_URL}/dashboard.php`);
  }
  
  sleep(1); // Think time
}

export function handleSummary(data) {
  const metrics = data.metrics;
  const httpReq = metrics.http_req_duration.values;
  const failedReqs = metrics.http_req_failed.values['rate<0.1'];
  
  return {
    stdout: `
HTTP
http_req_duration
{ expected_response: true }
http_req_failed
http_reqs

EXECUTION
iteration_duration
iterations
vus
vus_max

NETWORK
data_received
data_sent

p(95)=${(httpReq.p95).toFixed(2)}ms

${metrics.http_reqs.count}

avg=${(httpReq.avg).toFixed(2)}ms min=${(httpReq.min).toFixed(2)}ms med=${(httpReq.med).toFixed(2)}ms max=${(httpReq.max).toFixed(2)}ms p(90)=${(httpReq.p90).toFixed(2)}ms p(95)=${(httpReq.p95).toFixed(2)}ms
avg=${(metrics.iteration_duration.values.avg / 1000).toFixed(2)}s min=${(metrics.iteration_duration.values.min / 1000).toFixed(2)}s med=${(metrics.iteration_duration.values.med / 1000).toFixed(2)}s
${(failedReqs * 100).toFixed(2)}% ${Math.round(failedReqs * metrics.http_reqs.count)} out of ${metrics.http_reqs.count}
${(metrics.http_reqs.rate).toFixed(2)}/s

min=${(metrics.iteration_duration.values.min / 1000).toFixed(2)}s med=${(metrics.iteration_duration.values.med / 1000).toFixed(2)}s
${(metrics.iterations.rate).toFixed(2)}/s
min=${options.stages[0].target}
min=${options.stages[0].target}

max=${(metrics.iteration_duration.values.max / 1000).toFixed(2)}s

avg=${(metrics.iteration_duration.values.avg / 1000).toFixed(2)}s
${metrics.iterations.count}
${options.stages[0].target}
${options.stages[0].target}

p(90)=${(metrics.iteration_duration.values.p90 / 1000).toFixed(2)}s

p(95)=${(metrics.iteration_duration.values.p95 / 1000).toFixed(2)}s

max=${options.stages[0].target}
max=${options.stages[0].target}

${(metrics.data_received.count / 1024).toFixed(0)} kB ${(metrics.data_received.rate / 1024).toFixed(0)} kB/s
${(metrics.data_sent.count / 1024).toFixed(0)} kB ${(metrics.data_sent.rate / 1024).toFixed(0)} kB/s
`
  };
}
