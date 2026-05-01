<?php
/**
 * debug-wfp.php — тимчасова сторінка для перевірки підпису WayForPay
 * Поклади в корінь /basketball/debug-wfp.php
 * Після налагодження — ВИДАЛИ цей файл!
 */
require_once 'config.php';
require_once 'includes/wayforpay.php';

$merchantAccount = WFP_MERCHANT_ACCOUNT;
$merchantSecret  = WFP_MERCHANT_SECRET;
$merchantDomain  = WFP_MERCHANT_DOMAIN;

$orderReference = 'TEST_' . time();
$orderDate      = time();
$amount         = '1000.00';
$currency       = 'UAH';
$productName    = 'Basketball Course';
$productPrice   = '1000.00';
$productCount   = 1;

// Рядок для підпису
$signString = implode(';', [
    $merchantAccount,
    $merchantDomain,
    $orderReference,
    $orderDate,
    $amount,
    $currency,
    $productName,
    $productCount,
    $productPrice,
]);

$signature = hash_hmac('md5', $signString, $merchantSecret);
$returnUrl = BASE_URL . '/student/payment-result.php?course_id=1';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>WayForPay Debug</title>
    <style>
        body { font-family: monospace; padding: 30px; background: #f5f5f5; }
        .box { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .label { color: #666; font-size: 12px; margin-bottom: 4px; }
        .value { color: #333; font-size: 14px; word-break: break-all; margin-bottom: 12px; padding: 8px; background: #f8f8f8; border-radius: 4px; }
        .sign-string { background: #fff3cd; padding: 12px; border-radius: 4px; word-break: break-all; margin-bottom: 16px; }
        h2 { color: #333; margin-bottom: 16px; }
        .btn { padding: 12px 24px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; margin-right: 10px; }
        input[type=hidden] { display: none; }
    </style>
</head>
<body>

<div class="box">
    <h2>🔍 WayForPay Debug</h2>

    <div class="label">merchantAccount</div>
    <div class="value"><?= htmlspecialchars($merchantAccount) ?></div>

    <div class="label">merchantDomain</div>
    <div class="value"><?= htmlspecialchars($merchantDomain) ?></div>

    <div class="label">orderReference</div>
    <div class="value"><?= htmlspecialchars($orderReference) ?></div>

    <div class="label">orderDate</div>
    <div class="value"><?= $orderDate ?></div>

    <div class="label">amount</div>
    <div class="value"><?= $amount ?></div>

    <div class="label">Рядок для підпису (через ;)</div>
    <div class="sign-string"><?= htmlspecialchars($signString) ?></div>

    <div class="label">Підпис HMAC-MD5</div>
    <div class="value"><?= $signature ?></div>

    <div class="label">BASE_URL</div>
    <div class="value"><?= BASE_URL ?></div>

    <div class="label">returnUrl</div>
    <div class="value"><?= $returnUrl ?></div>
</div>

<div class="box">
    <h2>▶ Тестова форма оплати (натисни і перевір чи відкривається WayForPay)</h2>
    <p style="color:#666;margin-bottom:16px;">Якщо WayForPay відкриється — підпис правильний. Якщо одразу redirect — підпис неправильний.</p>

    <form method="POST" action="https://secure.wayforpay.com/pay" accept-charset="utf-8">
        <input type="hidden" name="merchantAccount"    value="<?= htmlspecialchars($merchantAccount) ?>">
        <input type="hidden" name="merchantDomainName" value="<?= htmlspecialchars($merchantDomain) ?>">
        <input type="hidden" name="merchantSignature"  value="<?= htmlspecialchars($signature) ?>">
        <input type="hidden" name="orderReference"     value="<?= htmlspecialchars($orderReference) ?>">
        <input type="hidden" name="orderDate"          value="<?= $orderDate ?>">
        <input type="hidden" name="amount"             value="<?= $amount ?>">
        <input type="hidden" name="currency"           value="UAH">
        <input type="hidden" name="orderLifetime"      value="86400">
        <input type="hidden" name="productName[]"      value="<?= htmlspecialchars($productName) ?>">
        <input type="hidden" name="productPrice[]"     value="<?= $productPrice ?>">
        <input type="hidden" name="productCount[]"     value="<?= $productCount ?>">
        <input type="hidden" name="language"           value="UA">
        <input type="hidden" name="returnUrl"          value="<?= htmlspecialchars($returnUrl) ?>">

        <button type="submit" class="btn">💳 Відкрити WayForPay</button>
    </form>
</div>

<div class="box">
    <h2>⚙️ PHP Info</h2>
    <div class="label">PHP версія</div>
    <div class="value"><?= PHP_VERSION ?></div>

    <div class="label">Default charset</div>
    <div class="value"><?= ini_get('default_charset') ?></div>
</div>

</body>
</html>