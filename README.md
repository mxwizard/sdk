# MX Wizard SDK for PHP

## Usage

Example:

```php
<?php

require dirname(__DIR__) . '/vendor/mxwizard/sdk/src/Mailer.php';

use vendor\mxwizard\sdk\src\Mailer as MXWMailer;

/* API Credentials: https://mxwizard.net/dashboard/api */
define('MXW_API_TOKEN_ID', 0);
define('MXW_API_TOKEN', 'test_1');

$mail = new MXWMailer(MXW_API_TOKEN_ID, MXW_API_TOKEN);

$mail
        ->setFrom('test@mxwizard.net', 'MX Wizard Test')
        ->addTo('info@mxwizard.net', 'MX Wizard Team')
        ->addCc('cc-test@mxwizard.net', 'MX Wizard (CC)')
        ->addBcc('bcc-test@mxwizard.net')
        ->setSubject('MX Wizard Mailer Test ' . date('d.m.Y H:i:s'))
        ->setHtml('<p style="color: red;">Test!</p>')
        ->setText('Test!')
        ->addAttachment(__FILE__);

if (false === $mail->send()) {
    echo 'MXWMailer error (', $mail->errorCode, '): ', $mail->error, PHP_EOL;
}

```
