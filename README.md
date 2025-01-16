# PrestaShop Payment Module Installation Guide

This guide provides detailed steps to install and configure the payment module for PrestaShop.

---

## Prerequisites
1. Ensure your PrestaShop installation is version-compatible with this module.
2. A valid API key, secret, or other credentials required by the payment provider.
3. FTP or file manager access to your PrestaShop installation.

---

## Installation Steps

### Option 1: Upload Module via PrestaShop Admin Panel

1. Log in to your PrestaShop Back Office.
2. Navigate to **Modules and Services** -> **Modules Manager**.
3. Click on the **Upload a Module** button.
4. Select the module’s `.zip` file and upload it.
5. Follow the on-screen instructions to complete the installation.

### Option 2: Manual Installation

1. Unzip the module archive on your local machine.
2. Connect to your server using FTP or a file manager.
3. Upload the unzipped folder into the `modules/` directory of your PrestaShop installation.
4. Go to **Modules Manager** in your Back Office and locate the new module.
5. Click **Install** and follow any setup instructions.

---

## Configuration

1. After installation, click on the **Configure** button.
2. Enter the required configuration values:
   - API Key
   - Secret Key
   - Callback URLs
   - Environment settings (test/live)
3. Save the configuration.

---

## Troubleshooting

- Ensure file permissions are correct for the module directory.
- Check for missing dependencies or conflicts with other modules.
- Review PrestaShop logs for error messages if issues arise.

---

## Uninstallation

1. Navigate to **Modules Manager**.
2. Locate the payment module and click **Uninstall**.

---

## License

This module is licensed under AFL-3.0. See LICENSE.md for details.

---

## Support

For any issues or further assistance, please contact our support team at [Support Email].
