document.addEventListener("DOMContentLoaded", function () {

    const expenseForm = document.getElementById("expenseForm");
    const expenseTable = document.getElementById("expenseTable");

    if (!expenseForm || !expenseTable) return;

    const expenseAmount = document.getElementById("expenseAmount");
    const expenseName = document.getElementById("expenseName");
    const expenseCategory = document.getElementById("expenseCategory");
    const expenseDate = document.getElementById("expenseDate");
    const expenseDescription = document.getElementById("expenseDescription");

    const summaryCards = document.querySelectorAll(
        ".transaction-summary .summary-card h3"
    );

    let transactions = [];

    function formatRupiah(amount) {
        return new Intl.NumberFormat("id-ID", {
            style: "currency",
            currency: "IDR",
            maximumFractionDigits: 0
        }).format(amount);
    }

    function renderTransactions() {

        expenseTable.innerHTML = "";

        if (transactions.length === 0) {

            expenseTable.innerHTML = `
                <tr>
                    <td colspan="5" class="table-empty">
                        Belum ada data pengeluaran.
                    </td>
                </tr>
            `;

        } else {

            transactions.forEach(item => {

                const row = document.createElement("tr");

                [
                    item.date,
                    item.name,
                    item.category,
                    formatRupiah(item.amount),
                    item.description || "-"
                ].forEach(value => {

                    const cell = document.createElement("td");
                    cell.textContent = value;
                    row.appendChild(cell);

                });

                expenseTable.appendChild(row);

            });

        }

        const total = transactions.reduce(
            (sum, item) => sum + item.amount, 0
        );

        if (summaryCards.length >= 2) {
            summaryCards[0].textContent = formatRupiah(total);
            summaryCards[1].textContent = transactions.length;
        }
    }

    expenseForm.addEventListener("submit", function (event) {

        event.preventDefault();

        const amount = Number(expenseAmount.value);

        if (!Number.isFinite(amount) || amount <= 0) {
            alert("Nominal harus lebih dari 0!");
            return;
        }

        const transaction = {
            name: expenseName.value.trim(),
            amount: amount,
            category: expenseCategory.value,
            date: expenseDate.value,
            description: expenseDescription.value.trim()
        };

        transactions.unshift(transaction);

        renderTransactions();
        expenseForm.reset();

        alert("Pengeluaran berhasil ditambahkan!");

    });

    renderTransactions();

});