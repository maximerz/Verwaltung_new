<style>
.modern-table {
    overflow-x: auto;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.07);
    margin: 1rem 0;
}

.table {
    margin: 0;
    background: white;
    border-collapse: separate;
    border-spacing: 0;
    width: 100%;
}

.table thead th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    font-weight: 600;
    border: none;
    padding: 1.2rem 1rem;
    white-space: nowrap;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border-bottom: 3px solid #5568d3;
}

.table thead th:first-child {
    border-top-left-radius: 16px;
}

.table thead th:last-child {
    border-top-right-radius: 16px;
}

.table tbody td {
    padding: 1.1rem 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #e8eaf0;
    color: #2d3748;
    background: white;
    font-size: 0.95rem;
}

.table tbody tr:last-child td {
    border-bottom: none;
}

.table tbody tr:last-child td:first-child {
    border-bottom-left-radius: 16px;
}

.table tbody tr:last-child td:last-child {
    border-bottom-right-radius: 16px;
}

.table tbody tr:hover {
    background: linear-gradient(to right, #f7f9fc 0%, #eef2f7 100%);
    transition: all 0.3s ease;
    transform: scale(1.01);
}

.table tbody tr:hover td {
    background: transparent;
}

.table tbody tr {
    transition: all 0.3s ease;
}
</style>
