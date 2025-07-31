# 📬 Laravel Email Testing with MailHog on Debian Bookworm

This guide walks you through setting up MailHog on a Debian server and configuring Laravel to send development emails through it.

---

## 🧱 Step 1: Install MailHog

Install Go (if not already installed):

```bash
sudo apt update
sudo apt install golang-go
```

Install MailHog using Go:

```bash
go install github.com/mailhog/MailHog@latest
```

Add MailHog to your PATH (if not automatically added):

```bash
export PATH=$PATH:$(go env GOPATH)/bin
```

Run MailHog:

```bash
MailHog
```

You’ll get:
- SMTP Server: `localhost:1025`
- Web UI: [http://localhost:8025](http://localhost:8025)

---

## 🛠 Step 2: Create a systemd Service for MailHog

This allows MailHog to run in the background and start automatically on boot.

Create the service file:

```bash
sudo nano /etc/systemd/system/mailhog.service
```

Paste:

```ini
[Unit]
Description=MailHog Service
After=network.target

[Service]
User=root
ExecStart=/root/go/bin/MailHog
Restart=always

[Install]
WantedBy=multi-user.target
```

Enable and start the service:

```bash
sudo systemctl daemon-reexec
sudo systemctl daemon-reload
sudo systemctl enable mailhog
sudo systemctl start mailhog
```

Check status:

```bash
systemctl status mailhog
```

---

## ✏️ Step 3: Configure Laravel `.env`

Update your `.env` file in the Laravel project root:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

---

## 🧪 Step 4: Test It!

Add this route to your `routes/web.php`:

```php
use Illuminate\Support\Facades\Mail;

Route::get('/test-mail', function () {
    Mail::raw('This is a test email from Laravel using MailHog.', function ($message) {
        $message->to('dev@example.com')
                ->subject('MailHog Test Email');
    });

    return 'Test email sent!';
});
```

Then visit:  
[http://localhost:8000/test-mail](http://localhost:8000/test-mail)  
(or your LAN IP if remote access is enabled)

Check your MailHog web UI at:  
[http://127.0.0.1:8025](http://127.0.0.1:8025)  
(or `http://192.168.3.175:8025` if remote)

---

## ✅ Alternative: Use Log Driver Instead

If you don’t want to install MailHog, you can log emails instead:

```env
MAIL_MAILER=log
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Then check email output in:

```bash
storage/logs/laravel.log
```

---

## 📎 Notes

- MailHog only captures emails sent via SMTP (port 1025).
- No real emails are delivered — safe for dev use!
- You can change the ports MailHog uses with flags like:
  ```bash
  MailHog -smtp-bind-addr "0.0.0.0:1025" -ui-bind-addr "0.0.0.0:8025"
  ```

---