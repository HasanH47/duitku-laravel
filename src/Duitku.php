<?php

namespace Duitku\Laravel;

use Duitku\Laravel\Data\BatchResult;
use Duitku\Laravel\Data\CallbackRequest;
use Duitku\Laravel\Data\PaymentFee;
use Duitku\Laravel\Data\PaymentRequest;
use Duitku\Laravel\Data\PaymentResponse;
use Duitku\Laravel\Data\TransactionStatus;
use Duitku\Laravel\Events\DuitkuCallbackReceived;
use Duitku\Laravel\Events\DuitkuPaymentFailed;
use Duitku\Laravel\Events\DuitkuPaymentReceived;
use Duitku\Laravel\Exceptions\CallbackMismatchException;
use Duitku\Laravel\Exceptions\DuitkuApiException;
use Duitku\Laravel\Exceptions\InvalidSignatureException;
use Duitku\Laravel\Http\Client;
use Duitku\Laravel\Services\Pop;
use Duitku\Laravel\Support\DuitkuConfig;
use Duitku\Laravel\Support\PaymentSigner;
use Duitku\Laravel\Support\ResponseValidator;
use Duitku\Laravel\Testing\DuitkuFake;
use Illuminate\Support\Facades\Validator;

class Duitku
{
    private Client $client;

    private PaymentSigner $signer;

    private ?DuitkuFake $fakeProvider = null;

    public function __construct(private DuitkuConfig $config)
    {
        $this->client = new Client($config);
        $this->signer = new PaymentSigner($config);
    }

    public function disbursement(): Disbursement
    {
        return new Disbursement($this->config);
    }

    public function fake(): DuitkuFake
    {
        return $this->fakeProvider ??= (new DuitkuFake($this->config))->register();
    }

    public function pop(): Pop
    {
        return new Pop($this->config);
    }

    /** @return list<PaymentFee> */
    public function paymentMethods(int $amount): array
    {
        Validator::make(['amount' => $amount], ['amount' => ['required', 'integer', 'min:1']])->validate();
        $datetime = now('Asia/Jakarta')->format('Y-m-d H:i:s');
        $signature = $this->signer->sign($this->config->getMerchantCode().$amount.$datetime, 'sha256');

        $response = $this->client->request(retryable: true)
            ->post('/webapi/api/merchant/paymentmethod/getpaymentmethod', [
                'merchantcode' => $this->config->getMerchantCode(),
                'amount' => $amount,
                'datetime' => $datetime,
                'signature' => $signature,
            ]);

        $data = $this->client->json($response);

        if (isset($data['responseCode']) && (string) $data['responseCode'] !== '00') {
            throw new DuitkuApiException('Duitku rejected the payment method request.', (string) $data['responseCode']);
        }

        ResponseValidator::validate($data, ['paymentFee' => ['present', 'array']], 'payment methods');

        return PaymentFee::fromList($data['paymentFee']);
    }

    public function checkout(PaymentRequest $request): PaymentResponse
    {
        $payload = $request->validatedPayload($this->config->getDefaultExpiry());
        $payload['merchantCode'] = $this->config->getMerchantCode();
        $payload['signature'] = $this->signer->sign(
            $payload['merchantCode'].$request->merchantOrderId.$request->amount
        );

        $response = $this->client->request()->post('/webapi/api/merchant/v2/inquiry', $payload);

        return PaymentResponse::fromArray($this->client->json($response))->throwIfFailed();
    }

    public function checkStatus(string $merchantOrderId): TransactionStatus
    {
        $response = $this->client->request(retryable: true)
            ->post('/webapi/api/merchant/transactionStatus', $this->statusPayload($merchantOrderId));

        return $this->decodeStatus($this->client->json($response), $merchantOrderId);
    }

    /**
     * @param  list<string>  $merchantOrderIds
     * @return list<BatchResult>
     */
    public function checkStatuses(array $merchantOrderIds): array
    {
        $requests = [];

        foreach ($merchantOrderIds as $orderId) {
            $requests[] = ['key' => $orderId, 'payload' => $this->statusPayload($orderId)];
        }

        return $this->client->batch(
            '/webapi/api/merchant/transactionStatus',
            $requests,
            fn (array $data, string $orderId) => $this->decodeStatus($data, $orderId),
        );
    }

    public function validateCallback(array $data): bool
    {
        $this->config->assertPaymentCredentials();

        $validator = Validator::make($data, [
            'merchantCode' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'integer', 'min:1'],
            'merchantOrderId' => ['required', 'string', 'max:50'],
            'signature' => ['required', 'string'],
        ]);

        if ($validator->fails() || (! is_string($data['amount']) && ! is_int($data['amount']))) {
            return false;
        }

        if ($data['merchantCode'] !== $this->config->getMerchantCode()) {
            return false;
        }

        $expected = $this->signer->sign($data['merchantCode'].$data['amount'].$data['merchantOrderId']);

        return hash_equals($expected, $data['signature']);
    }

    public function verifyCallback(array $data): CallbackRequest
    {
        if (! $this->validateCallback($data)) {
            throw new InvalidSignatureException('Duitku callback signature validation failed.');
        }

        Validator::make($data, [
            'resultCode' => ['required', 'string', 'in:00,01,02'],
            'reference' => ['required', 'string', 'max:255'],
        ])->validate();

        $callback = CallbackRequest::fromArray($data);

        if ($this->config->shouldVerifyCallbackStatus()) {
            $status = $this->checkStatus($callback->merchantOrderId);

            if ($status->reference !== $callback->reference || (int) $status->amount !== $callback->amount) {
                throw new CallbackMismatchException('Duitku callback does not match the transaction amount or reference.');
            }

            $callback->verifiedStatus = $status;
        }

        return $callback;
    }

    public function handleCallback(array $data): CallbackRequest
    {
        $callback = $this->verifyCallback($data);
        $statusCode = $callback->verifiedStatus !== null
            ? $callback->verifiedStatus->statusCode
            : $callback->resultCode;

        event(new DuitkuCallbackReceived($data));

        if ($statusCode === '00') {
            event(new DuitkuPaymentReceived($callback));
        } elseif ($statusCode === '02') {
            event(new DuitkuPaymentFailed($callback));
        }

        return $callback;
    }

    private function statusPayload(string $merchantOrderId): array
    {
        Validator::make(['orderId' => $merchantOrderId], [
            'orderId' => ['required', 'string', 'max:50'],
        ])->validate();

        return [
            'merchantCode' => $this->config->getMerchantCode(),
            'merchantOrderId' => $merchantOrderId,
            'signature' => $this->signer->sign($this->config->getMerchantCode().$merchantOrderId),
        ];
    }

    private function decodeStatus(array $data, string $orderId): TransactionStatus
    {
        $status = TransactionStatus::fromArray($data);

        if ($status->merchantOrderId !== $orderId) {
            throw new CallbackMismatchException('Duitku returned a status for a different order.');
        }

        return $status;
    }
}
