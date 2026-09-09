<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Doctor</th>
            <th>Billing</th>
            <th>Medicine</th>
            <th>Lab</th>
            <th>Dispatch</th>
            <th>Total Files</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @foreach($productivity as $row)
        <tr>
            <td>{{ $row->day }}</td>
            <td>{{ $row->doctor }}</td>
            <td>{{ $row->billing }}</td>
            <td>{{ $row->medicine }}</td>
            <td>{{ $row->lab }}</td>
            <td>{{ $row->dispatch }}</td>
            <td>{{ $row->total_files }}</td>
            <td>{{ $row->status }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
