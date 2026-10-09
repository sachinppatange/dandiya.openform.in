<?php
/**
 * MSG91 SMS OTP configuration
 * Dashboard: https://control.msg91.com/app/m/l/sms/
 *
 * Template name: kagpifotp
 * Template body:
 * Your OTP for account verification is ##var1##. Valid for ##var2## minutes. Do not share it. KAGPIF
 */

if (!defined('MSG91_AUTH_KEY')) {
    define('MSG91_AUTH_KEY', '545551AoXGUtr0EHe6a4f534fP1');
}
if (!defined('MSG91_SENDER_ID')) {
    define('MSG91_SENDER_ID', 'KAGPIF');
}
if (!defined('MSG91_OTP_TEMPLATE_NAME')) {
    define('MSG91_OTP_TEMPLATE_NAME', 'kagpifotp');
}
if (!defined('MSG91_OTP_TEMPLATE_ID')) {
    define('MSG91_OTP_TEMPLATE_ID', '6a53052a8ee89528c2049963');
}
if (!defined('MSG91_OTP_VAR')) {
    define('MSG91_OTP_VAR', 'var1');
}
if (!defined('MSG91_MINUTES_VAR')) {
    define('MSG91_MINUTES_VAR', 'var2');
}
if (!defined('MSG91_FLOW_URL')) {
    define('MSG91_FLOW_URL', 'https://control.msg91.com/api/v5/flow/');
}
if (!defined('SMS_COUNTRY_CODE')) {
    define('SMS_COUNTRY_CODE', defined('WA_COUNTRY_CODE') ? WA_COUNTRY_CODE : '91');
}
