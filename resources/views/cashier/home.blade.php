@extends('layouts.cashier')
@section('title', 'Cashier Dashboard')

@section('content')
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


    <style>
        .dashboard-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 22px;
            margin-top: 20px;
        }

        .card-dashboard {
            flex: 1 1 230px;
            background: #fff;
            border-radius: 12px;
            padding: 26px 22px 18px 22px;
            box-shadow: 0 4px 16px 0 rgba(0, 0, 0, 0.07);
            min-width: 210px;
            max-width: 250px;
            min-height: 125px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border-left: 7px solid #ffc107;
            margin-bottom: 14px;
        }

        .card-dashboard .icon {
            font-size: 2.1rem;
            margin-bottom: 10px;
        }

        .card-dashboard .count {
            font-size: 2.4rem;
            font-weight: bold;
            color: #222;
        }

        .card-dashboard .label {
            font-size: 1.08rem;
            color: #555;
            margin-bottom: 6px;
        }

        .card-dashboard .view-link {
            font-size: 0.97rem;
            color: #1767f2;
            font-weight: 500;
            text-decoration: none;
        }

        .card-dashboard.invoicing {
            border-left-color: #007bff;
        }

        .card-dashboard.quotation {
            border-left-color: #ffc107;
        }

        .card-dashboard.appointment {
            border-left-color: #28a745;
        }

        .card-dashboard.history {
            border-left-color: #6f42c1;
        }

        .card-dashboard.inventory {
            border-left-color: #343a40;
        }

        .card-dashboard.service {
            border-left-color: #fd7e14;
        }

        #calendar .fc-toolbar-title {
            font-size: 1.25rem;
            font-weight: 600;
        }

        #calendar .fc-daygrid-event {
            font-size: 0.85rem;
            padding: 2px 4px;
        }

        #calendar .fc-event:hover {
            background-color: #357ab8 !important;
            cursor: pointer;
        }

        .dashboard-overview-head {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-end;
            gap: 1rem;
            margin-bottom: 0.5rem;
        }

        .dashboard-filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: flex-end;
            max-width: 100%;
        }

        .dashboard-filter-form .form-label {
            font-size: 0.75rem;
            margin-bottom: 0.15rem;
            color: #555;
        }

        .dashboard-range-hint {
            font-size: 0.9rem;
            color: #6c757d;
            margin: 0 0 4px 0;
        }
    </style>

    <div class="dashboard-overview-head">
        <div>
            <h2 class="mb-1">Dashboard Overview</h2>
            <p class="dashboard-range-hint">
                @if(($period ?? 'all') === 'all')
                    Counts show <strong>all time</strong>.
                @else
                    <strong>{{ $rangeLabel ?? '' }}</strong> —
                    Quotation, invoicing, history, inventory &amp; service orders use <strong>created date</strong> in range. Appointment count &amp; calendar use <strong>appointment date</strong> in range.
                @endif
            </p>
        </div>
        <form method="get" action="{{ route('cashier.dashboard') }}" class="dashboard-filter-form" aria-label="Dashboard date filter">
            <div>
                <label for="dash_period" class="form-label fw-semibold">Period</label>
                <select id="dash_period" name="period" class="form-select form-select-sm">
                    <option value="all" @selected(($period ?? 'all') === 'all')>All time</option>
                    <option value="day" @selected(($period ?? '') === 'day')>Day</option>
                    <option value="week" @selected(($period ?? '') === 'week')>Week</option>
                    <option value="month" @selected(($period ?? '') === 'month')>Month</option>
                    <option value="year" @selected(($period ?? '') === 'year')>Year</option>
                </select>
            </div>
            <div>
                <label for="dash_ref" class="form-label fw-semibold">Reference date</label>
                <input type="date" id="dash_ref" name="ref" value="{{ old('ref', $refDateInput ?? \Carbon\Carbon::today()->format('Y-m-d')) }}"
                    class="form-control form-control-sm" style="min-width:11rem;">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
        </form>
    </div>
    <div class="dashboard-cards">

        <div class="card-dashboard quotation">
            <div class="icon"><i class="fas fa-file-alt text-warning"></i></div>
            <div class="count">{{ $quotationCount ?? 0 }}</div>
            <div class="label">Quotation</div>
            <a href="{{ route('cashier.quotation.index') }}" class="view-link">View All &rarr;</a>
        </div>

        <div class="card-dashboard invoicing">
            <div class="icon"><i class="fas fa-file-invoice-dollar text-primary"></i></div>
            <div class="count">{{ $invoicingCount ?? 0 }}</div>
            <div class="label">Invoicing</div>
            <a href="{{ route('cashier.invoice.index') }}" class="view-link">View All &rarr;</a>
        </div>

        <div class="card-dashboard appointment">
            <div class="icon"><i class="fas fa-calendar-check text-success"></i></div>
            <div class="count">{{ $appointmentCount ?? 0 }}</div>
            <div class="label">Appointment</div>
            <a href="{{ route('cashier.appointment.index') }}" class="view-link">View All &rarr;</a>
        </div>

        <div class="card-dashboard history">
            <div class="icon"><i class="fas fa-history" style="color:#6f42c1"></i></div>
            <div class="count">{{ $historyCount ?? 0 }}</div>
            <div class="label">History</div>
            <a href="{{ route('cashier.history') }}" class="view-link">View All &rarr;</a>
        </div>

        <div class="card-dashboard inventory">
            <div class="icon"><i class="fas fa-boxes text-dark"></i></div>
            <div class="count">{{ $inventoryCount ?? 0 }}</div>
            <div class="label">Inventory</div>
            <a href="{{ route('cashier.inventory.index') }}" class="view-link">View All &rarr;</a>
        </div>

        <div class="card-dashboard service">
            <div class="icon"><i class="fas fa-tools" style="color:#fd7e14"></i></div>
            <div class="count">{{ $serviceOrderCount ?? 0 }}</div>
            <div class="label">Service Orders</div>
            <a href="{{ route('cashier.service-order') }}" class="view-link">View All &rarr;</a>
        </div>
    </div>

    <div class="card mt-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">📅 Appointment Calendar</h5>
        </div>
        <div class="card-body p-4">
            <div id="calendar" style="min-height: 600px;"></div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let calendarEl = document.getElementById('calendar');

            let calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                themeSystem: 'bootstrap',
                height: "auto",
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listWeek'
                },
                events: @json($events ?? []), // Make sure $events is passed from controller
                eventClick: function (info) {
                    info.jsEvent.preventDefault();
                    if (info.event.url) {
                        window.location.href = info.event.url;
                    }
                }
            });

            calendar.render();
        });
    </script>


    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
@endsection