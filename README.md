# 9PSB Payment Gateway Module for PrestaShop

This module integrates the 9PSB payment gateway with PrestaShop, enabling secure and seamless payments for your online store.

## Features
- Secure payment processing with 9PSB
- Supports test and live environments
- Generates payment links and handles redirection
- Logs transaction responses for easy debugging

## Requirements
- PrestaShop 1.7 or higher
- PHP 7.2 or higher
- cURL and OpenSSL enabled

## Installation

### Download the Module
Download the ZIP archive of the module from the repository or release page.

### Upload the Module

1. Go to the PrestaShop admin dashboard.
2. Navigate to **Modules and Services** > **Module Manager**.
3. Click **Upload a Module** and select the ZIP archive.
4. The module will be automatically installed.

### Configure the Module

In the module settings, provide the following details:
- Test Public Key and Test Secret Key
- Live Public Key and Live Secret Key
- Environment Mode (Test or Live)
- Callback URL for payment notifications

### Set Permissions
Ensure the following folders have write permissions:
- `modules/paymentexample/logs/` (for logging errors and responses)

## Usage
1. Once configured, customers can select **9PSB Payment** as a payment method at checkout.
2. After placing an order, they will be redirected to the payment gateway to complete the transaction.

## Troubleshooting
- **Authentication Failure**: Ensure API keys are correct for the environment set (test/live).
- **Failed to Get Payment URL**: Check if the callback URL is correctly set in your 9PSB dashboard.
- **Enable logs**: Check the `modules/paymentexample/logs/` directory for detailed error messages.

## License
This module is licensed under the AFL-3.0.

## Support
For support, contact [itsupport@9psb.com.ng](mailto:itsupport@9psb.com.ng) or refer to the official documentation for PrestaShop module development.

