export function paymentStatusLabel(status) {
    return {
        unpaid: 'Unpaid',
        partial: 'Partial',
        paid: 'Paid',
        advance: 'Advance',
    }[status] || status || 'Unpaid';
}

export function paymentStatusClass(status) {
    return {
        unpaid: 'text-bg-danger',
        partial: 'text-bg-warning',
        paid: 'text-bg-success',
        advance: 'text-bg-info',
    }[status] || 'text-bg-secondary';
}

export function paymentSummary(total, paid) {
    const safeTotal = Number(total) || 0;
    const safePaid = Number(paid) || 0;
    const due = Math.max(0, Math.round((safeTotal - safePaid) * 100) / 100);
    const advance = Math.max(0, Math.round((safePaid - safeTotal) * 100) / 100);
    let status = 'unpaid';
    if (safePaid > 0) {
        status = advance > 0 ? 'advance' : (due > 0 ? 'partial' : 'paid');
    }

    return {
        paid: Math.round(safePaid * 100) / 100,
        due,
        advance,
        status,
    };
}
