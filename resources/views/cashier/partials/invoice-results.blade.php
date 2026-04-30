@php
  $invListBadgeClass ??= [
    'quotation' => 'bg-warning text-dark',
    'cancelled' => 'bg-danger',
    'appointment' => 'bg-info text-dark',
    'service_order' => 'bg-secondary',
    'invoicing' => 'bg-success text-white',
  ];
  $invListStatusBadge ??= [
    'unpaid' => 'bg-secondary',
    'paid' => 'bg-success text-white',
    'cancelled' => 'bg-danger',
    'voided' => 'bg-dark text-white',
  ];
  $groupedResults = collect($results->items())->sortByDesc('created_at')->values()->groupBy(function ($item) {
    return \Carbon\Carbon::parse($item->created_at)->format('F d, Y');
  });
@endphp

@if($results->isEmpty())
  <div class="alert alert-info">No invoices found.</div>
@else
  @foreach($groupedResults as $date => $records)
    <h4 class="mt-4">{{ $date }}</h4>
    <table class="table table-striped table-bordered align-middle history-list-table mb-3">
      <colgroup>
        <col style="width:10%;">
        <col style="width:15%;">
        <col style="width:10%;">
        <col style="width:10%;">
        <col style="width:11%;">
        <col style="width:11%;">
        <col style="width:12%;">
        <col style="width:10%;">
        <col style="width:11%;">
      </colgroup>
      <thead class="table-light">
        <tr>
          <th class="hist-invoice font-monospace">Invoice #</th>
          <th class="hist-customer">Customer</th>
          <th class="hist-vehicle">Vehicle</th>
          <th class="hist-tag">Source Type</th>
          <th class="hist-pay">Payment Type</th>
          <th class="hist-svc">Service Status</th>
          <th class="hist-lastprocessed">Last processed</th>
          <th class="hist-tag">Status</th>
          <th class="hist-actions">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($records as $h)
          <tr>
            <td class="hist-invoice font-monospace">{{ $h->invoice_no ?? '—' }}</td>
            <td class="hist-customer">{{ $h->resolvedCustomerName() }}</td>
            <td class="hist-vehicle">{{ $h->vehicle->plate_number ?? $h->vehicle_name ?? '—' }}</td>
            <td class="hist-tag">
              <span class="badge {{ $invListBadgeClass[$h->source_type] ?? 'bg-secondary' }}">
                {{ ucfirst(str_replace('_', ' ', $h->source_type)) }}
              </span>
            </td>
            <td class="hist-pay">{{ $h->paymentTypeDisplay() }}</td>
            <td class="hist-svc">{{ ucfirst(str_replace('_', ' ', $h->service_status)) }}</td>
            <td class="hist-lastprocessed">{{ $h->lastProcessedByUser?->attributionName() ?? '—' }}</td>
            <td class="hist-tag">
              <span class="badge {{ $invListStatusBadge[$h->status] ?? 'bg-secondary' }}">{{ ucfirst($h->status) }}</span>
            </td>
            <td class="hist-actions">
              <div class="hist-actions-inner">
                <a href="{{ route('cashier.invoice.view', $h->id) }}" class="btn btn-sm btn-info" target="_blank">View</a>
                <a href="{{ route('cashier.invoice.edit', $h->id) }}?modal=1" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Edit Invoice">
                  <i class="bi bi-pencil-square"></i>
                </a>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endforeach

  <div class="d-flex justify-content-center my-4">
    {{ $results->links() }}
  </div>
@endif
