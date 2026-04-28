<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientVehicleResolver
{
    public function resolveClientId(Request $request): ?int
    {
        if ($request->filled('client_id')) {
            return (int) $request->input('client_id');
        }

        $name = trim((string) $request->input('customer_name', ''));
        $phoneRaw = (string) ($request->input('number') ?? $request->input('phone') ?? '');
        $phoneDigits = $this->phoneDigits($phoneRaw);
        $email = trim((string) $request->input('email', ''));
        $address = trim((string) $request->input('address', ''));

        if ($name === '' && $phoneDigits === '' && $email === '') {
            return null;
        }

        if (strlen($phoneDigits) >= 7) {
            $byPhone = $this->findClientByPhoneDigits($phoneDigits);
            if ($byPhone) {
                $this->fillMissingClientFields($byPhone, $name, $phoneRaw, $address, $email);
                return $byPhone->id;
            }
        }

        if ($email !== '') {
            $client = Client::firstOrNew(['email' => $email]);
            if (! $client->exists) {
                $client->name = $name !== '' ? $name : 'Customer';
                $client->phone = $phoneRaw !== '' ? $phoneRaw : null;
                $client->address = $address !== '' ? $address : null;
            } else {
                $this->fillMissingClientFields($client, $name, $phoneRaw, $address, $email);
            }
            $client->save();

            return $client->id;
        }

        if ($name !== '') {
            $existing = Client::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                ->first();
            if ($existing) {
                $this->fillMissingClientFields($existing, $name, $phoneRaw, $address, $email);

                return $existing->id;
            }

            $client = Client::create([
                'name' => $name,
                'address' => $address !== '' ? $address : null,
                'phone' => $phoneRaw !== '' ? $phoneRaw : null,
                'email' => $email !== '' ? $email : null,
            ]);

            return $client->id;
        }

        return null;
    }

    public function resolveVehicleId(Request $request, ?int $clientId): ?int
    {
        if ($request->filled('vehicle_id')) {
            $vehicle = Vehicle::find((int) $request->input('vehicle_id'));
            if (! $vehicle) {
                return null;
            }
            if ($clientId) {
                $vehicle->client_id = $clientId;
            }
            $vehicle->fill([
                'plate_number' => $request->input('plate'),
                'model' => $request->input('model'),
                'year' => $request->input('year'),
                'color' => $request->input('color'),
                'odometer' => $request->input('odometer'),
            ]);
            $vehicle->save();

            return $vehicle->id;
        }

        if (! $this->wantsNewVehicleRecord($request)) {
            return null;
        }

        $plateNorm = $this->normalizePlate((string) $request->input('plate', ''));
        if ($plateNorm !== '') {
            $existing = $this->findVehicleByNormalizedPlate($plateNorm);
            if ($existing) {
                if ($clientId) {
                    $existing->client_id = $clientId;
                }
                $existing->fill([
                    'plate_number' => $request->input('plate') ?: $existing->plate_number,
                    'model' => $request->input('model') ?: $existing->model,
                    'year' => $request->input('year') ?: $existing->year,
                    'color' => $request->input('color') ?: $existing->color,
                    'odometer' => $request->input('odometer') ?? $existing->odometer,
                ]);
                $existing->save();

                return $existing->id;
            }
        }

        if (! $clientId && $plateNorm === '' && ! $request->filled('vehicle_name')) {
            return null;
        }

        $vehicle = Vehicle::create([
            'client_id' => $clientId,
            'plate_number' => $request->input('plate'),
            'model' => $request->input('model'),
            'year' => $request->input('year'),
            'color' => $request->input('color'),
            'odometer' => $request->input('odometer'),
        ]);

        return $vehicle->id;
    }

    private function wantsNewVehicleRecord(Request $request): bool
    {
        if ($request->filled('vehicle_name')) {
            return true;
        }

        return $request->filled('plate')
            || $request->filled('model')
            || $request->filled('year')
            || $request->filled('color')
            || $request->filled('odometer');
    }

    private function phoneDigits(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone);
    }

    private function findClientByPhoneDigits(string $digits): ?Client
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            return Client::query()
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->whereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') = ?", [$digits])
                ->first();
        }

        foreach (Client::query()->whereNotNull('phone')->where('phone', '!=', '')->cursor() as $client) {
            if ($this->phoneDigits($client->phone) === $digits) {
                return $client;
            }
        }

        return null;
    }

    private function fillMissingClientFields(Client $client, string $name, string $phoneRaw, string $address, string $email): void
    {
        $dirty = false;
        $currentName = trim((string) $client->name);
        if ($name !== '' && ($currentName === '' || Client::isPlaceholderLabel($currentName))) {
            $client->name = $name;
            $dirty = true;
        }
        $currentPhone = trim((string) ($client->phone ?? ''));
        if ($phoneRaw !== '' && ($currentPhone === '' || Client::isPlaceholderLabel($currentPhone))) {
            $client->phone = $phoneRaw;
            $dirty = true;
        }
        if ($address !== '' && trim((string) ($client->address ?? '')) === '') {
            $client->address = $address;
            $dirty = true;
        }
        if ($email !== '' && trim((string) ($client->email ?? '')) === '') {
            $client->email = $email;
            $dirty = true;
        }
        if ($dirty) {
            $client->save();
        }
    }

    private function normalizePlate(string $plate): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $plate));
    }

    private function findVehicleByNormalizedPlate(string $normalized): ?Vehicle
    {
        if ($normalized === '') {
            return null;
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            return Vehicle::query()
                ->whereNotNull('plate_number')
                ->where('plate_number', '!=', '')
                ->whereRaw(
                    "UPPER(REGEXP_REPLACE(REPLACE(TRIM(plate_number), '-', ''), '[^A-Za-z0-9]', '')) = ?",
                    [$normalized]
                )
                ->first();
        }

        foreach (Vehicle::query()->whereNotNull('plate_number')->where('plate_number', '!=', '')->cursor() as $vehicle) {
            if ($this->normalizePlate((string) $vehicle->plate_number) === $normalized) {
                return $vehicle;
            }
        }

        return null;
    }
}
