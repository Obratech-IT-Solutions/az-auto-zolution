<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request)
    {
        $clientQ = trim((string) $request->get('client_q', ''));
        $vehicleQ = trim((string) $request->get('vehicle_q', ''));

        $clientsForSelect = Client::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $clientsQuery = Client::query()->orderBy('created_at', 'desc');
        if ($clientQ !== '') {
            $like = '%' . addcslashes($clientQ, '%_\\') . '%';
            $clientsQuery->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                    ->orWhere('address', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }
        $clients = $clientsQuery
            ->paginate(self::PER_PAGE, ['*'], 'clients_page')
            ->withQueryString();

        $vehiclesQuery = Vehicle::query()->with('client')->orderBy('created_at', 'desc');
        if ($vehicleQ !== '') {
            $like = '%' . addcslashes($vehicleQ, '%_\\') . '%';
            $vehiclesQuery->where(function ($q) use ($like) {
                $q->where('plate_number', 'like', $like)
                    ->orWhere('model', 'like', $like)
                    ->orWhere('vin_chasis', 'like', $like)
                    ->orWhere('manufacturer', 'like', $like)
                    ->orWhere('year', 'like', $like)
                    ->orWhere('color', 'like', $like)
                    ->orWhere('odometer', 'like', $like)
                    ->orWhereHas('client', function ($cq) use ($like) {
                        $cq->where('name', 'like', $like);
                    });
            });
        }
        $vehicles = $vehiclesQuery
            ->paginate(self::PER_PAGE, ['*'], 'vehicles_page')
            ->withQueryString();

        return view('cashier.vehicle', compact('clients', 'clientsForSelect', 'vehicles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id'    => 'nullable|exists:clients,id',
            'plate_number' => 'required|string|unique:vehicles,plate_number',
            'model'        => 'nullable|string',
            'vin_chasis'   => 'nullable|string|unique:vehicles,vin_chasis',
            'manufacturer' => 'nullable|string',
            'year'         => 'nullable|string',
            'color'        => 'nullable|string',
            'odometer'     => 'nullable|string',
        ]);
        $vehicle = Vehicle::create($data);
        return response()->json($vehicle->load('client'));
    }

    public function update(Request $request, Vehicle $vehicle)
    {
        $data = $request->validate([
            'client_id'    => 'nullable|exists:clients,id',
            'plate_number' => 'required|string|unique:vehicles,plate_number,' . $vehicle->id,
            'model'        => 'nullable|string',
            'vin_chasis'   => 'nullable|string|unique:vehicles,vin_chasis,' . $vehicle->id,
            'manufacturer' => 'nullable|string',
            'year'         => 'nullable|string',
            'color'        => 'nullable|string',
            'odometer'     => 'nullable|string',
        ]);
        $vehicle->update($data);
        return response()->json($vehicle->load('client'));
    }

    public function destroy(Vehicle $vehicle)
    {
        $vehicle->delete();
        return response()->json(['deleted' => true]);
    }

    public function vehicles($id)
{
    $client = Client::findOrFail($id);
    $vehicles = $client->vehicles()->orderBy('created_at', 'desc')->get();

    return response()->json([
        'client' => $client,
        'vehicles' => $vehicles
    ]);
}

}
