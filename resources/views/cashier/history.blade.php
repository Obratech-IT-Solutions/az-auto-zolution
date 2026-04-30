@extends('layouts.cashier')

@section('title', 'Invoice/Quotation History')

@section('content')
<style>
    /* Same column widths for every date block; header/body stay aligned */
    .history-list-table {
        table-layout: fixed;
        width: 100%;
    }
    .history-list-table th,
    .history-list-table td {
        vertical-align: middle;
    }
    .history-list-table .hist-invoice {
        white-space: nowrap;
    }
    .history-list-table .hist-customer,
    .history-list-table .hist-vehicle {
        word-break: break-word;
        overflow-wrap: anywhere;
    }
    .history-list-table .hist-tag {
        white-space: nowrap;
    }
    .history-list-table .hist-view {
        text-align: center;
        white-space: nowrap;
    }
    .history-list-table .hist-lastprocessed {
        word-break: break-word;
        overflow-wrap: anywhere;
        font-size: 0.9rem;
    }
</style>
<div class="container mt-4">
    {{-- Search Bar --}}
    <form method="GET" action="{{ route('cashier.history') }}" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search by customer, plate, status, etc." value="{{ request('search') }}">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Search</button>
        </div>
    </form>

    @php
        $grouped = $history->getCollection()->groupBy(function($item) {
            return \Carbon\Carbon::parse($item->created_at)->format('F d, Y');
        });
        $badgeClass = [
            'quotation'    => 'bg-warning text-dark',
            'cancelled'    => 'bg-danger',
            'appointment'  => 'bg-info text-dark',
            'service_order'=> 'bg-secondary',
            'invoicing'    => 'bg-success text-white'
        ];
        $statusBadge = [
            'unpaid' => 'bg-secondary',
            'paid' => 'bg-success text-white',
            'cancelled' => 'bg-danger',
            'voided' => 'bg-dark text-white'
        ];
    @endphp

    @if($history->isEmpty())
        <p>No records found.</p>
    @else
        @foreach($grouped as $date => $records)
            <h4 class="mt-4">{{ $date }}</h4>
            <table class="table table-striped table-bordered align-middle history-list-table mb-3">
                <colgroup>
                    <col style="width: 10%;">
                    <col style="width: 16%;">
                    <col style="width: 10%;">
                    <col style="width: 10%;">
                    <col style="width: 11%;">
                    <col style="width: 9%;">
                    <col style="width: 13%;">
                    <col style="width: 9%;">
                    <col style="width: 12%;">
                </colgroup>
                <thead class="table-light">
                    <tr>
                        <th class="hist-invoice font-monospace">Invoice #</th>
                        <th class="hist-customer">Customer</th>
                        <th class="hist-vehicle">Vehicle</th>
                        <th class="hist-tag">Source Type</th>
                        <th class="hist-pay">Payment Type</th>
                        <th class="hist-svc">Service Status</th>
                        <th class="hist-lastprocessed">Last processed log</th>
                        <th class="hist-tag">Status</th>
                        <th class="hist-view">View</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $h)
                        <tr>
                            <td class="hist-invoice font-monospace">{{ $h->invoice_no ?? '—' }}</td>
                            <td class="hist-customer">{{ $h->resolvedCustomerName() }}</td>
                            <td class="hist-vehicle">{{ $h->vehicle->plate_number ?? $h->vehicle_name ?? '—' }}</td>
                            <td class="hist-tag">
                                <span class="badge {{ $badgeClass[$h->source_type] ?? 'bg-secondary' }}">
                                    {{ ucfirst(str_replace('_', ' ', $h->source_type)) }}
                                </span>
                            </td>
                            <td class="hist-pay">{{ $h->paymentTypeDisplay() }}</td>
                            <td class="hist-svc">{{ ucfirst(str_replace('_',' ', $h->service_status)) }}</td>
                            <td class="hist-lastprocessed">{{ $h->lastProcessedByUser?->attributionName() ?? '—' }}</td>
                            <td class="hist-tag">
                                <span class="badge {{ $statusBadge[$h->status] ?? 'bg-secondary' }}">
                                    {{ ucfirst($h->status) }}
                                </span>
                            </td>
                            <td class="hist-view">
                                <a href="{{ route('cashier.history.view', $h->id) }}" class="btn btn-sm btn-info" target="_blank">View</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach

        @if($history->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $history->links() }}
        </div>
        @endif
    @endif
</div>
@endsection
