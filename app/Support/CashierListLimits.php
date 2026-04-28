<?php

namespace App\Support;

/** Bounded list sizes for cashier pages (avoid unbounded ->get() on large tables). */
final class CashierListLimits
{
    public const SIDEBAR_INVOICE_HISTORY = 150;

    public const QUOTATION_SO_APPOINTMENT_HISTORY = 150;

    public const HISTORY_INDEX_PER_PAGE = 30;

    public const EXPENSES_PER_PAGE = 25;

    /** Cap FullCalendar payload on home. */
    public const HOME_APPOINTMENTS_MAX = 500;
}
