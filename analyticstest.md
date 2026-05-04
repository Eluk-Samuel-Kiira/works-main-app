# Analytics Testing Guide

Step-by-step manual testing for all analytics features. This file grows as features are added.

---

## Prerequisites

- Docker Desktop running
- Project `.env` configured (DB, REDIS, APP_KEY)
- At least one user per role: `admin`/`super_admin`, `moderator`, `employer`, `job_seeker`

---

## 1. Start the Environment

```bash
# Start all containers
docker compose -f compose.dev.yaml up -d

# Verify containers are running
docker compose -f compose.dev.yaml ps

# Run migrations if needed
docker compose -f compose.dev.yaml exec workspace php artisan migrate

# Seed data (creates users, jobs, companies, transactions)
docker compose -f compose.dev.yaml exec workspace php artisan db:seed

# Check Laravel is healthy
docker compose -f compose.dev.yaml exec workspace php artisan route:list --name=analytics
```

Expected: The route list shows all `analytics.*` named routes.

---

## 2. Verify Middleware Registration

```bash
docker compose -f compose.dev.yaml exec workspace php artisan route:list --name=analytics --columns=name,uri,middleware
```

Expected output includes:
- `analytics.dashboard` → middleware: `auth, analytics.admin`
- `analytics.revenue` → middleware: `auth, analytics.admin, analytics.revenue`
- `analytics.employer.dashboard` → middleware: `auth, analytics.employer`

---

## 3. Role-Based Access Control

Test access restrictions by logging in as different roles.

### 3.1 Admin / Super Admin

Login as admin at `http://localhost/login`.

| URL | Expected Result |
|-----|-----------------|
| `/analytics` | ✅ Dashboard loads |
| `/analytics/jobs` | ✅ Loads |
| `/analytics/users` | ✅ Loads |
| `/analytics/companies` | ✅ Loads |
| `/analytics/seo` | ✅ Loads |
| `/analytics/notifications` | ✅ Loads |
| `/analytics/revenue` | ✅ Loads (admin only) |
| `/analytics/api-usage` | ✅ Loads (admin only) |
| `/analytics/employer` | ✅ Loads |

### 3.2 Moderator

Login as moderator.

| URL | Expected Result |
|-----|-----------------|
| `/analytics` | ✅ Dashboard loads |
| `/analytics/jobs` | ✅ Loads |
| `/analytics/revenue` | ❌ 403 Forbidden |
| `/analytics/api-usage` | ❌ 403 Forbidden |
| `/analytics/employer` | ✅ Loads |

### 3.3 Employer

Login as employer.

| URL | Expected Result |
|-----|-----------------|
| `/analytics` | ❌ 403 Forbidden |
| `/analytics/jobs` | ❌ 403 Forbidden |
| `/analytics/employer` | ✅ "My Analytics" loads |
| `/analytics/revenue` | ❌ 403 Forbidden |

### 3.4 Job Seeker

Login as job_seeker.

| URL | Expected Result |
|-----|-----------------|
| `/analytics` | ❌ 403 Forbidden |
| `/analytics/employer` | ❌ 403 Forbidden |

---

## 4. Sidebar Navigation

Login as admin. Open the sidebar.

- [ ] "Analytics" section is visible under Dashboards
- [ ] Clicking "Analytics" → Overview link goes to `/analytics`
- [ ] Collapsible sub-menu "Analytics" expands to show: Overview, Jobs, Users, Companies, SEO, Notifications, Revenue, API Usage
- [ ] Revenue and API Usage sub-links are visible to admin but hidden to moderator
- [ ] "My Analytics" link visible in sidebar when logged in as employer

Login as employer. Verify sidebar shows "My Analytics" but NOT the admin analytics sub-menu.

---

## 5. Overview Dashboard (`/analytics`)

Login as admin, navigate to `/analytics`.

**KPI tiles check:**
- [ ] Total Users, New Today, New This Week, New This Month all show numbers
- [ ] Total Jobs, Active, Expired, Featured all show numbers
- [ ] Total Companies, Verified, Active all show numbers
- [ ] Revenue card: Total Revenue, This Month, Today show dollar amounts (admin only)
- [ ] Blog stats: Total Blogs, Published, Drafts show numbers
- [ ] Notification summary: Total, Unread, Urgent show numbers

