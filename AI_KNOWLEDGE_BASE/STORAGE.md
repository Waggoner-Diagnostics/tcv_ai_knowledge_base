# Storage

`config/filesystems.php` defines three disks — `local`, `public`, `s3` — and
`FILESYSTEM_DISK` defaults to **`local`**.

## S3 — test plate images

The only disk the code names explicitly (`Storage::disk('s3')`), in `SecureImageService`:

```php
Storage::disk('s3')->temporaryUrl(
    $imagePath,
    now()->addSeconds(900),
    ['ResponseContentDisposition' => 'inline', 'ResponseContentType' => 'image/png']
);
```

Plates are **private**; the browser only ever sees a pre-signed URL. Before signing,
`validateTestSession()` checks that the test is `inprogress` **and** the plate is unanswered — that check
is the real access control, not the URL.

Uploads go through the `UploadTestPlates` console command.
`SecureImageService::uploadPlateToS3()` also exists but carries a comment saying it is unused.

Env: `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION`, `AWS_BUCKET`,
`AWS_USE_PATH_STYLE_ENDPOINT`.

☠️ **If S3 credentials are wrong, plates silently return `null`** — the test player shows blank plates
and the only signal is `Failed to generate secure plate URL` in the log.

## Other uploads

| What | Where |
|---|---|
| Organisation logo | `POST api/organizations/{id}/upload-logo` → `OrganizationController::uploadLogo()`; `organizations.logo_uploaded` is the flag |
| Generated PDFs | rendered by dompdf and streamed in the response — **not persisted** |
| Excel exports | streamed by `maatwebsite/excel` — **not persisted** |

nginx allows `client_max_body_size 500M`, which is sized for plate uploads.

## ☠️ The default disk is `local`, and `local` is not persistent

`docker-compose.yml` mounts only **`storage/logs`** and `./public`. Anything written to
`storage/app` inside the container is lost on replacement. Any new file-writing feature must name the
`s3` disk explicitly — do not rely on the default.

## `public/`

Bind-mounted into **both** containers so nginx can serve static assets directly while php-fpm sees the
same tree. Files placed there are world-readable — never write user or patient data into `public/`.

## What is not here

No local file storage of patient data, no report archive, no generated-PDF store, no `storage:link`
usage. Every artefact is either in MySQL, in S3, or generated per request.
