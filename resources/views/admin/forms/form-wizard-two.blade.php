@extends('layouts.admin.master')

@section('title')Form 82053
{{ $title }}
@endsection

@push('css')
@endpush

@section('content')
@component('components.breadcrumb')
@slot('breadcrumb_title')
<h3>Form 82053</h3>
@endslot
<li class="breadcrumb-item">Forms</li>
<li class="breadcrumb-item active">Form 82053</li>
@endcomponent

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="body mt-5">
					<h4 class="text-center">Form 82053</h4>
					<h6 class="text-center fw-bold mt-4">POWER OF ATTORNEY FOR A MOTOR VEHICLE, MOBILE HOME, VESSEL or
						VESSEL with TRAILER </h6>
					<br>
					<div class="row" style="margin-left: 18px !important; margin-right: 18px !important;">
						<form [formGroup]="Form82053Group">
							<div class="col-md-12">
								<div class="row">
									<p style="text-align: justify;">
										As of today,
										<span class="d-inline-flex align-items-center">
											<input id='82053_today' type="date" class="form-control col-md-2 me-2" />
										</span>
										I/we hereby name and appoint,
										<span class="d-inline-flex align-items-center">
											<input id='82053appoint_' type="text" class="form-control col-md-8" />
										</span>
										to be my/our lawful attorney-in-fact to act for me/us, in applying for an original or duplicate certificate of title, to register, transfer title, or record a lien to the motor vehicle, mobile home, vessel, or vessel with a trailer described below, and to print my/our name and sign their name, in my/our behalf.
										My attorney-in-fact can also do all things necessary to the application or any other related instrument and to bind me/us in as sufficient a manner as I/we or myself/ourselves could do, were I/we personally present and signing the same.
									</p>
									<p style="text-align: justify;">
										With full power of substitution and revocation, I/we hereby ratify and confirm whatever my/our said attorney-in-fact may lawfully do or cause to be done in the virtue hereof.
									</p>
								</div>
							</div>
							<div class="w-100">
								<p>
									Please check only one of the following options:
								</p>
								<div class="row">
									<div class="col-md-2">
										<input type="checkbox" id='82053_cb_mv'>
										Motor Vehicle

									</div>
									<div class="col-md-2">
										<input type="checkbox" id='82053_cb_mh'>
										Mobile Home

									</div>
									<div class="col-md-2">
										<input type="checkbox" id='82053_cb_ves'>
										Vessel

									</div>
									<div class="col-md-3">
										<input type="checkbox" id='82053_cb_veswith'>
										Vessel with an Untitled Trailer
										(Trailers 2,000 pounds or more)

									</div>
									<div class="col-md-3">
										<input type="checkbox" id='82053_cb_title'>
										Vessel with a Titled Trailer
										(Trailers less than 2,000 pounds)

									</div>
								</div>
							</div>
							<br />
							<br />
							<div type="card" class="cus-mat-card">
								<div class="row px-2 custom-bg-grey">
									<div class="col-md-2">
										<label class="form-label">Year</label>
										<select id='82040_year' class="form-control w-100">
											<option selected>ONE</option>
											<option>TWO</option>
										</select>
									</div>
									<div class="col-md-2">
										<label class="form-label">Make/Manufacturer</label>
										<input id='82053_make' class="form-control w-100" type="text">
									</div>
									<div class="col-md-2">
										<label class="form-label">Body Type</label>
										<input id='82053_type' class="form-control w-100" type="text">
									</div>
									<div class="col-md-3">
										<label class="form-label">Title Number</label>
										<input id='82053_titlenum' class="form-control w-100" type="text">
									</div>
									<div class="col-md-3">
										<label class="form-label">
											<small>Vehicle Identification Number (VIN)/
												Hull Identification Number (HIN) </small>
										</label>
										<input id='82053_vin' class="form-control w-100" type="text">
									</div>
								</div>
								<br>
								<div class="row px-2 custom-bg-grey">
									<div class="col-md-2">
										<label class="form-label">Year</label>
										<select id='82040_year1' class="form-control w-100">
											<option selected>ONE</option>
											<option>TWO</option>
										</select>
									</div>
									<div class="col-md-2">
										<label class="form-label">Make/Manufacturer</label>
										<input id='82053_make1' class="form-control w-100" type="text">
									</div>
									<div class="col-md-2">
										<label class="form-label">Body Type</label>
										<input id='82053_type1' class="form-control w-100" type="text">
									</div>
									<div class="col-md-3">
										<label class="form-label">Title Number</label>
										<input id='82053_titlenum' class="form-control w-100" type="text">
									</div>
									<div class="col-md-3">
										<label class="form-label">
											<small>Vehicle Identification Number (VIN)/
												Hull Identification Number (HIN) </small>
										</label>
										<input id='82053_hin' class="form-control w-100" type="text">
									</div>
								</div>
								<br>
							</div>
							<hr class="m-0">
							<br>
							<div class="col-md-12">
								<div class="row">
									<h5>NOTICE TO OWNER(S):</h5>
									<p> Please complete this form in its entirety prior to signing. </p>
								</div>
								<div class="row">
									<p><b>Under penalties of perjury, I/we declare that I/we have read the foregoing document and that the facts stated in it are true. </b></p>
								</div>
							</div>
							<div type="card" class="cus-mat-card">
								<div class="row px-2 custom-bg-grey">
									<div class="col-md-8">
										<label class="form-label">Legibly Printed Name of Owner (“Grantor”)
										</label>
										<input id='82053_lpnoo' class="form-control w-100" type="text">
									</div>
									<div class="col-md-4">
										<label class="form-label">Signature of Owner (“Grantor”)
										</label>
										<input id='82053_sing' class="form-control w-100" type="text">
									</div>
								</div>

								<div class="row px-2 custom-bg-grey">
									<div class="col-md-8 mt-2">
										<label class="form-label">Driver License, Identification Card or FEID Number
											of Owner</label>
										<input id='82053_ddlicofn' class="form-control w-100" type="text">
									</div>
									<div class="col-md-4 mt-2">
										<label class="form-label">Date of Birth of Owner, if applicable
										</label>
										<input id='82053_doboo' class="form-control w-100" type="date">
									</div>
								</div>

								<div class="row px-2 custom-bg-grey">
									<div class="col-md-6 mt-2">
										<label class="form-label">
											<small>Owner’s Address</small>
										</label>
										<input id='82053_owner' class="form-control w-100" type="text">
									</div>
									<div class="col-md-3 mt-2">
										<label class="form-label">
											<small>City</small>
										</label>
										<select id='82040_city' class="form-control w-100">
											<option selected>ONE</option>
											<option>TWO</option>
										</select>
									</div>
									<div class="col-md-2 mt-2">
										<label class="form-label">
											<small>State</small>
										</label>
										<select id='82040_state' class="form-control w-100">
											<option selected>ONE</option>
											<option>TWO</option>
										</select>
									</div>
									<div class="col-md-1 mt-2">
										<label class="form-label">
											<small>Zipcode</small>
										</label>
										<input id='82053_zipcode' class="form-control w-100" type="number">
									</div>
								</div>

								<div class="row px-2 custom-bg-grey">
									<div class="col-md-8 mt-2">
										<label class="form-label">Legibly Printed Name of Co-Owner (“Grantor”), if
											applicable
										</label>
										<input id='82053_lpnoco' class="form-control w-100" type="text">
									</div>
									<div class="col-md-4 mt-2">
										<label class="form-label">Signature of Co-Owner (“Grantor”)
										</label>
										<input id='82053_soco' class="form-control w-100" type="text">
									</div>
								</div>

								<div class="row px-2 custom-bg-grey">
									<div class="col-md-8 mt-2">
										<label class="form-label">Driver License, Identification Card or FEID Number
											of Co-Owner</label>
										<input id='82053_dlicofn' class="form-control w-100" type="text">
									</div>
									<div class="col-md-4 mt-2">
										<label class="form-label">Date of Birth of Co-Owner, if applicable
										</label>
										<input id='82053_dob1' class="form-control w-100" type="date">
									</div>
								</div>

								<div class="row px-2 custom-bg-grey">
									<div class="col-md-6 mt-2">
										<label class="form-label">
											<small>Co-Owner’s Address</small>
										</label>
										<input id='82053_' class="form-control w-100" type="text">
									</div>
									<div class="col-md-3 mt-2">
										<label class="form-label">
											<small>City</small>
										</label>
										<select id='82040_cocity' class="form-control w-100">
											<option selected>ONE</option>
											<option>TWO</option>
										</select>
									</div>
									<div class="col-md-2 mt-2">
										<label class="form-label">
											<small>State</small>
										</label>
										<select id='82040_costate' class="form-control w-100">
											<option selected>ONE</option>
											<option>TWO</option>
										</select>
									</div>
									<div class="col-md-1 mt-2">
										<label class="form-label">
											<small>Zipcode</small>
										</label>
										<input id='82053_cozip' class="form-control w-100" type="number">
									</div>
								</div>
							</div>
							<p class="px-2 pt-4">
								This non-secure power of attorney form may be used when an individual or entity
								appointed as the attorney-infact will be completing the odometer disclosure
								statement as the buyer only or the seller only. However, this
								form cannot be used to allow an individual or entity (such as a dealership) to sign
								as both buyer and seller for
								the purpose of disclosing the odometer reading. This may be accomplished only with
								the secure power of
								attorney (HSMV 82995) when:
							</p>
							<div class="px-4 mx-4 w-100">
								<ol style="list-style-type: lower-alpha;">
									<li>the title is physically being held by the lienholder; or</li>
									<li>the title is lost.</li>
								</ol>
								<br>
							</div>
							<p class="px-2">
								A licensed dealer and his/her employees are considered a single entity. The Owner
								and/or Co-owner must be the
								same for ALL vehicles, mobile homes, vessels, or vessels with a trailer listed
								above.
							</p>
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
						<br>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
@push('scripts')
<script src="{{asset('assets/js/form-wizard/form-wizard-two.js')}}"></script>
@endpush

@endsection