<?php
declare(strict_types=1);

function momoRequireConfig(): array
{
    $config = momoConfig();
    foreach (['partner_code', 'access_key', 'secret_key', 'redirect_url', 'ipn_url'] as $key) {
        if ($config[$key] === '') {
            throw new RuntimeException('MoMo chưa được cấu hình biến môi trường: MOMO_' . strtoupper($key) . '.');
        }
    }
    return $config;
}

function momoCreatePayment(PDO $pdo, int $orderId): string
{
    $config = momoRequireConfig();
    $statement = $pdo->prepare('SELECT order_code, total_amount FROM orders WHERE id = :id AND status = \'pending\' LIMIT 1');
    $statement->execute(['id' => $orderId]);
    $order = $statement->fetch();
    if (!$order) {
        throw new RuntimeException('Đơn hàng không còn ở trạng thái chờ thanh toán.');
    }

    $requestId = $config['partner_code'] . '-' . $order['order_code'] . '-' . bin2hex(random_bytes(4));
    $payload = [
        'partnerCode' => $config['partner_code'],
        'requestType' => 'captureWallet',
        'ipnUrl' => $config['ipn_url'],
        'redirectUrl' => $config['redirect_url'],
        'orderId' => $order['order_code'],
        'amount' => (string) $order['total_amount'],
        'orderInfo' => 'Thanh toan ve CineNeon ' . $order['order_code'],
        'requestId' => $requestId,
        'extraData' => '',
        'lang' => 'vi',
    ];
    $rawSignature = 'accessKey=' . $config['access_key'] . '&amount=' . $payload['amount']
        . '&extraData=' . $payload['extraData'] . '&ipnUrl=' . $payload['ipnUrl']
        . '&orderId=' . $payload['orderId'] . '&orderInfo=' . $payload['orderInfo']
        . '&partnerCode=' . $payload['partnerCode'] . '&redirectUrl=' . $payload['redirectUrl']
        . '&requestId=' . $payload['requestId'] . '&requestType=' . $payload['requestType'];
    $payload['signature'] = hash_hmac('sha256', $rawSignature, $config['secret_key']);

    $response = momoPostJson($config['endpoint'], $payload);
    $pdo->prepare('UPDATE payments SET transaction_code = :request_id, response_payload = :payload WHERE order_id = :order_id AND provider = \'momo\'')
        ->execute(['request_id' => $requestId, 'payload' => json_encode($response, JSON_UNESCAPED_UNICODE), 'order_id' => $orderId]);
    if ((int) ($response['resultCode'] ?? -1) !== 0 || empty($response['payUrl'])) {
        throw new RuntimeException('MoMo từ chối tạo giao dịch: ' . (string) ($response['message'] ?? 'Lỗi không xác định.'));
    }
    return (string) $response['payUrl'];
}

