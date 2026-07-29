@extends('layouts.admin.master')

@section('title')Form 82050
{{ $title }}
@endsection

@push('css')
@endpush

@section('content')
@component('components.breadcrumb')
@slot('breadcrumb_title')
<h3>Form 82050</h3>
@endslot
<li class="breadcrumb-item">Forms</li>
<li class="breadcrumb-item active">Form 82050</li>
@endcomponent

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="body mt-5">
                    <h4 class="text-center">Form 82050</h4>
                    <h6 class="text-center">
                        Notice of Sale and/or Bill of Sale for a Motor Vehicle,<br>
                        Mobile Home, Off-Highway Vehicle or Vessel
                    </h6>
                    <br>
                    <div class="row" style="margin-left: 15px !important; margin-right: 15px !important;">
                        <form [formGroup]="Form82050Group">
                            <div class="container mt-4">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="82050_CB_NOS">
                                    <label class="form-check-label" for="82050_CB_NOS">
                                        Notice of Sale (Seller must complete sections 1 & 3). The purchaser’s signature in section 3 is optional.
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="82050_CB_BOS">
                                    <label class="form-check-label" for="82050_CB_BOS">
                                        Bill of Sale (Seller and purchaser must complete sections 1, 2 (when applicable) & 3).
                                    </label>
                                </div>
                            </div>
                            <br>
                            <hr class="m-0">
                            <div class="row px-2">
                                <div class="col-md-12">
                                    <div type="card" class="cus-mat-card">
                                        <br>
                                        <h6 class="p-2 custom-bg-grey">
                                            1. Motor Vehicle, Mobile Home, Off- Highway or Vessel Description
                                        </h6>
                                        <div class="row px-2">
                                            <div class="col-md-2">
                                                <label class="form-label">Year</label>
                                                <select id='default-form_year' class="form-control w-100">
                                                    <option selected>ONE</option>
                                                    <option>TWO</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Make/Manufacturer</label>
                                                <input id='82050_maker' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Body Type</label>
                                                <input id='82050_type' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Model</label>
                                                <input id='82050_model' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Color</label>
                                                <input id='82050_color' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-4 mt-2">
                                                <label class="form-label">Certificate of Title Number</label>
                                                <input id='82050_titleNum' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-3 mt-2">
                                                <label class="form-label">Current Title Issue Date</label>
                                                <input id='82050_ctid' class="form-control w-100" type="date">
                                            </div>
                                            <div class="col-md-5 mt-2">
                                                <label class="form-label">Vehicle/Vessel Identification
                                                    Number</label>
                                                <input id='82050_vehcile' class="form-control w-100" type="text">
                                            </div>
                                        </div>
                                        <br>
                                        <hr class="m-0">
                                        <br>
                                        <p class="pt-2 px-2">
                                            <b>
                                                I/we do hereby sell or have sold and delivered the above described
                                                motor vehicle, mobile home,
                                                off-highway vehicle or vessel to:
                                            </b>
                                        </p>

                                        <div class="row px-2 pt-2">
                                            <div class="col-md-12">
                                                <label class="form-label">Print Name(s) of Purchaser(s)</label>
                                                <input id='82050_pnop' class="form-control w-100" type="text">
                                            </div>
                                        </div>

                                        <div class="row px-2 pt-2">
                                            <div class="col-md-4">
                                                <label class="form-label">Address</label>
                                                <input id='82050_address' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">City</label>
                                                <select id='default-form_city' class="form-control w-100">
                                                    <option selected>ONE</option>
                                                    <option>TWO</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">State</label>
                                                <select id='default-form_state' class="form-control w-100">
                                                    <option selected>ONE</option>
                                                    <option>TWO</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Zip Code</label>
                                                <input id='82050_zipcode' class="form-control w-100" type="number">
                                            </div>
                                        </div>

                                        <div class="row px-2 pt-2">
                                            <div class="col-md-6">
                                                <label class="form-label">Date of Sale</label>
                                                <input id='82050_dos' class="form-control w-100" type="date">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Selling Price $ </label>
                                                <input id='82050_sp' class="form-control w-100" type="number">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <hr class="m-0">
                            <br>
                            <div class="row px-2">
                                <div class="col-md-12">
                                    <div type="card" class="cus-mat-card">
                                        <h6 class="p-2 custom-bg-grey">
                                            2. Odometer Disclosure Statement (Required For a Motor Vehicle)
                                        </h6>
                                        <p class="px-2 pt-2">
                                            <b>
                                                Federal and State law requires that you state the mileage in
                                                connection with the transfer of ownership. Failure to
                                                complete or providing a false statement may result in fines and/or
                                                imprisonment.
                                            </b>
                                        </p>
                                        <div class="align-items-baseline w-100 px-2">
                                            <span>WE STATE THAT THIS MOTOR VEHICLE’S </span>
                                            <input type="checkbox" id='82050_CB_5DIG_'> 5 DIGIT OR
                                            <input type="checkbox" id='82050_CB_6DIG'> 6 DIGIT ODOMETER NOW READS

                                        </div>
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="col-1">
                                                    <input id='82050_r1' class="form-control" type="number">
                                                </div>
                                                <div class="col-1">
                                                    <input id='82050_r2' class="form-control" type="number">
                                                </div>
                                                <div class="col-1">
                                                    <input id='82050_r3' class="form-control" type="number">
                                                </div>
                                                <div class="col-1">
                                                    <input id='82050_r4' class="form-control" type="number">
                                                </div>
                                                <div class="col-1">
                                                    <input id='82050_r5' class="form-control" type="number">
                                                </div>
                                                <div class="col-1">
                                                    <input id='82050_r6' class="form-control" type="number">
                                                </div>
                                                <div class="col-md-2">
                                                    .xx (NO TENTHS) MILES.
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <p class="mb-0 me-2">DATE READ</p>
                                                        </div>
                                                        <div class="col-md-8">
                                                            <input id='82050_dr' class="form-control" type="date" />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="align-items-baseline w-100 p-2 flex-wrap">
                                            AND WE HEREBY CERTIFY THAT TO THE BEST OF OUR KNOWLEDGE
                                            THE ODOMETER READING:
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <input type="checkbox" id='82050_CB_REF'> 1.
                                                    REFLECTS ACTUAL MILEAGE.
                                                    </mat-checkbox>
                                                </div>
                                                <div class="col-md-4">
                                                    <input type="checkbox" id='82050_CB_ISIN'> 2. IS
                                                    IN EXCESS OF ITS
                                                    MECHANICAL
                                                    LIMITS.

                                                </div>
                                                <div class="col-md-4">
                                                    <input type="checkbox" id='82050_CB_ISNOT'> 3. IS
                                                    NOT THE ACTUAL MILEAGE.
                                                </div>
                                            </div>
                                        </div>
                                        <h6 class="p-2">
                                            Affidavit (When applicable):
                                        </h6>
                                        <input id='82050_aff1' class="m-2 form-control w-100" />
                                        <input id='82050_aff2' class="m-2 form-control w-100" />
                                    </div>
                                </div>
                            </div>
                            <br>
                            <hr class="m-0">
                            <br>
                            <div class="row px-2">
                                <div class="col-md-12">
                                    <div type="card" class="cus-mat-card">
                                        <h6 class="p-2 custom-bg-grey">
                                            3. Certification
                                        </h6>
                                        <p class="px-2">
                                            UNDER PENALTIES OF PERJURY, I DECLARE THAT I HAVE READ THE FOREGOING
                                            DOCUMENT AND THAT THE FACTS
                                            STATED IN IT ARE TRUE.
                                        </p>
                                        <div class="px-2 row">
                                            <div class="col-md-4">
                                                <label class="form-label">Seller's Signature
                                                </label>
                                                <input id='82050_seller' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-5">
                                                <label class="form-label">Seller's Printed Name
                                                </label>
                                                <input id='82050_spn' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Date</label>
                                                <input id='82050_date1' class="form-control w-100" type="date">
                                            </div>
                                        </div>
                                        <div class="px-2 row">
                                            <div class="col-md-4 mt-2">
                                                <label class="form-label">Seller's Address
                                                </label>
                                                <input id='82050_sell' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-3 mt-2">
                                                <label class="form-label">City
                                                </label>
                                                <select id='default-form_scity' class="form-control w-100">
                                                    <option selected>ONE</option>
                                                    <option>TWO</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 mt-2">
                                                <label class="form-label">State</label>
                                                <select id='default-form_sstate' class="form-control w-100">
                                                    <option selected>ONE</option>
                                                    <option>TWO</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mt-2">
                                                <label class="form-label">Zip Code</label>
                                                <input id='82050_szipcode' class="form-control w-100" type="number">
                                            </div>
                                        </div>
                                        <div class="px-2 row">
                                            <div class="col-md-4 mt-2">
                                                <label class="form-label">Co-Seller's Signature (when
                                                    applicable)
                                                </label>
                                                <input id='82050_csswa' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-5 mt-2">
                                                <label class="form-label">Co-Seller's Printed Name (when
                                                    applicable)
                                                </label>
                                                <input id='82050_printedname' class="form-control w-100"
                                                    type="text">
                                            </div>
                                            <div class="col-md-3 mt-2">
                                                <label class="form-label">Date</label>
                                                <input id='82050_date12' class="form-control w-100" type="date">
                                            </div>
                                        </div>
                                        <div class="px-2 row">
                                            <div class="col-md-4 mt-2">
                                                <label class="form-label">Co-Seller's Address (when applicable)
                                                </label>
                                                <input id='82050_csawa1' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-3 mt-2">
                                                <label class="form-label">City
                                                </label>
                                                <select id='default-form_cscity' class="form-control w-100">
                                                    <option selected>ONE</option>
                                                    <option>TWO</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2 mt-2">
                                                <label class="form-label">State</label>
                                                <select id='default-form_csstate' class="form-control w-100">
                                                    <option selected>ONE</option>
                                                    <option>TWO</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3 mt-2">
                                                <label class="form-label">Zip Code</label>
                                                <input id='82050_cszipcode' class="form-control w-100"
                                                    type="number">
                                            </div>
                                        </div>
                                        <div class="px-2 row">
                                            <div class="col-md-4 mt-2">
                                                <label class="form-label">Purchaser's Signature
                                                </label>
                                                <input id='82050_purchase' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-5 mt-2">
                                                <label class="form-label">Purchaser's Printed Name
                                                </label>
                                                <input id='82050_printname' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-3 mt-2">
                                                <label class="form-label">Date</label>
                                                <input id='82050_date21' class="form-control w-100" type="date">
                                            </div>
                                        </div>
                                        <div class="px-2 row">
                                            <div class="col-md-4 mt-2">
                                                <label class="form-label">Co-Purchaser's Signature (when
                                                    applicable)
                                                </label>
                                                <input id='82050_sing21' class="form-control w-100" type="text">
                                            </div>
                                            <div class="col-md-5 mt-2">
                                                <label class="form-label">Co-Purchaser's Printed name (when
                                                    applicable)
                                                </label>
                                                <input id='82050_printname21' class="form-control w-100"
                                                    type="text">
                                            </div>
                                            <div class="col-md-3 mt-2">
                                                <label class="form-label">Date</label>
                                                <input id='82050_date31' class="form-control w-100" type="date">
                                            </div>
                                        </div>
                                    </div>
                                    <br>
                                    <p>
                                        <span class="text-danger fs-3">*</span>
                                        <b>
                                            OWNERSHIP STATUS FOR THE ABOVE DESCRIBED MOTOR VEHICLE, MOBILE HOME,
                                            OFF-HIGHWAY VEHICLE OR VESSEL
                                            WILL NOT CHANGE UNTIL THE PURCHASER APPLIES FOR AND IS ISSUED A
                                            CERTIFICATE OF TITLE.
                                        </b><br>
                                       
                                    </p>
                                </div>
                            </div>
                            <br>
                         
                            <!-- Botones -->
                            <div class="m-t-10 col-md-12">
                                    <button type="submit" class="btn btn-success btn-sm m-r-10">
                                        <i class="fa fa-save"></i>
                                        SAVE
                                    </button>
                                    <button type="button" class="btn btn-danger btn-sm">
                                        <i class="fa fa-times-circle"></i>
                                        CANCEL
                                    </button>
                                </div>
                                <br>
                            <br>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="{{asset('assets/js/form-wizard/form-wizard.js')}}"></script>
@endpush

@endsection