**Charts check:**
- [ ] "Platform Trends (Last 30 Days)" area chart renders with two lines: Jobs Posted + New Users
- [ ] "User Roles Distribution" donut chart renders
- [ ] "Job Status Distribution" donut chart renders
- [ ] "Engagement Overview" radial bar chart renders

**Recent Activity section:**
- [ ] "Recent Jobs" table shows up to 5 recent job posts with company, status, date
- [ ] "Recent Users" table shows up to 5 recent users with role and status
- [ ] "Recent Transactions" table shows up to 5 transactions with amount, gateway, status

Login as moderator → revenue KPI card should be hidden (not show dollar amounts).

---

## 6. Jobs Analytics (`/analytics/jobs`)

Navigate to `/analytics/jobs`.

**KPI tiles (9 total):**
- [ ] Total Jobs, Active, Expired, Featured, Urgent, Verified all show counts
- [ ] In Period (jobs posted in selected date range)
- [ ] Total Views and Total Applications show aggregate counts
- [ ] Avg SEO Score shows a percentage

**Charts (verify each renders without console errors):**
- [ ] Jobs Posted Over Time — area chart with date X-axis
- [ ] Job Status — donut chart
- [ ] Jobs by Category (Top 10) — horizontal bar chart
- [ ] Jobs by Location (Top 10) — horizontal bar chart
- [ ] Jobs by Industry — bar chart
- [ ] Jobs by Type — bar chart
- [ ] Jobs by Experience Level — bar chart
- [ ] SEO Score Distribution — bar chart (0–25, 26–50, 51–75, 76–100)
- [ ] Featured vs Regular comparison — bar chart (avg views, clicks, applications)

**Tables:**
- [ ] "Jobs Approaching Deadline (Next 14 Days)" table shows deadline date, days remaining, application count
- [ ] "Top Performing Jobs" table shows title, views, clicks, applications, SEO bar, status, posted date
- [ ] Export CSV button at top right → clicking downloads a `.csv` file

---

## 7. User Analytics (`/analytics/users`)

Navigate to `/analytics/users`.

**KPI tiles (8 total):**
- [ ] Total Users, New Today, New This Week, Active, Inactive, Verified, Unverified, Never Logged In

**Charts:**
- [ ] Registration Trend — area chart
- [ ] User Roles — pie chart (admin, employer, job_seeker, etc.)
- [ ] Roles Over Time — line chart
- [ ] Login Activity (Last 30 Days) — bar chart
- [ ] Magic Link Usage — bar chart (Sent vs Used per day)
- [ ] Top Countries — horizontal bar chart
- [ ] Account Status — donut (Active vs Inactive)
- [ ] Email Verification — donut

**Tables:**
- [ ] "Recently Active Users" table
- [ ] "Churned Users" table (registered >30 days, never logged in)
- [ ] Export CSV button downloads a `.csv`

---

## 8. Company Analytics (`/analytics/companies`)

Navigate to `/analytics/companies`.

**KPI tiles (7 total):**
- [ ] Total, Verified, Unverified, Active, Inactive, With Jobs, Without Jobs

**Charts:**
- [ ] Company Registrations Over Time — area chart
- [ ] Verification Status — donut
- [ ] By Industry (Top 10) — horizontal bar
- [ ] By Location (Top 10) — horizontal bar
- [ ] Jobs Per Company Distribution — bar chart (0, 1–5, 6–20, 21–50, 50+)
- [ ] Verified Companies Over Time — line chart

**Tables:**
- [ ] "Most Active Companies" (by total jobs) table
- [ ] "Companies With No Active Jobs" table
- [ ] Export CSV button works

---

## 9. Revenue Analytics (`/analytics/revenue`) — Admin Only

Navigate to `/analytics/revenue`.

**Access check:**
- [ ] Moderator visiting this URL gets 403

**KPI cards (colored):**
- [ ] Total Revenue (blue), This Month (green), Today, Transaction Count, Successful, Pending, Failed

