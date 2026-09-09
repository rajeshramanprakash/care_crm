@extends('admin.layouts.app')
@section('title', 'Productivity | Vendor')
@section('header-css')
<link rel="stylesheet" href="{{ asset('plugins/charts/chart.css') }}">
@endsection
@section('main')
<div class="content-wrapper pb-5">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Per-User Productivity</h1>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            <div class="card shadow">
                <div class="card-body">
                    <form id="productivityFilterForm" class="row g-3">
                        <div class="col-md-6">
                            <label for="user_id" class="form-label">Select User:</label>
                            <select name="user_id" id="user_id" class="form-control">
                                <option value="">-- Select User --</option>
                                @foreach($users as $user)
                                <option value="{{ $user->id }}">{{ $user->f_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="month" class="form-label">Select Month:</label>
                            <input type="month" name="month" id="month" class="form-control" value="{{ date('Y-m') }}">
                        </div>

                        <div class="col-12 text-center mt-3">
                            <button type="button" id="fetchProductivity" class="btn btn-primary">
                                <i class="fas fa-search"></i> Get Productivity
                            </button>
                            <a href="#" id="downloadExcel" class="btn btn-success">
                                <i class="fas fa-file-excel"></i> Download Excel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive mt-4">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Date</th>
                            <th>Sales</th>
                            <th>Doctor</th>
                            <th>Billing</th>
                            <th>Medicine</th>
                            <th>Lab</th>
                            <th>Dispatch</th>
                            <th>Subadmin</th>
                            <th>Total Files</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="productivityTableBody">
                        <tr>
                            <td colspan="8" class="text-center">No data available</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
</div>
</section>
</div>

@section('footer-script')
<script>
    document.getElementById('downloadExcel').addEventListener('click', function (e) {
    e.preventDefault();

    const userId = document.getElementById('user_id').value;
    const month = document.getElementById('month').value;

    if (!userId) {
        alert('Please select a user.');
        return;
    }

    window.location.href = `/admin/per-user-productivity-export?user_id=${userId}&month=${month}`;
});



    document.getElementById('fetchProductivity').addEventListener('click', function () {
    const userId = document.getElementById('user_id').value;
    const month = document.getElementById('month').value;

    if (!userId) {
        alert('Please select a user.');
        return;
    }

    fetch(`/admin/per-user-productivity-data?user_id=${userId}&month=${month}`)
        .then(response => response.json())
        .then(data => {
            const tbody = document.getElementById('productivityTableBody');
            tbody.innerHTML = '';

            if (data.data.length > 0) {
                data.data.forEach(row => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${row.day}</td>
                        <td>${row.sales}</td>
                        <td>${row.doctor}</td>
                        <td>${row.billing}</td>
                        <td>${row.medicine}</td>
                        <td>${row.lab}</td>
                        <td>${row.dispatch}</td>
                        <td>${row.subadmin}</td>
                        <td>${row.total_files}</td>
                        <td>${row.status}</td>
                    `;
                    tbody.appendChild(tr);
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No data available</td></tr>';
            }
        })
        .catch(error => console.error('Error fetching productivity data:', error));
});
</script>
@endsection
@endsection