function momoHandleIpn(PDO $pdo, string $body): void
{
    $config = momoRequireConfig();
    $data = json_decode($body, true);
    if (!is_array($data)) {
        throw new RuntimeException('IPN MoMo không hợp lệ.');
    }
    $rawSignature = 'accessKey=' . $config['access_key'] . '&amount=' . ($data['amount'] ?? '')
        . '&extraData=' . ($data['extraData'] ?? '') . '&message=' . ($data['message'] ?? '')
        . '&orderId=' . ($data['orderId'] ?? '') . '&orderInfo=' . ($data['orderInfo'] ?? '')
        . '&orderType=' . ($data['orderType'] ?? '') . '&partnerCode=' . ($data['partnerCode'] ?? '')
        . '&payType=' . ($data['payType'] ?? '') . '&requestId=' . ($data['requestId'] ?? '')
        . '&responseTime=' . ($data['responseTime'] ?? '') . '&resultCode=' . ($data['resultCode'] ?? '')
        . '&transId=' . ($data['transId'] ?? '');
    if (!hash_equals(hash_hmac('sha256', $rawSignature, $config['secret_key']), (string) ($data['signature'] ?? ''))) {
        throw new RuntimeException('Chữ ký IPN MoMo không hợp lệ.');
    }
    $orderId = dbOrderIdByCode($pdo, (string) ($data['orderId'] ?? ''));
    if (!$orderId) {
        throw new RuntimeException('Không tìm thấy đơn hàng MoMo.');
    }
    $order = $pdo->prepare('SELECT total_amount, status FROM orders WHERE id = :id LIMIT 1');
    $order->execute(['id' => $orderId]);
    $current = $order->fetch();
    if (!$current || (int) $current['total_amount'] !== (int) ($data['amount'] ?? 0)) {
        throw new RuntimeException('Số tiền thanh toán không khớp.');
    }
    $success = (int) ($data['resultCode'] ?? -1) === 0;
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE orders SET status = :status, paid_at = :paid_at WHERE id = :id AND status = \'pending\'')
            ->execute(['status' => $success ? 'paid' : 'cancelled', 'paid_at' => $success ? date('Y-m-d H:i:s') : null, 'id' => $orderId]);
        $pdo->prepare('UPDATE payments SET transaction_code = :transaction_code, status = :status, response_payload = :payload WHERE order_id = :order_id AND provider = \'momo\'')
            ->execute(['transaction_code' => (string) ($data['transId'] ?? ''), 'status' => $success ? 'success' : 'failed', 'payload' => json_encode($data, JSON_UNESCAPED_UNICODE), 'order_id' => $orderId]);
        $pdo->commit();
    } catch (Throwable $exception) {
        $pdo->rollBack();
        throw $exception;
    }
}

function momoHandleReturn(PDO $pdo, array $data): int
{
    $config = momoRequireConfig();
    $orderId = dbOrderIdByCode($pdo, (string) ($data['orderId'] ?? ''));
    if (!$orderId) {
        throw new RuntimeException('Không tìm thấy đơn hàng MoMo.');
    }
    if ((int) ($data['resultCode'] ?? -1) !== 0) {
        throw new RuntimeException('Thanh toán MoMo chưa thành công: ' . (string) ($data['message'] ?? 'Giao dịch bị từ chối.'));
    }
    $statement = $pdo->prepare('SELECT total_amount FROM orders WHERE id = :id AND status = \'pending\' LIMIT 1');
    $statement->execute(['id' => $orderId]);
    $order = $statement->fetch();
    if (!$order || (int) $order['total_amount'] !== (int) ($data['amount'] ?? 0)) {
        throw new RuntimeException('Thông tin callback MoMo không khớp đơn hàng.');
    }
    $rawSignature = 'accessKey=' . $config['access_key'] . '&amount=' . ($data['amount'] ?? '')
        . '&extraData=' . ($data['extraData'] ?? '') . '&message=' . ($data['message'] ?? '')
        . '&orderId=' . ($data['orderId'] ?? '') . '&orderInfo=' . ($data['orderInfo'] ?? '')
        . '&orderType=' . ($data['orderType'] ?? '') . '&partnerCode=' . ($data['partnerCode'] ?? '')
        . '&payType=' . ($data['payType'] ?? '') . '&requestId=' . ($data['requestId'] ?? '')
        . '&responseTime=' . ($data['responseTime'] ?? '') . '&resultCode=' . ($data['resultCode'] ?? '')
        . '&transId=' . ($data['transId'] ?? '');
    if (!hash_equals(hash_hmac('sha256', $rawSignature, $config['secret_key']), (string) ($data['signature'] ?? ''))) {
        throw new RuntimeException('Chữ ký callback MoMo không hợp lệ.');
    }
    return $orderId;
}

function momoPostJson(string $url, array $payload): array
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $body = curl_exec($curl);
    $error = curl_error($curl);
    curl_close($curl);
    if ($body === false) {
        throw new RuntimeException('Không thể kết nối MoMo: ' . $error);
    }
    $response = json_decode($body, true);
    if (!is_array($response)) {
        throw new RuntimeException('Phản hồi MoMo không hợp lệ.');
    }
    return $response;
}