<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1>Item Form</h1>
    <form id="itemForm">
        @csrf
        <div class="mb-3">
            <input id="item_name" type="text" placeholder="Name" class="form-control" name="item_name" required>
        </div>
        <div class="mb-3">
            <input id="current_stock" type="number" placeholder="Stock" class="form-control" name="current_stock" required>
        </div>
        <div class="mb-3">
            <input id="item_price" type="number" placeholder="Price" step="0.01" class="form-control" name="item_price" required>
        </div>
        <button type="submit" class="btn btn-primary">Submit</button>
    </form>

    <h2 class="mt-5">Items</h2>
    <table class="table">
        <thead>
        <tr>
            <th>Name</th>
            <th>Stock</th>
            <th>Price</th>
            <th>Date</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody id="itemsTable">
        @php $sum = 0; @endphp
        @foreach($items as $item)
            @php
                $total = $item->current_stock * $item->item_price;
                $sum += $total;
            @endphp
            <tr data-id="{{ $item->id }}">
                <td>{{ $item->item_name }}</td>
                <td>{{ $item->current_stock }}</td>
                <td>{{ $item->item_price }}</td>
                <td>{{ $item->created_at }}</td>
                <td>{{ $total }}</td>
                <td>
                    <button class="btn btn-sm btn-warning edit-item">Edit</button>
                </td>
            </tr>
        @endforeach
            <tr>
                <td colspan="4"><strong>All sum:</strong></td>
                <td colspan="2">{{ $sum }}</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Popup -->
<div id="editModal" class="modal fade" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editItemForm">
                    @csrf
                    <div class="mb-3">
                        <input id="popup_item_name" type="text" placeholder="Name" class="form-control" name="item_name" required>
                    </div>
                    <div class="mb-3">
                        <input id="popup_current_stock" type="number" placeholder="Stock" class="form-control" name="current_stock" required>
                    </div>
                    <div class="mb-3">
                        <input id="popup_item_price" type="number" placeholder="Price" step="0.01" class="form-control" name="item_price" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// helpers
function formatDate(dateString) {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const seconds = String(date.getSeconds()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

document.addEventListener('DOMContentLoaded', function () {
    const editModal = new bootstrap.Modal(document.getElementById('editModal'));

    const lists = document.querySelectorAll('.edit-item');
    for (let list of lists) {
        list.addEventListener('click', editItem);
    }

    document.getElementById('itemForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        let form = e.target;
        let formData = new FormData(form);

        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        try {
            const response = await fetch('{{ route("items.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            });
            const item = await response.json();
            addItemToTable(item);
            form.reset();
        } catch (err) {
            console.log(err);
        }
    });

    function addItemToTable(item) {
        let totalValue = item.current_stock * item.item_price;
        let newRow = `
            <tr data-id="${item.id}">
                <td>${item.item_name}</td>
                <td>${item.current_stock}</td>
                <td>${item.item_price}</td>
                <td>${formatDate(item.created_at)}</td>
                <td>${totalValue}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-warning edit-item">Edit</button>
                </td>
            </tr>
        `;
        let itemsTable = document.getElementById('itemsTable');
        itemsTable.insertAdjacentHTML('afterbegin', newRow);

        itemsTable.querySelector('.edit-item').addEventListener('click', editItem);

        // Update total sum
        let currentTotal = parseFloat(itemsTable.querySelector('tr:last-child td:last-child').textContent);
        itemsTable.querySelector('tr:last-child td:last-child').textContent = currentTotal + totalValue;
    }

    document.getElementById('editModal').addEventListener('submit', async function (e) {
        e.preventDefault();

        const editForm = document.getElementById('editItemForm');
        const id = editForm.getAttribute('data-id');
        const formData = new FormData(editForm);
        const data = {};
        formData.forEach((value, key) => {
            data[key] = value;
        });

        try {
            const response = await fetch(`/items/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(data)
            });
            
            const uItem = await response.json();
            updateItem(uItem);
            editModal.hide();
        } catch (err) {
            console.error(err);
        }
    });

    function updateItem(item) {
        const tr = document.querySelector(`tr[data-id="${item.id}"]`);

        tr.children[0].textContent = item.item_name;
        tr.children[1].textContent = item.current_stock;
        tr.children[2].textContent = item.item_price;
        tr.children[3].textContent = formatDate(item.created_at);
        tr.children[4].textContent = item.current_stock * item.item_price;
    }

    function editItem(e) {
        const btn = e.target;
        const tr = btn.closest('tr');
        const id = tr.getAttribute('data-id');
        const itemName = tr.children[0].textContent;
        const currentStock = tr.children[1].textContent;
        const itemPrice = tr.children[2].textContent;

        document.getElementById('editModalLabel').textContent = `Edit Item (ID: ${id})`;
        document.getElementById('popup_item_name').value = itemName;
        document.getElementById('popup_current_stock').value = currentStock;
        document.getElementById('popup_item_price').value = itemPrice;
        document.getElementById('editItemForm').setAttribute('data-id', id);

        editModal.show();
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>