# Deploying Bob to Google Cloud Compute Engine

A traditional VPS deployment on Ubuntu 22.04 with Nginx + PHP-FPM + MySQL.

---

## Prerequisites

- [Google Cloud SDK](https://cloud.google.com/sdk/docs/install) installed and authenticated (`gcloud auth login`)
- A domain name pointed at your VM's external IP
- Your GitHub repository pushed to `main`

---

## Step 1 — Create the VM

```bash
# Create an e2-small VM with Ubuntu 22.04
gcloud compute instances create bob-prod \
  --zone=asia-southeast1-a \
  --machine-type=e2-small \
  --image-family=ubuntu-2204-lts \
  --image-project=ubuntu-os-cloud \
  --boot-disk-size=20GB \
  --tags=http-server,https-server

# Open HTTP and HTTPS firewall ports
gcloud compute firewall-rules create allow-http \
  --allow tcp:80 --target-tags=http-server

gcloud compute firewall-rules create allow-https \
  --allow tcp:443 --target-tags=https-server
```

Get your VM's external IP:

```bash
gcloud compute instances list
```

Point your domain's **A record** to that IP before continuing (SSL setup requires this).

---

## Step 2 — Set up the server (run once)

SSH into the VM:

```bash
gcloud compute ssh bob-prod --zone=asia-southeast1-a
```

Upload and run `setup-server.sh` as root:

```bash
sudo su -
bash <(curl -s https://raw.githubusercontent.com/YOU/bob/main/scripts/setup-server.sh) \
  yourdomain.com your@email.com
```

Or copy the script manually:

```bash
# From your local machine
gcloud compute scp scripts/setup-server.sh bob-prod:~ --zone=asia-southeast1-a

# On the server
sudo bash ~/setup-server.sh yourdomain.com your@email.com
```

This installs: PHP 8.2-FPM, Nginx, MySQL 8.0, Node.js 20, Composer, Supervisor, Certbot (SSL).

**Save the DB password printed at the end** — you'll need it in the next step.

---

## Step 3 — First deployment

Still on the server as root:

```bash
bash /tmp/first-deploy.sh https://github.com/YOU/bob.git
```

The script will pause and ask you to edit `/var/www/bob/.env`. Fill in at minimum:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_KEY=                        # generated automatically after you save
DB_CONNECTION=mysql
DB_DATABASE=bob
DB_USERNAME=bob
DB_PASSWORD=<password from setup-server.sh>
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587
MAIL_USERNAME=your@mailgun.com
MAIL_PASSWORD=your-mailgun-key
MAIL_FROM_ADDRESS=no-reply@yourdomain.com
MAIL_FROM_NAME="bob"
```

Press **Enter** when done — the script finishes migrations, caching, and service restarts automatically.

---

## Step 4 — Configure GitHub Actions for auto-deploy

Every push to `main` will SSH into the server and run `scripts/deploy.sh`.

### Add secrets to GitHub

Go to your repo → **Settings → Secrets and variables → Actions** → **New repository secret**.

| Secret name | Value |
|---|---|
| `SERVER_HOST` | VM external IP address |
| `SERVER_USER` | `root` |
| `SERVER_SSH_KEY` | Your SSH private key (see below) |

### Get your SSH private key

The key gcloud uses is stored locally:

```bash
cat ~/.ssh/google_compute_engine
```

Copy the entire output (including `-----BEGIN` and `-----END` lines) and paste it as `SERVER_SSH_KEY`.

### Verify the workflow

The workflow file is already at `.github/workflows/deploy.yml`. Push any change to `main` and check the **Actions** tab in GitHub to confirm the deploy runs.

---

## Manual deploy (without GitHub Actions)

SSH into the server and run:

```bash
gcloud compute ssh bob-prod --zone=asia-southeast1-a
sudo bash /var/www/bob/scripts/deploy.sh
```

---

## Troubleshooting

**502 Bad Gateway**
```bash
# Check PHP-FPM is running
systemctl status php8.2-fpm

# Check Nginx error log
tail -50 /var/log/nginx/error.log
```

**Queue worker not processing jobs**
```bash
supervisorctl status bob-worker:*
supervisorctl restart bob-worker:*
tail -50 /var/www/bob/storage/logs/worker.log
```

**SSL certificate renewal**
Certbot renews automatically via a cron job. To renew manually:
```bash
certbot renew --nginx
```

**View Laravel logs**
```bash
tail -100 /var/www/bob/storage/logs/laravel.log
```

---

## Scripts reference

| Script | When to run |
|---|---|
| `scripts/setup-server.sh` | Once — fresh VM only |
| `scripts/first-deploy.sh` | Once — after setup-server.sh |
| `scripts/deploy.sh` | Every update (auto via GitHub Actions) |
