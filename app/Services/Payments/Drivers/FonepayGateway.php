<?php

namespace App\Services\Payments\Drivers;

use App\Models\Order;
use App\Models\PaymentTransaction;
use App\Services\Payments\Contracts\PaymentGatewayContract;
use App\Services\Payments\DTO\PaymentInitiationResult;
use App\Services\Payments\DTO\PaymentVerificationResult;
use Illuminate\Http\Request;

/**
 * Fonepay — a query-string GET redirect flow, checksummed with HMAC-SHA512.
 *
 * initiate() builds the merchantRequest URL with a request checksum (DV).
 * handleCallback() only parses the response query params into a structural
 * result (see verify() for why nothing here is trusted). verify() is the
 * authoritative step: it recomputes the response DV from the raw callback
 * params (stashed onto the transaction by the caller) and compares it
 * against what Fonepay sent, then checks the payment status flag.
 *
 * NOTE (flagged for review): Fonepay does not publicly document the exact
 * success sentinel for the `PS` response field. We assume PS === 'true'
 * indicates a successful payment, mirroring the most common reference
 * integrations seen in the wild. This assumption needs confirming against
 * a real sandbox response before this gateway goes live.
 */
class FonepayGateway implements PaymentGatewayContract
{
    /**
     * The PS value we treat as "paid". See the class-level note above —
     * this is an assumption, not a documented contract.
     */
    private const SUCCESS_PS_VALUE = 'true';

    public function initiate(Order $order): PaymentInitiationResult
    {
        $pid = (string) config('services.fonepay.merchant_code');
        $secretKey = (string) config('services.fonepay.secret_key');

        $md = 'P';
        $prn = $order->order_number;
        $amt = number_format((float) $order->grand_total, 2, '.', '');
        $crn = 'NPR';
        $dt = now()->format('m/d/Y');
        $r1 = 'Order payment';
        $r2 = $order->order_number;
        $ru = route('payments.callback', ['gateway' => 'fonepay']);

        $dv = $this->sign([$pid, $md, $prn, $amt, $crn, $dt, $r1, $r2, $ru], $secretKey);

        $query = http_build_query([
            'PID' => $pid,
            'MD' => $md,
            'PRN' => $prn,
            'AMT' => $amt,
            'CRN' => $crn,
            'DT' => $dt,
            'R1' => $r1,
            'R2' => $r2,
            'RU' => $ru,
            'DV' => $dv,
        ]);

        $baseUrl = (string) config('services.fonepay.base_url');

        return PaymentInitiationResult::redirect(url: $baseUrl.'?'.$query);
    }

    public function handleCallback(Request $request): PaymentVerificationResult
    {
        $prn = $request->query('PRN');

        if (! is_string($prn) || $prn === '') {
            return new PaymentVerificationResult(success: false, message: 'Missing PRN in Fonepay callback.');
        }

        $raw = [
            'PRN' => $prn,
            'PID' => (string) $request->query('PID'),
            'PS' => (string) $request->query('PS'),
            'RC' => (string) $request->query('RC'),
            'UID' => (string) $request->query('UID'),
            'BC' => (string) $request->query('BC'),
            'INI' => (string) $request->query('INI'),
            'P_AMT' => (string) $request->query('P_AMT'),
            'R_AMT' => (string) $request->query('R_AMT'),
            'DV' => (string) $request->query('DV'),
        ];

        // Structural parse only — never trust this alone. verify() below
        // performs the authoritative checksum recomputation.
        return new PaymentVerificationResult(
            success: false,
            gatewayReference: $raw['UID'] !== '' ? $raw['UID'] : null,
            amount: $raw['P_AMT'] !== '' ? $raw['P_AMT'] : null,
            orderNumber: $prn,
            rawResponse: $raw,
        );
    }

    public function verify(PaymentTransaction $transaction): bool
    {
        /** @var array<string, mixed> $raw */
        $raw = $transaction->raw_response ?? [];

        $prn = (string) ($raw['PRN'] ?? '');
        $pid = (string) ($raw['PID'] ?? '');
        $ps = (string) ($raw['PS'] ?? '');
        $rc = (string) ($raw['RC'] ?? '');
        $uid = (string) ($raw['UID'] ?? '');
        $bc = (string) ($raw['BC'] ?? '');
        $ini = (string) ($raw['INI'] ?? '');
        $pAmt = (string) ($raw['P_AMT'] ?? '');
        $rAmt = (string) ($raw['R_AMT'] ?? '');
        $dv = (string) ($raw['DV'] ?? '');

        if ($prn === '' || $dv === '') {
            return false;
        }

        $expected = $this->sign([$prn, $pid, $ps, $rc, $uid, $bc, $ini, $pAmt, $rAmt], (string) config('services.fonepay.secret_key'));

        $checksumValid = hash_equals(strtolower($expected), strtolower($dv));

        return $checksumValid && strtolower($ps) === self::SUCCESS_PS_VALUE;
    }

    /**
     * @param  array<int, string>  $parts
     */
    private function sign(array $parts, string $secretKey): string
    {
        return hash_hmac('sha512', implode(',', $parts), $secretKey);
    }
}
