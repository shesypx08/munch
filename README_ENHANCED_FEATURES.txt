MUNCH ENHANCED SYSTEM NOTES
===========================

Added features in this version:

1. Customer email validation
   - Customer sign up now has Email Address.
   - PHP checks the email using filter_var().
   - Invalid email will notify user and stop registration.

2. Email verification flow
   - After sign up, the system generates a verification token.
   - User must verify email before login.
   - On localhost/XAMPP, email is saved in:
       munch/emails_outbox/
   - Open the latest HTML email file and click Verify Email.
   - If you configure PHP mail(), set MUNCH_USE_PHP_MAIL to true in mail_config.php.

3. Payment gateway demo
   - Customer order checkout redirects to:
       payment-gateway.php?type=order&ref=ORDERID
   - Reservation/event booking redirects to:
       payment-gateway.php?type=reservation&ref=RESERVEID
   - Supports different UI fields for Card Payment, Online Banking, E-Wallet, and QR Payment.
   - This is a prototype/demo gateway. No real money is charged.

4. Payment approval notification
   - After approving payment, system generates transaction number.
   - Payment status becomes Approved.
   - Order status becomes Paid.
   - Reservation status becomes Confirmed.
   - Email notification is saved/sent using email_helper.php.

5. Receipt page and PDF download
   - After payment, user goes to receipt.php.
   - Receipt includes customer details, payment method, transaction number, order/reservation details, item breakdown, tax, delivery charge, and total.
   - User can download PDF using download_receipt_pdf.php.
   - A PDF copy is also saved in emails_outbox/attachments when payment email is generated.

Important localhost note:
Real email sending from XAMPP needs SMTP/sendmail configuration. This version uses a safe local outbox by default so the full flow can be tested without external accounts.

Files added/updated:
- customer-signup.html
- customerSignUp.php
- CustLogin.php
- verify_email.php
- email_verification_notice.php
- email_helper.php
- mail_config.php
- system_init.php
- order.php
- cart.js
- reserve.php
- Reservation.html
- payment-gateway.php
- process_payment.php
- receipt.php
- receipt_helper.php
- download_receipt_pdf.php
- payment-receipt.css
- displayCustomerOrders.php
- customerOrder.html
- database/munchdb.sql

Testing steps:
1. Put the munch folder into htdocs.
2. Start Apache and MySQL in XAMPP.
3. Import database/munchdb.sql into phpMyAdmin OR use your existing munchdb.
   - If using existing DB, the system will auto-add required columns/tables when pages run.
4. Register a new customer with a valid email format.
5. Open munch/emails_outbox/latest-email.html and click Verify Email.
6. Login.
7. Add items to cart and checkout.
8. Complete demo payment.
9. Download receipt PDF.
10. Check munch/emails_outbox/attachments for saved receipt PDF.
