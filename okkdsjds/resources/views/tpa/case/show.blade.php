@extends('tpa.layouts.app')

@section('title', 'Case Details')

@section('header-css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
@endsection

@section('main')
    <div class="content-wrapper pb-5 pt-4">
        <section class="content">
            <div class="card mb-3">
                <div class="card-header text-light" style="background-color: var(--wb-renosand);">
                    <h3 class="card-title">Case Information</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Id: </span>
                            <span class="mx-1"> {{ $case->id }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Case Code: </span>
                            <span class="mx-1">{{ $case->case_code }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)"> Name: </span>
                            <span class="mx-1">{{ $case->name }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Age: </span>
                            <span class="mx-1">{{ $case->age }} </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Hospital: </span>
                            <span class="mx-1">{{ $case->hospital ?? 'N/A' }}</span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Corp: </span>
                            <span class="mx-1">{{ $case->corp ?? 'N/A' }} </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Approved Amount: </span>
                            <span class="mx-1">{{ $case->approved_amt ?? 'N/A' }} </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Approved Type: </span>
                            <span class="mx-1">
                                @php
                                    if ($case->tpa_type === 'direct') {
                                        echo 'Direct';
                                    } elseif ($case->tpa_type === 'first') {
                                        $authUserId = Auth::guard('TPA')->user()->id;
                                        if ($case->tpa_allot_after_claim_no_received == $authUserId) {
                                            echo 'First';
                                        } elseif ($case->tpa_allot_after_claim_no_received_two == $authUserId) {
                                            echo 'Second';
                                        }
                                    }
                                @endphp
                            </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Status: </span>
                            <span class="mx-1">{{ $case->status ?? 'N/A' }} </span>
                        </div>
                        <div class="col-sm-6">
                            <span class="text-bold mx-1" style="color: var(--wb-wood)">Commision: </span>
                            <span class="mx-1 ">
                                @php
                                    if ($case->tpa_type === 'direct') {
                                        echo $case->commission_main_tpa ?? 0;
                                    } elseif ($case->tpa_type === 'first') {
                                        $authUserId = Auth::guard('TPA')->user()->id;
                                        if ($case->tpa_allot_after_claim_no_received == $authUserId) {
                                            echo $case->commission_first_tpa ?? 0;
                                        } elseif ($case->tpa_allot_after_claim_no_received_two == $authUserId) {
                                            echo $case->commission_second_tpa ?? 0;
                                        }
                                    }
                                @endphp
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

@section('footer-script')

@endsection

@endsection