**Status tiles:**
- [ ] 7 status tiles: Total Txns, Successful, Failed, Pending, Processing, Refunded, Disputed

**Charts:**
- [ ] Revenue Trend — area chart
- [ ] Transaction Status — donut
- [ ] Monthly Revenue (Last 12 Months) — bar chart
- [ ] Revenue by Gateway — pie chart
- [ ] Revenue by Plan Type — donut
- [ ] Revenue by Payment Method — bar chart

**Tables:**
- [ ] "Top Plans by Revenue" table
- [ ] "Failure Reasons" table
- [ ] "Flagged Transactions" table
- [ ] Export CSV button works

---

## 10. SEO Analytics (`/analytics/seo`)

Navigate to `/analytics/seo`.

**KPI tiles — Jobs section (9):**
- [ ] Total Jobs, Indexed, Submitted, Not Submitted, Approved, Pending, Rejected, Avg SEO Score, High Quality Jobs

**KPI tiles — Blogs section (7):**
- [ ] Total Blogs, Published, Drafts, With Meta, With Featured Image, Total Views, Avg Blog SEO

**Charts:**
- [ ] Indexing Status — donut
- [ ] SEO Score Distribution — bar
- [ ] Quality Score Distribution — bar
- [ ] Jobs Indexed Over Time — area chart
- [ ] Impressions + Clicks — dual axis chart
- [ ] Blog SEO Distribution — bar

**Tables:**
- [ ] "Low SEO Score Jobs (< 40)" table
- [ ] "Top SEO Jobs" table
- [ ] "Top Performing Blogs" table
- [ ] Export CSV button works

---

## 11. API Usage Analytics (`/analytics/api-usage`) — Admin Only

Navigate to `/analytics/api-usage`.

**Access check:**
- [ ] Moderator visiting this URL gets 403

**KPI tiles (8):**
- [ ] Total Calls, Today, This Week, Unique Services, Avg Duration, Total Errors, Active Keys, Inactive Keys

**Charts:**
- [ ] API Call Volume — bar chart
- [ ] Calls by Service — donut chart

**Tables:**
- [ ] "Service Performance" table (success rate, avg duration, error count)
- [ ] "Top Endpoints" (most called)
- [ ] "Slowest Endpoints"
- [ ] "Top Errors" table
- [ ] "Response Code Breakdown" table
- [ ] "API Key Status" table

---

## 12. Notifications Analytics (`/analytics/notifications`)

Navigate to `/analytics/notifications`.

**KPI tiles (7):**
- [ ] Total, Unread, Resolved, Urgent Unresolved, High Unresolved, In Period, Avg Resolution (hours)

**Charts:**
- [ ] Notification Volume Over Time — dual area (Created + Resolved)
- [ ] Status Breakdown — donut (unread, read, resolved, archived)
- [ ] Priority Breakdown — donut (low, medium, high, urgent)
- [ ] Resolution Trend — line chart
- [ ] Audit Actions — horizontal bar chart
- [ ] Audit by Source — pie chart

**Tables:**
- [ ] "Unresolved High-Priority Notifications" table (type, title, priority, status, created)
- [ ] "Most Active Users (Audit)" table

---

## 13. Employer Analytics (`/analytics/employer`)

### As Employer

Login as an employer who has at least one company and job posted.

Navigate to `/analytics/employer`.

**KPI tiles (9):**
- [ ] My Companies, Verified Companies, Total Jobs, Active Jobs, Expired Jobs, Featured Jobs, Total Views, Total Applications, Avg Views/Job

**Charts:**
- [ ] Jobs Posted & Views Over Time — dual area chart
- [ ] Job Status — donut

**Tables:**
- [ ] "My Companies" list with verification badge and active status
- [ ] "Jobs Expiring Soon (Next 14 Days)" table

**Conditional chart:**
- [ ] If employer has 2+ companies: "Company Performance Comparison" bar chart appears
- [ ] If only 1 company: that section is hidden

**Table:**
- [ ] "Top Performing Jobs" table with SEO progress bar
- [ ] Export CSV button downloads employer-scoped data

### As Admin Viewing an Employer

