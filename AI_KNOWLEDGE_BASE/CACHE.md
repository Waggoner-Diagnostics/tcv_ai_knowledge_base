# Cache

`CACHE_STORE=database` — the `cache` and `cache_locks` tables, in the **same MySQL** as everything else.
Redis and Memcached hosts are configured but no driver uses them.

## What is actually cached

**One thing.** `SecureImageService`:

```php
$cacheKey = "plate_url:{$uniqueTestId}:{$testAnswerId}";
Cache::get($cacheKey)                                   // hit → return the URL
Cache::put($cacheKey, $url, TEST_PLATE_URL_TTL_SECONDS) // 880 s
Cache::forget($cacheKey)                                // "revokeAccess"
```

Two constants govern it:
```php
const TEST_PLATE_URL_TTL_SECONDS      = 880;   // cache lifetime
const TEST_PLATE_URL_VALIDITY_SECONDS = 900;   // S3 pre-signed URL lifetime
```

**The 880 < 900 ordering is deliberate and must be preserved.** If the cache outlived the URL, a cache
hit would return an already-expired URL and the plate would fail to load with no error path.

☠️ `revokeAccess()` only forgets the cache key. The S3 URL already delivered to the browser stays valid
for its full 900 s ([S-11](SECURITY.md#s-11--revokeaccess-does-not-revoke-s3-access)).

## What is *not* cached, but is queried constantly

| Query | Frequency |
|---|---|
| `RestrictedIp::where('ip_address', $ip)->exists()` | **every request** ([MIDDLEWARE.md](MIDDLEWARE.md)) |
| `Credits::getAvailableCredits()` — two aggregate `SUM`s | every balance read, several per flow, **plus one per open SPA tab per minute** since ws-397 ([FRONTEND.md](FRONTEND.md#the-credit-balance-is-polled-not-pushed)) |
| Lookup tables (`countries`, `states`, `compliances`, `privileges`, `organization_types`) | every dropdown call |

These are the obvious caching candidates. Note the store is **the database**, so caching a DB lookup in
the DB is a smaller win than it looks — the gain is index/aggregate avoidance, not I/O avoidance.

## Config caching (different thing)

`entrypoint.sh` runs `config:cache` and `route:cache` at boot. That is compile-time caching, unrelated to
`Cache::`. Its consequences — `env()` returning `null` outside config files, and route changes needing a
restart — are in [CONFIGURATION.md](CONFIGURATION.md).

## In tests

`phpunit.xml` sets `CACHE_STORE=array`, so cache state never leaks between tests.

## If you add caching

1. Key on something that changes when the data does; there is **no cache-invalidation convention** in
   this codebase to follow.
2. Prefer a TTL over manual invalidation — the one existing use does exactly that, and its
   "invalidation" is decorative.
3. Remember the store is MySQL: a hot key is a hot row.
