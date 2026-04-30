<?php

namespace App\Support;

use App\Models\Invoice;

/**
 * Cash vs cashless for admin reporting. Pass the amount billed (usually invoice grand_total);
 * falls back to line+job sum if grand_total is zero.
 * Uses payment_cash_amount / payment_non_cash_amount when valid; else payment_type.
 */
final class InvoicePaymentAllocation
{
    private const TOL = 0.06;

    /**
     * @return array{cash: float, cashless: float}
     */
    public static function cashAndCashlessForInvoice(Invoice $inv, float $saleBase): array
    {
        $saleBase = round($saleBase, 2);
        if ($saleBase <= 0) {
            return ['cash' => 0.0, 'cashless' => 0.0];
        }

        $gt = round((float) ($inv->grand_total ?? 0), 2);
        $pc = round((float) ($inv->payment_cash_amount ?? 0), 2);
        $pn = round((float) ($inv->payment_non_cash_amount ?? 0), 2);
        $ch = round((float) ($inv->cash_change_amount ?? 0), 2);
        $type = (string) ($inv->payment_type ?? '');

        $denom = $pc + $pn;

        $matchesGrandTotal = $gt > 0 && abs($denom - $gt) <= self::TOL;
        $splitTenderWithChange = $pc > 0.005 && $pn > 0.005 && abs($denom - $gt - $ch) <= self::TOL;

        $useProportional = $denom > 0.005 && (
            $matchesGrandTotal
            || $splitTenderWithChange
            || ($type === 'split')
        );

        if ($useProportional) {
            $cash = round($saleBase * ($pc / $denom), 2);
            $cashless = round($saleBase - $cash, 2);

            return ['cash' => max(0.0, $cash), 'cashless' => max(0.0, $cashless)];
        }

        if ($type === 'cash') {
            return ['cash' => $saleBase, 'cashless' => 0.0];
        }

        return ['cash' => 0.0, 'cashless' => $saleBase];
    }

    public static function paymentBreakdownLabel(Invoice $inv): string
    {
        $type = $inv->payment_type ?? '';
        $pc = (float) ($inv->payment_cash_amount ?? 0);
        $pn = (float) ($inv->payment_non_cash_amount ?? 0);

        if ($type === 'split' || ($pc > 0.005 && $pn > 0.005)) {
            return 'Split (Cash ₱'.number_format($pc, 2).' + Non-cash ₱'.number_format($pn, 2).')';
        }

        if ($type === 'cash') {
            return 'Cash';
        }

        $label = $type !== '' ? str_replace('_', ' ', (string) $type) : 'non-cash';

        return (string) mb_convert_case($label, MB_CASE_TITLE, 'UTF-8');
    }
}
