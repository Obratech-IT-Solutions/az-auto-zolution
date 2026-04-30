<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Invoice extends Model
{
    protected $fillable = [
        'client_id',
        'vehicle_id',
        'customer_name',
        'vehicle_name',
        'source_type',
        'service_status',
        'status',
        'appointment_date',
        'note',
        'subtotal',
        'total_discount',
        'vat_amount',
        'grand_total',
        'payment_cash_amount',
        'payment_non_cash_amount',
        'cash_tender_amount',
        'cash_change_amount',
        'cashless_tender_amount',
        'payment_type',
        'invoice_no',
        'number',
        'address',
        'created_by_user_id',
        'last_processed_by_user_id',
        'created_at',
    ];

    protected $attributes = [
        'source_type' => 'quotation',
        'service_status' => 'pending',
        'status' => 'unpaid',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'total_discount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'payment_cash_amount' => 'decimal:2',
        'payment_non_cash_amount' => 'decimal:2',
        'cash_tender_amount' => 'decimal:2',
        'cash_change_amount' => 'decimal:2',
        'cashless_tender_amount' => 'decimal:2',
        'number' => 'string',
        'address' => 'string',

    ];

    // ✅ Add 'cancelled' here
    public static $sourceTypes = ['quotation', 'cancelled', 'appointment', 'service_order', 'invoicing'];

    public static $serviceStatuses = ['pending', 'in_progress', 'done'];

    public static $statuses = ['unpaid', 'paid', 'cancelled', 'voided'];

    /**
     * Relations
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function jobs()
    {
        return $this->hasMany(InvoiceJob::class);
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function lastProcessedByUser()
    {
        return $this->belongsTo(User::class, 'last_processed_by_user_id');
    }

    /** Payment type label for cashier lists, history, and print (handles split tenders). */
    public function paymentTypeDisplay(): string
    {
        $slug = strtolower(trim((string) ($this->payment_type ?? '')));

        if ($this->usesSplitCashAndCashlessTenders()) {
            if (in_array($slug, ['gcash', 'debit', 'credit', 'non_cash'], true)) {
                return 'Cash/'.$this->cashlessPairLabel($slug);
            }

            return 'Cash/Mixed';
        }

        if ($slug === '') {
            return '—';
        }

        if ($slug === 'split') {
            return 'Split';
        }

        return $this->singlePaymentRailLabel($slug);
    }

    /** Both cash and cashless amounts recorded on the invoice (cash + gcash/debit/etc.). */
    public function usesSplitCashAndCashlessTenders(): bool
    {
        $pc = (float) ($this->payment_cash_amount ?? 0);
        $pn = (float) ($this->payment_non_cash_amount ?? 0);

        return $pc >= 0.005 && $pn >= 0.005;
    }

    /** Second rail after "Cash/" in split summaries. */
    private function cashlessPairLabel(string $slug): string
    {
        return match (strtolower(trim($slug))) {
            'gcash' => 'GCash',
            'debit' => 'Debit',
            'credit' => 'Credit',
            'non_cash' => 'Non cash',
            default => 'Mixed',
        };
    }

    private function singlePaymentRailLabel(string $slug): string
    {
        return match ($slug) {
            'cash' => 'Cash',
            'gcash' => 'GCash',
            'debit' => 'Debit',
            'credit' => 'Credit',
            'non_cash' => 'Non cash',
            default => ucfirst(str_replace('_', ' ', $slug)),
        };
    }

    /**
     * Printable / UI customer name: real client name, snapshot, or smart fallback (never raw "0").
     */
    public function resolvedCustomerName(): string
    {
        $c = $this->client;

        if ($c) {
            $raw = trim((string) $c->name);
            if ($raw !== '' && ! Client::isPlaceholderLabel($raw)) {
                return $raw;
            }
        }

        $snap = trim((string) ($this->customer_name ?? ''));
        if ($snap !== '' && ! Client::isPlaceholderLabel($snap)) {
            return $snap;
        }

        if ($c) {
            $v = $this->vehicle;
            if (! $v && $this->vehicle_id) {
                $v = $this->vehicle()->first();
            }
            $plate = $v ? trim((string) ($v->plate_number ?? '')) : '';
            if ($plate === '' || Client::isPlaceholderLabel($plate)) {
                $plate = trim((string) (Vehicle::query()
                    ->where('client_id', $c->id)
                    ->whereNotNull('plate_number')
                    ->where('plate_number', '!=', '')
                    ->orderBy('id')
                    ->value('plate_number') ?? ''));
            }

            $latestName = trim((string) (static::query()
                ->where('client_id', $c->id)
                ->whereNotNull('customer_name')
                ->where('customer_name', '!=', '')
                ->orderByDesc('created_at')
                ->value('customer_name') ?? ''));

            return $c->select2Label(
                $latestName !== '' ? $latestName : null,
                $plate !== '' ? $plate : null
            );
        }

        return $snap !== '' ? $snap : '—';
    }

    public function resolvedCustomerPhone(): ?string
    {
        $n = trim((string) ($this->number ?? ''));
        if ($n !== '' && ! Client::isPlaceholderLabel($n)) {
            return $n;
        }
        $c = $this->client;
        if ($c) {
            $p = trim((string) ($c->phone ?? ''));
            if ($p !== '' && ! Client::isPlaceholderLabel($p)) {
                return $p;
            }
        }

        return null;
    }

    public function resolvedCustomerAddress(): ?string
    {
        $a = trim((string) ($this->address ?? ''));
        if ($a !== '') {
            return $a;
        }
        $c = $this->client;
        if ($c) {
            $a2 = trim((string) ($c->address ?? ''));
            if ($a2 !== '') {
                return $a2;
            }
        }

        return null;
    }

    /**
     * Display fallbacks (use in views as $invoice->customer_display / $invoice->vehicle_display)
     */
    public function getCustomerDisplayAttribute(): string
    {
        return $this->resolvedCustomerName();
    }

    public function getVehicleDisplayAttribute(): string
    {
        return $this->vehicle
            ? $this->vehicle->plate_number
            : ($this->vehicle_name ?? '');
    }

    /**
     * Enum validation mutators
     */
    public function setSourceTypeAttribute(string $value): void
    {
        if (! in_array($value, static::$sourceTypes, true)) {
            throw new InvalidArgumentException("Invalid source_type: {$value}");
        }
        $this->attributes['source_type'] = $value;
    }

    public function setServiceStatusAttribute(string $value): void
    {
        if (! in_array($value, static::$serviceStatuses, true)) {
            throw new InvalidArgumentException("Invalid service_status: {$value}");
        }
        $this->attributes['service_status'] = $value;
    }

    public function setStatusAttribute(string $value): void
    {
        if (! in_array($value, static::$statuses, true)) {
            throw new InvalidArgumentException("Invalid status: {$value}");
        }
        $this->attributes['status'] = $value;
    }

    /**
     * Convenience helpers
     */
    public function isPending(): bool
    {
        return $this->service_status === 'pending';
    }

    public function isInProgress(): bool
    {
        return $this->service_status === 'in_progress';
    }

    public function isDone(): bool
    {
        return $this->service_status === 'done';
    }
}
