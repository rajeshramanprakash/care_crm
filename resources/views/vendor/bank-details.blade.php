@extends('vendor.layouts.app')
@section('title', $page_heading ?? 'Bank Account Details')

@section('main')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ $page_heading ?? 'Bank Account Details' }}</h3>
                        </div>
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr><th style="width: 200px;">Account Name</th><td>{{ $vendor->account_name ?? '—' }}</td></tr>
                                <tr><th>Account Number</th><td>{{ $vendor->account_number ?? '—' }}</td></tr>
                                <tr><th>IFSC Code</th><td>{{ $vendor->ifsc_code ?? '—' }}</td></tr>
                                <tr><th>UPI ID</th><td>{{ $vendor->upi_id ?? '—' }}</td></tr>
                                <tr>
                                    <th>Bank Document</th>
                                    <td>
                                        @if($vendor->bank_document)
                                            <a href="{{ asset('storage/' . $vendor->bank_document) }}" target="_blank" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i> View</a>
                                            <a href="{{ asset('storage/' . $vendor->bank_document) }}" download class="btn btn-sm btn-success"><i class="fas fa-download"></i> Download</a>
                                        @else
                                            <span class="text-muted">Not uploaded</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
