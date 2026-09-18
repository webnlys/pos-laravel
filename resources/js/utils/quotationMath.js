export function lineAmount(line, taxes = []) {
    const quantity = Number(line.quantity) || 0;
    const unitPrice = Number(line.unit_price) || 0;
    const gross = roundMoney(quantity * unitPrice);
    const discount = roundMoney(Math.min(Math.max(0, Number(line.discount) || 0), gross));
    const net = roundMoney(gross - discount);
    const tax = taxes.find((t) => Number(t.id) === Number(line.tax_id));
    const taxAmount = tax ? roundMoney(net * (Number(tax.rate_percent) || 0) / 100) : 0;
    return roundMoney(net + taxAmount);
}

export function lineTaxAmount(line, taxes = []) {
    const quantity = Number(line.quantity) || 0;
    const unitPrice = Number(line.unit_price) || 0;
    const gross = roundMoney(quantity * unitPrice);
    const discount = roundMoney(Math.min(Math.max(0, Number(line.discount) || 0), gross));
    const net = roundMoney(gross - discount);
    const tax = taxes.find((t) => Number(t.id) === Number(line.tax_id));
    return tax ? roundMoney(net * (Number(tax.rate_percent) || 0) / 100) : 0;
}

export function quotationTotals(items, taxes = []) {
    let subtotal = 0;
    let discount = 0;
    const taxRollup = {};

    items.forEach((line) => {
        const quantity = Number(line.quantity) || 0;
        const unitPrice = Number(line.unit_price) || 0;
        const gross = roundMoney(quantity * unitPrice);
        const lineDiscount = roundMoney(Math.min(Math.max(0, Number(line.discount) || 0), gross));
        const net = roundMoney(gross - lineDiscount);
        const tax = taxes.find((t) => Number(t.id) === Number(line.tax_id));
        const taxAmount = tax ? roundMoney(net * (Number(tax.rate_percent) || 0) / 100) : 0;

        subtotal += gross;
        discount += lineDiscount;

        if (tax) {
            if (!taxRollup[tax.id]) {
                taxRollup[tax.id] = { name: tax.name, rate_percent: tax.rate_percent, amount: 0 };
            }
            taxRollup[tax.id].amount = roundMoney(taxRollup[tax.id].amount + taxAmount);
        }
    });

    subtotal = roundMoney(subtotal);
    discount = roundMoney(discount);
    const taxesList = Object.values(taxRollup);
    const taxTotal = roundMoney(taxesList.reduce((sum, tax) => sum + tax.amount, 0));

    return {
        subtotal,
        discount,
        tax_total: taxTotal,
        total: roundMoney(subtotal - discount + taxTotal),
        taxes: taxesList,
    };
}

function roundMoney(value) {
    return Math.round((Number(value) || 0) * 100) / 100;
}
