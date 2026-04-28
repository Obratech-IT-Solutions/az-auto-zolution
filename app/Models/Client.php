<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use App\Models\Invoice;
use App\Models\Vehicle;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    /**
     * Without a search term, plain orderBy('name') puts empty/placeholder names first, so Select2 shows
     * "Client #…" / phone digits. Real names should appear first when the dropdown opens.
     */
    public function scopeOrderForSelect2Dropdown(Builder $query): Builder
    {
        $bad = "LOWER(TRIM(COALESCE(name, ''))) IN ('0','-','—','n/a','na','none','.','null','undefined')";
        $driver = $query->getConnection()->getDriverName();
        $short = $driver === 'sqlite'
            ? 'LENGTH(TRIM(COALESCE(name, \'\'))) <= 1'
            : 'CHAR_LENGTH(TRIM(COALESCE(name, \'\'))) <= 1';

        return $query->orderByRaw(
            "CASE WHEN name IS NULL OR TRIM(name) = '' OR {$bad} OR {$short} THEN 1 ELSE 0 END ASC, name ASC, id ASC"
        );
    }

    /**
     * Label for Select2 when `name` is blank (import/merge leftovers).
     * Pass batch lookups from {@see latestInvoiceCustomerNameByClientId()} / {@see primaryVehiclePlateByClientId()}.
     */
    public function select2Label(?string $latestInvoiceCustomerName = null, ?string $primaryVehiclePlate = null): string
    {
        $name = trim((string) $this->name);
        if ($name !== '' && ! self::isPlaceholderLabel($name)) {
            return $name;
        }
        $bits = array_values(array_filter([
            trim((string) $this->phone),
            trim((string) $this->email),
        ], fn ($v) => $v !== '' && ! self::isPlaceholderLabel($v)));
        if ($bits !== []) {
            return implode(' · ', $bits);
        }

        $inv = trim((string) $latestInvoiceCustomerName);
        if ($inv !== '') {
            // Disambiguate: same invoice name can appear on multiple client rows.
            return sprintf('%s · Client #%d', $inv, $this->id);
        }

        $plate = trim((string) $primaryVehiclePlate);
        if ($plate !== '') {
            // Many client rows share the same plate in messy data; id makes the list selectable.
            return sprintf('Client #%d — %s', $this->id, $plate);
        }

        return 'Client #' . $this->id;
    }

    /** Treat junk / legacy placeholder "names" as empty so we fall back to plate, etc. */
    public static function isPlaceholderLabel(string $value): bool
    {
        $trimmed = trim($value);
        if (mb_strlen($trimmed) <= 1) {
            return true;
        }

        $v = strtolower($trimmed);

        return $v === '' || in_array($v, ['0', '-', '—', 'n/a', 'na', 'none', '.', 'null', 'undefined'], true);
    }

    /** Most recent non-empty invoice customer_name per client (for dropdown fallback). */
    public static function latestInvoiceCustomerNameByClientId(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return Invoice::query()
            ->whereIn('client_id', $ids)
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->orderByDesc('created_at')
            ->get(['client_id', 'customer_name'])
            ->unique('client_id')
            ->mapWithKeys(fn ($row) => [$row->client_id => trim((string) $row->customer_name)]);
    }

    /** First vehicle plate per client by id (stable fallback when client name is empty). */
    public static function primaryVehiclePlateByClientId(array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return Vehicle::query()
            ->whereIn('client_id', $ids)
            ->whereNotNull('plate_number')
            ->where('plate_number', '!=', '')
            ->orderBy('id')
            ->get(['client_id', 'plate_number'])
            ->unique('client_id')
            ->mapWithKeys(fn ($row) => [$row->client_id => trim((string) $row->plate_number)]);
    }
}
