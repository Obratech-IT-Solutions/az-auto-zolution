@once
<style>
  /* 1fr | auto | 1fr — customer block is optically centered on the page; Obratech in column 3 */
  .invoice-print-footer {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: flex-end;
    gap: 0.5rem 0.75rem;
    margin-top: 1.25rem;
    padding-bottom: 0.5rem;
    box-sizing: border-box;
    line-height: 1.15;
  }
  .invoice-print-footer-balance {
    grid-column: 1;
    min-width: 0;
  }
  .invoice-print-footer-main {
    grid-column: 2;
    justify-self: center;
    text-align: center;
    min-width: 0;
    max-width: min(100%, 28rem);
  }
  .invoice-print-footer-main .customer-print-name {
    font-weight: bold;
    font-size: 1rem;
    letter-spacing: 0.04em;
  }
  .invoice-print-footer-main .signature {
    text-align: center;
    margin-top: 0.15rem;
    font-weight: bold;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    font-size: 0.95rem;
  }
  .invoice-print-footer-obratech-wrap {
    grid-column: 3;
    justify-self: end;
    text-align: right;
    min-width: 0;
    max-width: 100%;
  }
  .invoice-print-obratech-brand {
    display: inline-flex;
    flex-direction: row;
    align-items: center;
    gap: 0.5rem 0.65rem;
    text-align: left;
    font-size: 0.72rem;
    line-height: 1.35;
    color: #333;
    max-width: min(16.5rem, 100%);
    min-width: 0;
  }
  .invoice-print-obratech-lines {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.08rem;
    min-width: 0;
  }
  .invoice-print-obratech-line {
    display: block;
    white-space: nowrap;
  }
  .invoice-print-obratech-logo {
    display: block;
    flex-shrink: 0;
    margin: 0;
    max-height: 40px;
    width: auto;
    align-self: center;
  }
  .invoice-print-obratech-brand a {
    color: #1a5276;
    text-decoration: none;
  }
  @media print {
    .invoice-print-footer {
      page-break-inside: avoid;
      /* Non-printable margin on most printers — keep footer inside the sheet */
      padding-left: 6mm;
      padding-right: 8mm;
    }
    .invoice-print-obratech-brand {
      font-size: 0.65rem;
      gap: 0.35rem 0.45rem;
      max-width: min(15rem, 100%);
    }
    .invoice-print-obratech-logo {
      max-height: 30px;
    }
    .invoice-print-obratech-lines {
      max-width: 9.75rem;
    }
    .invoice-print-obratech-line {
      white-space: normal;
      overflow-wrap: break-word;
    }
  }
</style>
@endonce
