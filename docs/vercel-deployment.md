# Deploying to Vercel

This Laravel application is configured for Vercel's Container runtime through
`Dockerfile.vercel`. Vercel detects that file at the repository root and routes
requests to the container automatically.

## Before the first deploy

1. Push this repository to GitHub (the existing `origin` remote is already set).
2. In Vercel, choose **Add New → Project**, import the repository, and deploy it.
3. In **Project Settings → Environment Variables**, add the production values in
   `.env.vercel.example`. Generate `APP_KEY` locally with:

   ```powershell
   php artisan key:generate --show
   ```

4. Use a managed MySQL database accessible from the internet. Apply migrations
   from a trusted machine or CI job, never as part of a web request:

   ```powershell
   php artisan migrate --force
   ```

5. Set `APP_URL` to the Vercel production URL (or custom domain), then redeploy.

## Important runtime constraints

Vercel containers are stateless. The deployment filesystem is not durable, so
do not use local disk for uploaded files, sessions, cache, queues, or databases.
Use external managed services instead. The included defaults use cookie sessions,
in-memory-per-request cache, synchronous queues, stderr logging, and `/tmp` only
for disposable Laravel compiled views and framework caches.

For uploads, configure an object-storage disk (S3/R2/Blob). For queues or jobs
that must run outside a request, use an external queue and worker service.

## Optional CLI deploy

After signing into Vercel, deployment from the repository root is:

```powershell
npx vercel --prod
```
