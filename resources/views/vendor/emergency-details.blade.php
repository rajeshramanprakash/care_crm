@extends('vendor.layouts.app')
@section('title', $page_heading ?? 'Emergency Details')

@section('main')
<div class="content-wrapper">
    <section class="content">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">{{ $page_heading ?? 'Emergency Details' }}</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Emergency contact details will be displayed here once available.</p>
                            <table class="table table-bordered">
                                <tr><th style="width: 220px;">Emergency Contact Name</th><td>—</td></tr>
                                <tr><th>Emergency Contact Number</th><td>—</td></tr>
                                <tr><th>Relationship</th><td>—</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
