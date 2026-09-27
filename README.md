# FansAPI profile refresh

Background refresh of OnlyFans profile stats (likes / revision) with Redis + Horizon. Last valid snapshot is kept; failed or malformed upstream responses do not wipe it.

## Setup

PHP 8.5, Composer, MySQL, Redis, `ext-redis`.

```bash
cp .env.example .env
composer install
php artisan key:generate
```

Minimum `.env`:

```env
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_DATABASE=fans_api_test_task
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_CLIENT=phpredis

ONLYFANSAPI_API_KEY=
ONLY_FANS_API_BASE_URL=https://app.onlyfansapi.com
ONLYFANS_WORKLOAD=false
```

```bash
php artisan migrate
php artisan db:seed
php artisan horizon
php artisan test
```

UI (replace `$APP_URL` with the value from `.env`):

| Page | URL |
|---|---|
| Profile list | `$APP_URL/` |
| Profile card | `$APP_URL/{uuid}` |
| Horizon | `$APP_URL/horizon` |

Horizon is a web UI on the app host. It is not Redis (`:6379`) and may fail on HTTPS if the vhost has no SSL for this app — use the same scheme as `APP_URL`.

## Failure

Upstream changed likes placement: old responses had top-level `likes`, newer ones nested under `profile.likes`. The broken handler only read the top level, defaulted a missing value to `0`, and marked every response as a successful refresh.

Also observed in the incident story:

- HTTP 429 without `Retry-After`
- empty HTTP 500 that still looked like a completed queue job
- one busy account delaying others on a shared worker pool

Evidence:

- broken parser: `tests/Unit/OnlyFansProfileParserTest.php`
- fixed path: `tests/Feature/ProfileRefreshTest.php`
- fixtures: `tests/Fixtures/onlyfans/`

## Fix

- both likes formats accepted; missing likes invalid; explicit `0` valid; negative / non-numeric rejected
- older revision never overwrites a newer accepted one
- `last_attempt_at`, `last_successful_refresh_at`, `last_failure` kept separate
- each try stored in `profile_update_attempts`
- 429 → random delay; 5xx / timeout / connection → backoff; permanent 4xx → no retry
- queues: `profiles` (likes ≤ 100000, every 72h), `profiles-heavy` (likes > 100000, every 24h, one Horizon process)
- `ShouldBeUnique` — no second pending job for the same profile

## Live API shape note

OnlyFansAPI often wraps the profile in `{ "data": {...}, "_meta": {...} }`.

Even inside `data`, `likes` / `profile.likes` / `revision` are **not always present**. A live check of `madison420ivy` returned HTTP 200 with no `likes` field; a related counter on the same payload was `favoritedCount`.

Current parser expects likes at top-level `likes` or `profile.likes` on the payload it receives. Missing likes → `invalid_payload`, stored snapshot unchanged. Mapping/`favoritedCount` (and unwrapping `data`) is a reasonable next step; not wired in yet.

## Timeouts

| Layer | Value |
|---|---|
| Job `$timeout` | 30s |
| Horizon worker timeout | 60s |
| Redis `retry_after` | 90s |

`retry_after` must stay above the worker timeout, or Redis can give the same reserved job to another worker. `ShouldBeUnique` blocks a second *dispatch*, not a second pop after reservation expiry.

## Workload

Command: `php artisan app:profile-workload`

Options:

- `--phase=before|after|both` (default `both`)
- `--timeout=120` — seconds to wait per phase until queues drain

Phases:

- **before** — all jobs on `profiles` (shared pool)
- **after** — light on `profiles`, heavy on `profiles-heavy`

Reports: `storage/app/workload-reports/*.json` → start with the top-level `metrics` block.

`drained: true` means both queues were empty before timeout.

Workload helpers under `Src\Application\Services\Workload\*` are demo scaffolding. Fine for this assignment; for production-grade metrics (per-job latency, upstream timings, percentiles) replace with proper instrumentation.

### 1) Stubbed run (scripted responses)

Predictable 429 → 200 on heavy accounts. No real OnlyFansAPI calls.

```bash
# terminal 1
ONLYFANS_WORKLOAD=true php artisan horizon

# terminal 2
ONLYFANS_WORKLOAD=true php artisan app:profile-workload --phase=both
```

Both processes need the flag. Horizon workers resolve `ScriptedOnlyFansClient`; scripts live in Redis cache.

Example (scripted):

| Phase | successful | attempts | attempts / success | oldest waiting |
|---|---:|---:|---:|---:|
| before | 7 | 9 | 1.29 | ~23s |
| after | 7 | 9 | 1.29 | ~8–25s |

### 2) Live run (real OnlyFansAPI)

Uses `OnlyFansClient` and `ONLYFANSAPI_API_KEY`. Keep `ONLYFANS_WORKLOAD=false` (or unset) on **both** Horizon and the command.

```bash
# terminal 1
php artisan horizon

# terminal 2
php artisan app:profile-workload --phase=both --timeout=180
```

Expect real upstream behaviour: rate limits, empty/odd payloads, missing `likes`. A live local run (`20260927-095430.json`) drained both phases; before had 6/9 successes (one `invalid_payload` on `madison420ivy` — 200 without likes), after recovered to 7/9.

| Phase | successful | attempts | attempts / success | oldest waiting | drained |
|---|---:|---:|---:|---:|---|
| before | 6 | 9 | 1.5 | 27s | yes |
| after | 7 | 9 | 1.29 | 23s | yes |

## Replay after DB write

Worker dies after COMMIT but before Redis ack → job returns after `retry_after`. Revision lock prevents likes from rolling back. Does not prove a mid-transaction crash or exactly-once delivery.

## Scaling (50M jobs / day)

~579 jobs/s average is not capacity. Measure next: peak arrivals, job duration, concurrency, retry amplification, MySQL/Redis pressure, upstream limits, largest accounts. Likely bottleneck here: single heavy worker + upstream 429. First change: raise heavy workers carefully while keeping per-account uniqueness; add rate-limit budget tracking.

## Production — first 15 minutes

1. Horizon up; both queues draining  
2. `profile.update.finished` logs and `last_failure` on busy accounts  
3. successes vs attempts for the last hour  
4. deploy; watch heavy wait time  
5. roll back if oldest waiting age climbs while successes drop  
6. recovery: rate-limited account later gets `succeeded` and a newer `last_successful_refresh_at`

## Time spent

About one focused day: setup, Horizon/Redis locally, stubbed + live workload runs.
