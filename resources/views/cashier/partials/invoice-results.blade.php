@if($results->isEmpty())
  <div class="alert alert-info">No invoices found.</div>
@else
  <div class="table-responsive shadow-sm rounded">
    <table class="table table-hover align-middle inv-index-table">
    <thead class="table-light">
      <tr>
      <th>Customer</th>
      <th>Vehicle</th>
      <th class="text-center">Payment</th>
      <th class="text-center">Service</th>
      <th class="text-center">Status</th>
      <th class="text-end">Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($results as $h)
      <tr>
      <td>{{ $h->client->name ?? $h->customer_name }}</td>
      <td>{{ $h->vehicle->plate_number ?? $h->vehicle_name }}</td>
      <td class="text-center align-middle">
      <div class="inv-pay-wrap">
      @include('cashier.partials.invoice-payment-column', ['h' => $h])
      </div>
      </td>
      <td class="text-center align-middle">
      <div class="inv-pill-center">
      <span class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', $h->service_status)) }}</span>
      </div>
      </td>
      <td class="text-center align-middle">
      <div class="inv-pill-center">
      <span
      class="badge bg-{{ $h->status === 'paid' ? 'success' : ($h->status === 'unpaid' ? 'warning text-dark' : 'secondary') }}">
      {{ ucfirst($h->status) }}
      </span>
      </div>
      </td>
      <td class="text-end align-middle">
      <div class="inv-actions-inner">
      <a href="{{ route('cashier.invoice.view', $h->id) }}" class="btn btn-sm btn-outline-info"
      title="View Invoice">
      <i class="bi bi-eye"></i>
      </a>
      <a href="{{ route('cashier.invoice.edit', $h->id) }}?modal=1" class="btn btn-sm btn-outline-primary"
      data-bs-toggle="tooltip" title="Edit Invoice">
      <i class="bi bi-pencil-square"></i>
      </a>
      </div>
      </td>
      </tr>
    @endforeach
    </tbody>
    </table>
    <div class="d-flex justify-content-center my-4">
    {{ $results->links() }}
    </div>
  </div>
@endif
