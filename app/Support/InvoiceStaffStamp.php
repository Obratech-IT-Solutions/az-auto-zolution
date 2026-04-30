<?php

namespace App\Support;

/**
 * Server-side attribution for invoice rows (quotation, appointment, service order, invoicing).
 */
final class InvoiceStaffStamp
{
    /**
     * @return array<string, int>
     */
    public static function attributePairForCreate(): array
    {
        $id = auth()->id();
        if ($id === null) {
            return [];
        }

        return [
            'created_by_user_id' => (int) $id,
            'last_processed_by_user_id' => (int) $id,
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function attributePairForUpdate(): array
    {
        $id = auth()->id();
        if ($id === null) {
            return [];
        }

        return [
            'last_processed_by_user_id' => (int) $id,
        ];
    }
}
