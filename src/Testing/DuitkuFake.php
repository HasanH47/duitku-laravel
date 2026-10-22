<?php

namespace Duitku\Laravel\Testing;

use Closure;
use Duitku\Laravel\Data\PaymentFee;
use Duitku\Laravel\Support\DuitkuConfig;
use Duitku\Laravel\Support\PaymentSigner;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use LogicException;

class DuitkuFake
{
    private const INVOICE = '/webapi/api/merchant/v2/inquiry';

    private const POP_INVOICE = '/api/merchant/createInvoice';

    private const STATUS = '/webapi/api/merchant/transactionStatus';

    private const METHODS = '/webapi/api/merchant/paymentmethod/getpaymentmethod';

    /** @var array<string, array{merchantOrderId: string, amount: string, reference: string, statusCode: string, statusMessage: string}> */
    private array $payments = [];

    private array $methods = [];

    public function __construct(private DuitkuConfig $config) {}

    public function register(): self
    {
        if (! app()->runningUnitTests()) {
            throw new LogicException('Duitku::fake() is only available in the testing environment.');
        }

        Http::fake([
            $this->config->getPassportHost().'/*' => fn (Request $request) => $this->respond($request),
            $this->config->getApiHost().'/*' => fn (Request $request) => $this->respond($request),
            $this->config->getDisbursementHost().'/*' => fn (Request $request) => $this->respond($request),
        ]);

        return $this;
    }

    public function payment(string $orderId, int $amount, string $statusCode = '01', ?string $reference = null): self
    {
        $data = [
            'merchantOrderId' => $orderId,
            'amount' => (string) $amount,
            'reference' => $reference ?? 'TEST-'.$orderId,
            'statusCode' => $statusCode,
            'statusMessage' => 'Synthetic provider status',
        ];
        Validator::make($data, [
            'merchantOrderId' => ['required', 'string', 'max:50'],
            'amount' => ['required', 'integer', 'min:1'],
            'reference' => ['required', 'string', 'max:255'],
            'statusCode' => ['required', 'string', 'in:00,01,02'],
        ])->validate();
        $this->payments[$orderId] = $data;

        return $this;
    }

    public function paymentMethods(array $methods): self
    {
        PaymentFee::fromList($methods);
        $this->methods = $methods;

        return $this;
    }

    public function callback(string $orderId): array
    {
        $payment = $this->findPayment($orderId);
        $merchantCode = $this->config->getMerchantCode();

        return [
            'merchantCode' => $merchantCode,
            'merchantOrderId' => $orderId,
            'amount' => $payment['amount'],
            'reference' => $payment['reference'],
            'resultCode' => $payment['statusCode'] === '00' ? '00' : '01',
            'signature' => (new PaymentSigner($this->config))->sign($merchantCode.$payment['amount'].$orderId),
        ];
    }

    public function assertPaymentRequested(string $orderId, ?Closure $callback = null): void
    {
        Http::assertSent(fn (Request $request) => $request->method() === 'POST' &&
            in_array($request->url(), [
                $this->config->getPassportHost().self::INVOICE,
                $this->config->getApiHost().self::POP_INVOICE,
            ], true) && $request['merchantOrderId'] === $orderId && ($callback === null || $callback($request)));
    }

    public function assertStatusChecked(string $orderId): void
    {
        Http::assertSent(fn (Request $request) => $request->method() === 'POST' &&
            $request->url() === $this->config->getPassportHost().self::STATUS &&
            $request['merchantOrderId'] === $orderId);
    }

    public function assertNothingSent(): void
    {
        Http::assertNotSent(fn (Request $request) => in_array(parse_url($request->url(), PHP_URL_HOST), [
            parse_url($this->config->getPassportHost(), PHP_URL_HOST),
            parse_url($this->config->getApiHost(), PHP_URL_HOST),
            parse_url($this->config->getDisbursementHost(), PHP_URL_HOST),
        ], true));
    }

    private function respond(Request $request): PromiseInterface
    {
        $path = parse_url($request->url(), PHP_URL_PATH);

        if ($request->method() !== 'POST') {
            throw new LogicException('Duitku fake only supports POST requests to payment API endpoints.');
        }

        if ($path === self::METHODS) {
            return Http::response(['responseCode' => '00', 'paymentFee' => $this->methods]);
        }

        if (! in_array($path, [self::INVOICE, self::POP_INVOICE, self::STATUS], true)) {
            throw new LogicException('This Duitku endpoint has no fake response. Use an explicit Http::fake() fixture.');
        }

        $payment = $this->findPayment($request['merchantOrderId'] ?? '');

        if ($path === self::STATUS) {
            return Http::response($payment);
        }

        if ((string) $request['paymentAmount'] !== $payment['amount']) {
            throw new LogicException('The payment amount differs from the registered Duitku fake payment.');
        }

        return Http::response([
            'merchantCode' => $this->config->getMerchantCode(),
            'reference' => $payment['reference'],
            'paymentUrl' => 'https://checkout.example.test/'.rawurlencode($payment['reference']),
            'statusCode' => '00',
            'statusMessage' => 'Synthetic invoice created',
        ]);
    }

    /** @return array{merchantOrderId: string, amount: string, reference: string, statusCode: string, statusMessage: string} */
    private function findPayment(string $orderId): array
    {
        if (! isset($this->payments[$orderId])) {
            throw new LogicException('Register this order with Duitku::fake()->payment() before sending requests.');
        }

        return $this->payments[$orderId];
    }
}
