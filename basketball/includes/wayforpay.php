<?php
/**
 * includes/wayforpay.php
 * WayForPay інтеграція
 * Документація: https://wiki.wayforpay.com/en/view/852102
 */
class WayForPay {

    const PURCHASE_URL = 'https://secure.wayforpay.com/pay';
    const API_URL      = 'https://api.wayforpay.com/api';

    private string $merchantAccount;
    private string $merchantSecret;
    private string $merchantDomain;

    public function __construct(string $merchantAccount, string $merchantSecret, string $merchantDomain) {
        $this->merchantAccount = $merchantAccount;
        $this->merchantSecret  = $merchantSecret;
        $this->merchantDomain  = $merchantDomain;
    }

    /**
     * Генерує підпис HMAC_MD5
     * Рядок: merchantAccount;merchantDomain;orderReference;orderDate;amount;currency;productName;productCount;productPrice
     */
    public function generateSignature(array $parts): string {
        return hash_hmac('md5', implode(';', $parts), $this->merchantSecret);
    }

    /**
     * Генерує HTML форму для переходу на сторінку оплати
     */
    public function buildForm(array $params): string {
        $orderDate      = (int)$params['orderDate'];
        $orderReference = $params['orderReference'];
        // Сума повинна бути рядком без зайвих нулів
        $amount         = number_format((float)$params['amount'], 2, '.', '');
        $currency       = $params['currency'] ?? 'UAH';
        $productName    = $params['productName'];
        $productPrice   = number_format((float)$params['productPrice'], 2, '.', '');
        $productCount   = (int)($params['productCount'] ?? 1);

        // Для підпису використовуємо ASCII назву (кирилиця може ламати HMAC)
        $productNameForSign = 'Basketball Course';

        // Підпис: merchantAccount;merchantDomainName;orderReference;orderDate;amount;currency;productName;productCount;productPrice
        $signParts = [
            $this->merchantAccount,
            $this->merchantDomain,
            $orderReference,
            $orderDate,
            $amount,
            $currency,
            $productNameForSign,
            $productCount,
            $productPrice,
        ];

        $signature = $this->generateSignature($signParts);

        $returnUrl  = $params['returnUrl']  ?? '';
        $serviceUrl = $params['serviceUrl'] ?? '';

        $html = '<form method="POST" action="' . self::PURCHASE_URL . '" accept-charset="utf-8" id="wfp-form">';
        $fields = [
            'merchantAccount'    => $this->merchantAccount,
            'merchantDomainName' => $this->merchantDomain,
            'merchantSignature'  => $signature,
            'orderReference'     => $orderReference,
            'orderDate'          => $orderDate,
            'amount'             => $amount,
            'currency'           => $currency,
            'orderLifetime'      => 86400,
            'productName[]'      => $productNameForSign,
            'productPrice[]'     => $productPrice,
            'productCount[]'     => $productCount,
            'clientEmail'        => $params['clientEmail'] ?? '',
            'language'           => 'UA',
            'returnUrl'          => $returnUrl,
            'serviceUrl'         => $serviceUrl,
        ];

        foreach ($fields as $name => $value) {
            $html .= '<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars((string)$value) . '">';
        }

        $html .= '<button type="submit" class="btn-wayforpay">
            <img src="https://secure.wayforpay.com/images/wfp-logo.png" style="height:22px;vertical-align:middle;margin-right:8px;" alt="WayForPay">
            Оплатити через WayForPay
        </button></form>';

        return $html;
    }

    /**
     * Перевірка підпису callback від WayForPay
     */
    public function verifyCallback(array $data): bool {
        $signParts = [
            $data['merchantAccount']  ?? '',
            $data['orderReference']   ?? '',
            $data['amount']           ?? '',
            $data['currency']         ?? '',
            $data['authCode']         ?? '',
            $data['cardPan']          ?? '',
            $data['transactionStatus']?? '',
            $data['reasonCode']       ?? '',
        ];
        $expected = $this->generateSignature($signParts);
        return hash_equals($expected, $data['merchantSignature'] ?? '');
    }

    /**
     * Перевірка статусу замовлення через API (для localhost без callback)
     */
    public function checkOrderStatus(string $orderReference): array {
        $date      = time();
        $signParts = [$this->merchantAccount, $orderReference, $date];
        $signature = $this->generateSignature($signParts);

        $payload = json_encode([
            'transactionType'   => 'CHECK_STATUS',
            'merchantAccount'   => $this->merchantAccount,
            'orderReference'    => $orderReference,
            'merchantSignature' => $signature,
            'apiVersion'        => 1,
            'dateBegin'         => $date - 86400,
            'dateEnd'           => $date + 3600,
        ]);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response ? (json_decode($response, true) ?? []) : [];
    }

    /**
     * Генерує унікальний orderReference
     */
    public static function generateOrderReference(int $courseId, int $userId): string {
        return 'BSK' . $courseId . 'U' . $userId . 'T' . time();
    }
}