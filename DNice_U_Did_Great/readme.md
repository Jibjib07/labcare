This guide outlines the steps required to configure PHPMailer within the LabCare project for sending password reset emails and other notifications.

## Step 0: Project Directory Structure

Ensure your `labcare-main` project folder is structured as follows:

```text
/labcare-main
├── includes/db.php
├── vendor/             <-- Created in Step 2
├── forgot_password.php  <-- Current file
└── password_resets.php  <-- Reset link destination
```

## Step 1: Install Composer

1.  Download & install Composer from: [https://getcomposer.org/download/](https://getcomposer.org/download/)
2.  Open Terminal or Command Prompt inside the `labcare-main` folder.
3.  Verify installation by running:
    ```bash
    composer --version
    ```

## Step 2: Install PHPMailer

Run the following command in your terminal inside the project folder:

```bash
composer require phpmailer/phpmailer
```
*This creates the `vendor/` folder and the `autoload.php` file.*

## Step 3: Google Account (Gmail) Configuration

Gmail blocks standard login for third-party apps. You must configure an App Password:

1.  **Enable 2-Step Verification:**
    *   Go to: [https://myaccount.google.com/security](https://myaccount.google.com/security)
2.  **Create App Password:**
    *   Navigate to **App Passwords**.
    *   Select App: **Other**.
    *   Name: **LabCare**.
    *   **Copy the 16-character code** generated.

## Step 4: SMTP Configuration

Update your PHPMailer initialization code with the following settings: (in forgot_password.php)

```php
$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'your-email@gmail.com';
$mail->Password   = 'your-app-password'; // Use the 16-char code from Step 3
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587; // TLS (or 465 for SSL)
```

## Step 5: File Paths

*   **BASE_URL:** `http://localhost/labcare-main`
*   **Autoload:** `require 'vendor/autoload.php';`

## Step 6: Troubleshooting

*   **Class not found:** Run `composer install` in the terminal.
*   **SMTP fails:** Check your firewall settings and ensure port 587 is open.
*   **Enable Debugging:** To see detailed error logs, add the following line:
    ```php
    $mail->SMTPDebug = 2;
    ```