Navigate to `/analytics/employer?employer_id={USER_ID}` (replace with an actual employer user ID).

- [ ] Page shows data scoped to that specific employer (not the admin's own companies)

---

## 14. Date Range Filters

Navigate to `/analytics/jobs`.

**Buttons:**
- [ ] Click "24h" → URL updates to `?range=24h`, KPI "In Period" updates to reflect last 24 hours
- [ ] Click "7d" → URL updates to `?range=7d`
- [ ] Click "30d" → URL updates to `?range=30d` (default active state)
- [ ] Click "All Time" → URL updates to `?range=all`, chart shows full history

**Custom Range:**
- [ ] Click "Custom" or fill in From and To date inputs
- [ ] Click "Apply" → URL updates to `?range=custom&from=YYYY-MM-DD&to=YYYY-MM-DD`
- [ ] Charts and KPIs update to reflect only that date window
- [ ] "Reset" button appears when not on the 30d default → clicking it returns to default

Test the same filters on: `/analytics/users`, `/analytics/companies`, `/analytics/revenue`, `/analytics/employer`.

---

## 15. CSV Export

**Jobs export:**
```
GET /analytics/export/jobs
GET /analytics/export/jobs?range=7d
GET /analytics/export/jobs?range=custom&from=2025-01-01&to=2025-03-31
```
- [ ] Response has `Content-Type: text/csv`
- [ ] Response has `Content-Disposition: attachment; filename="jobs-analytics-*.csv"`
- [ ] File opens correctly in Excel / Google Sheets
- [ ] Columns match expected fields (job title, company, views, clicks, applications, SEO score, status, posted date)
- [ ] Date range filter is applied to the export data

**Other exports:**
- [ ] `/analytics/export/users` — downloads users CSV
- [ ] `/analytics/export/companies` — downloads companies CSV
- [ ] `/analytics/export/seo` — downloads SEO CSV
- [ ] `/analytics/export/revenue` — admin only; downloads transactions CSV
- [ ] `/analytics/employer/export` — employer-scoped export

---

## 16. Dark Mode

Toggle dark mode (if available in the UI header).

- [ ] All ApexCharts switch to dark grid and axis label colors
- [ ] No charts break or disappear when switching themes

---

## 17. Empty State Handling

To test with no data, either use a fresh database or filter to a date range with no activity.

Navigate to `/analytics/jobs?range=custom&from=2000-01-01&to=2000-01-02`.

- [ ] Charts render empty (no errors, no blank white blocks)
- [ ] KPI tiles show `0`
- [ ] Tables show "No data" / "No jobs found" messages instead of broken rows

---

## 18. Smoke Test via Docker (Quick)

Run all of the following curl commands to verify HTTP responses. Requires a valid session cookie or adjust to use basic auth.

```bash
# From host machine — confirm pages load (302 = redirect to login is expected if not authenticated)
curl -s -o /dev/null -w "%{http_code}" http://localhost/analytics
# Expected: 302 (redirected to login when not authenticated) ✅

# From workspace container — run a quick route list check
docker compose -f compose.dev.yaml exec workspace php artisan route:list --name=analytics
```

---

## 19. Common Errors & Fixes

| Symptom | Likely Cause | Fix |
|---------|-------------|-----|
| 500 on `/analytics` | Missing DB table or column | Run `php artisan migrate` |
| Charts show blank | JS error in console | Check browser console; look for `ApexCharts` undefined |
| 403 on all analytics | Middleware not registered | Check `bootstrap/app.php` has `analytics.admin` alias |
| Export returns HTML | Route returns view instead of stream | Check `JobAnalyticsController::export()` uses `response()->stream()` |
| Date filter has no effect | `getDateRange()` not receiving `range` param | Check form `action` URL includes current query params |
| Employer sees all data | Scope not applied | Check `EmployerAnalyticsController` uses `Company::where('created_by', $user->id)` |

---

## Changelog

| Date | Feature | Notes |
|------|---------|-------|
| 2026-05-03 | Initial analytics suite | Dashboard, Jobs, Users, Companies, Revenue, SEO, API, Notifications, Employer analytics — all with date filters and CSV exports |
