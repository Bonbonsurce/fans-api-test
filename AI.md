# AI.md

## Tools

- Cursor — search public OnlyFans profiles that fit the light-account threshold (likes under 100k) for seed/workload; refactor tests
- Laravel docs: Horizon, Redis queues, `ShouldBeUnique`, scheduling
- OnlyFansAPI PHP SDK docs for profile retrieve

Light accounts used after that search: `ladydisdainvip`, `lexnorth`, `littlikimmy`, `kristinemartinsen`, `marysoph` (plus heavy `madison420ivy` / `amouranth` from the brief / known large accounts).

## Checked

Automated:

- old vs nested likes fixtures and parser behaviour
- 429 / 500 / timeout / connection / permanent failures
- revision protection, attempt logging, no secrets in logs
- 24h / 72h schedule and unique dispatch
- PHPUnit suite (44 tests at last full run)

Local runtime:

- Horizon + Redis using `APP_URL` from `.env`
- stubbed workload (`ONLYFANS_WORKLOAD=true`) — scripted 429→200, before/after metrics
- live workload (`ONLYFANS_WORKLOAD=false`) against real OnlyFansAPI — report `storage/app/workload-reports/20260927-095430.json`
- live curl of `madison420ivy`: body wrapped in `data` / `_meta`; no `likes` / `profile.likes` / `revision` on that payload; `favoritedCount` present instead → explains `invalid_payload` on HTTP 200 when the parser expects likes

## Incorrect suggestion caught

Treating a finished queue job as proof of a successful refresh. A job can complete after recording a failure; source of truth is stored likes / revision and `last_successful_refresh_at`.

## Not verified

- sustained multi-hour live traffic / production-scale rate limits
- Redis restore or broker failover
- Scout search end-to-end (package present; search not required by the brief)
- multi-node Horizon
- using `favoritedCount` (or unwrapping `data`) as a likes fallback in code
