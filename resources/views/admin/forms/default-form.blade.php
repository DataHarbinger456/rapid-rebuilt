@extends('layouts.admin.master')

@section('title')Form 82040
{{ $title }}
@endsection

@push('css')
@endpush

@section('content')
@component('components.breadcrumb')
@slot('breadcrumb_title')
<h3>Form 82040</h3>
@endslot
<li class="breadcrumb-item">Forms</li>
<li class="breadcrumb-item active">Form 82040</li>
@endcomponent

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12 col-xl-12">
			<div class="col-md-12">
				<div class="card">
					<div class="body mt-5">
						<h4 class="text-center">Form 82040</h4>
						<h6 class="text-center">Note: All fields are required unless otherwise stated or not applicable.
						</h6>
						<div class="row" style="margin-left: 15px !important; margin-right: 15px !important;">
						<form [formGroup]="Form82040Group" method="POST" action="{{ route('forms.store', '82040') }}">
						@csrf
								<div class="col-md-12">
									<div class="row mt-3">
										<div class="col-md-5">
											<div class="row" style="margin-left: 10px !important;">
												<label class="form-label fw-bold"><b>Application Type: </b></label>
												<div class="d-flex align-items-center">
													<div class="form-check me-3">
														<input type="checkbox" class="form-check-input" id='82040_CB_O'>
														<label class="form-check-label" for='82040_CB_O'>Original</label>
													</div>
													<div class="form-check">
														<input type="checkbox" class="form-check-input" id='82040_CB_T'>
														<label class="form-check-label" for='82040_CB_T'>Transfer</label>
													</div>
												</div>
											</div>
										</div>
										<div class="col-md-7">
											<div class="row" style="margin-left: 10px !important;">
												<label class="form-label fw-bold"><b>Request to print Certificate of Title:</b></label>
												<div class="d-flex align-items-center">
													<div class="form-check me-3">
														<input type="checkbox" class="form-check-input" id='82040_CB_NO'>
														<label class="form-check-label" for='82040_CB_NO'>No</label>
													</div>
													<div class="form-check me-3">
														<input type="checkbox" class="form-check-input" id='82040_CB_YI'>
														<label class="form-check-label" for='82040_CB_YI'>Yes: In office</label>
													</div>
													<div class="form-check">
														<input type="checkbox" class="form-check-input" id='82040_CB_YM'>
														<label class="form-check-label" for='82040_CB_YM'>Yes: Mailed</label>
													</div>
												</div>
											</div>
										</div>

										<div class="col-md-12 mt-3">
											<div class="row" style="margin-left: 10px !important;">
												<label class="form-label fw-bold"><b>Off-Highway Vehicle Type: </b></label>
												<div class="d-flex align-items-center">
													<div class="form-check me-3">
														<input type="checkbox" class="form-check-input" id='82040_CB_ATV'>
														<label class="form-check-label" for='82040_CB_ATV'>All-Terrain Vehicle (ATV)</label>
													</div>
													<div class="form-check me-3">
														<input type="checkbox" class="form-check-input" id='82040_CB_TOHV'>
														<label class="form-check-label" for='82040_CB_TOHV'>Recreational Off-Highway Vehicle (ROV)</label>
													</div>
													<div class="form-check">
														<input type="checkbox" class="form-check-input" id='82040_CB_OHM'>
														<label class="form-check-label" for='82040_CB_OHM'>Off-Highway Motorcycle (OHM)</label>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="col-md-12">
									<h6 class="p-2 custom-bg-grey">Section 1: OWNER/APPLICANT INFORMATION</h6>
									<hr class="m-0">
									<br>
									<div class="row px-2">
										<div class="col-md-3">
											<label class="form-label">Customer Number</label>
											<input id='82040_CN' class="form-control w-100" type="text">
										</div>
										<div class="col-md-3">
											<label class="form-label">Fleet Number</label>
											<input id='82040_FN' class="form-control w-100" type="text">
										</div>
										<div class="col-md-3">
											<label class="form-label">Unit Number</label>
											<input id='82040_UN' class="form-control w-100" type="text">
										</div>
										<div class="col-md-3">
											<label class="form-label">Owner's County of Residence" </label>
											<input id='82040_OCR' class="form-control w-100" type="text">
										</div>
									</div>
									<br>
									<hr class="m-0">
									<br>
									<div class="row px-2">
										<div class="col-md-2 align-items-center d-flex">
											<label class="form-label"> <b>Owner Details</b>
											</label>
										</div>
										<div class="col-md-3">
											<label class="form-label">Are you a Florida Resident?</label>
											<div class="row">
												<div class="form-check">
													<input type="checkbox" class="form-check-input" id='82040_CB_YES'>
													<label class="form-check-label" for='82040_CB_YES'>YES</label>
												</div>
												<div class="form-check">
													<input type="checkbox" class="form-check-input" id='82040_CB_NO1'>
													<label class="form-check-label" for='82040_CB_NO1'>NO</label>
												</div>
											</div>
										</div>
										<div class="col-md-3">
											<label class="form-label">Are you a US Citizen?</label>
											<div class="row">
												<div class="form-check">
													<input type="checkbox" class="form-check-input" id='82040_CB_YES1'>
													<label class="form-check-label" for='82040_CB_YES1'>YES</label>
												</div>
												<div class="form-check">
													<input type="checkbox" class="form-check-input" id='82040_CB_NO2'>
													<label class="form-check-label" for='82040_CB_NO2'>NO</label>
												</div>
											</div>
										</div>
										<div class="col-md-4">
											<label class="form-label">Are you deaf or hard of hearing? (Voluntary)</label>
											<div class="row">
												<div class="form-check">
													<input type="checkbox" class="form-check-input" id='82040_CB_YES2'>
													<label class="form-check-label" for='82040_CB_YES2'>YES</label>
												</div>
												<div class="form-check">
													<input type="checkbox" class="form-check-input" id='82040_CB_NO3'>
													<label class="form-check-label" for='82040_CB_NO3'>NO</label>
												</div>
											</div>
										</div>
									</div>

									<hr class="m-0">
									<br>
									<div class="row pt-2 px-2">
										<div class="col-md-6">
											<span>When joint ownership, please indicate if “or” or “and” is to
												be shown on title when issued. </span>
											<input type="checkbox" id='82040_CB_OR'>
											OR <span style="margin-left: 2%;"></span>
											<input type="checkbox" id='82040_CB_AND'>
											AND
											<br>
											<span>(If neither box is checked, the title will be issued with
												"and.")</span>
										</div>
										<div class="col-md-3">
											<span>Select, if applicable:</span><br>
											<input type="checkbox" id='82040_CB_TBTE'>
											Tenancy by the Entirety
										</div>
										<div class="col-md-3">
											<input type="checkbox" id='82040_CB_LERP'>
											Life Estate/Remainder Person
											<br>
											<input type="checkbox" id='82040_CB_WROS'>
											With Rights of Survivorship
										</div>
									</div>
									<br>
									<hr class="m-0">
									<br>
									<div class="row pt-2 px-2">
										<div class="col-md-8">
											<label class="form-label">
												Owner’s Name as It Appears on Driver License
												<br />
												<small>(First, Full Middle/Maiden, & Last Name)</small>
											</label>
											<input id='82040_FMLN' class="form-control w-100" type="text">
										</div>
										<div class="col-md-4">
											<label class="form-label">
												Owner’s Phone Number <br />
												<small>(Voluntary)</small>
											</label>
											<input id='82040_OPN' class="form-control w-100" type="tel">
										</div>
										<div class="col-md-6 mt-2">
											<label class="form-label">Owner’s Email
												<small>(Voluntary)</small>
											</label>
											<input id='82040_OE' class="form-control w-100" type="email">
										</div>
										<div class="col-md-3 mt-2">
											<label class="form-label">Sex<br />
											</label>
											<select id='82040_SEX' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-3 mt-2">
											<label class="form-label">Date of Birth</label>
											<input id='82040_DOB' class="form-control w-100" type="date">
										</div>
									</div>
									<br>
									<hr class="m-0">
									<br>
									<div class="row pt-2 px-2">
										<div class="col-md-6">
											<label class="form-label">
												FL DL/ID or FEID/Suffix Number
											</label>
											<input id='82040_SN' class="form-control w-100" type="text">
										</div>
										<div class="col-md-6">
											<label class="form-label">
												Owner’s Mailing Address
											</label>
											<input id='82040_OMA' class="form-control w-100" type="text">
										</div>
										<div class="col-md-6  mt-2">
											<label class="form-label">City<br />
											</label>
											<select id='82040_CITY' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-3  mt-2">
											<label class="form-label">State<br />
											</label>
											<select id='82040_STATE' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-3  mt-2">
											<label class="form-label">Zip Code<br />
												<small> </small>
											</label>
											<input id='82040_ZC' class="form-control w-100" type="number">
										</div>
									</div>
									<div class="row pt-2 px-2">
										<div class="col-md-12">
											<label class="form-label">
												Owner’s Residential Street Address
											</label>
											<input id='82040_ORSA' class="form-control w-100" type="text">
										</div>
										<div class="col-md-4  mt-2">
											<label class="form-label">City<br />
											</label>
											<select id='82040_CITY1' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-4  mt-2">
											<label class="form-label">State<br />
											</label>
											<select id='82040_STATE1' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-4  mt-2">
											<label class="form-label">
												Zip Code
											</label>
											<input id='82040ZC1_' class="form-control w-100" type="number">
										</div>
									</div>
									<br>
									<hr class="m-0">
									<br>
									<div class="row pt-2 px-2">
										<div class="col-md-8">
											<label class="form-label">
												Mail To Customer Name <br>
												<small>(If different from above owner)</small>
											</label>
											<input id='82040_MTOCN' class="form-control w-100" type="text">
										</div>
										<div class="col-md-4">
											<label class="form-label">
												Mail To’s Phone Number<br>
												<small>(Voluntary)</small>
											</label>
											<input id='82040_MTPN' class="form-control w-100" type="tel">
										</div>
										<div class="col-md-4  mt-2">
											<label class="form-label">Mail To’s Email
												<small>(Voluntary)</small>
											</label>
											<input id='82040_MTOE1' class="form-control w-100" type="email">
										</div>
										<div class="col-md-4  mt-2">
											<label class="form-label">Sex
											</label>
											<select id='82040_SEX1' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-4  mt-2">
											<label class="form-label">
												Date of Birth
											</label>
											<input id='82040_DOB1' class="form-control w-100" type="date">
										</div>
									</div>
									<br>
									<hr class="m-0">
									<br>
									<div class="row pt-2 px-2">
										<div class="col-md-6">
											<label class="form-label">
												FL DL/ID or FEID/Suffix Number
											</label>
											<input id='82040_SN1' class="form-control w-100" type="text">
										</div>
										<div class="col-md-6">
											<label class="form-label">
												Mail To’s Address (If different from above mailing address)
											</label>
											<input id='82040_MTA1' class="form-control w-100" type="text">
										</div>
										<div class="col-md-6  mt-2">
											<label class="form-label">City<br />
											</label>
											<select id='82040_CITY2' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-3  mt-2">
											<label class="form-label">State<br />
											</label>
											<select id='82040_STATE2' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-3  mt-2">
											<label class="form-label">Zip Code<br />
												<small> </small>
											</label>
											<input id='82040_ZC2' class="form-control w-100" type="number">
										</div>
									</div>
									<br>
									<hr class="m-0">
									<br>
									<div class="row px-2">
										<div class="col-md-2 align-items-center d-flex">
											<label class="form-label"> <b>Co-Owner Details</b>
											</label>
										</div>
										<div class="col-md-3">
											<label class="form-label">Are you a Florida Resident?</label>
											<div class="row">
												<div class="form-check">
													<input type="checkbox" class="form-check-input" id='82040_CB_YES11'>
													<label class="form-check-label" for='82040_CB_YES11'>YES</label>
												</div>
												<div class="form-check">
													<input type="checkbox" class="form-check-input" id='82040_CB_NO11'>
													<label class="form-check-label" for='82040_CB_NO11'>NO</label>
												</div>
											</div>
										</div>
										<div class="col-md-3">
											<label class="form-label">Are you a US Citizen?</label>
											<div class="row">
												<div class="form-check">
													<input type="checkbox" class="form-check-input" id='82040_CB_YES12'>
													<label class="form-check-label" for='82040_CB_YES12'>YES</label>
												</div>
												<div class="form-check">
													<input type="checkbox" class="form-check-input" id='82040_CB_NO12'>
													<label class="form-check-label" for='82040_CB_NO12'>NO</label>
												</div>
											</div>
										</div>
										<div class="col-md-4">
											<label class="form-label">Are you deaf or hard of hearing? (Voluntary)</label>
											<div class="row">
												<div class="form-check">
													<input type="checkbox" class="form-check-input" id='82040_CB_YES22'>
													<label class="form-check-label" for='82040_CB_YES22'>YES</label>
												</div>
												<div class="form-check">
													<input type="checkbox" class="form-check-input" id='82040_CB_NO23'>
													<label class="form-check-label" for='82040_CB_NO23'>NO</label>
												</div>
											</div>
										</div>
									</div>
									<br>
									<hr class="m-0">
									<br>
									<div class="row pt-2 px-2">
										<!-- First Row -->
										<div class="col-md-8">
											<label class="form-label">
												<input type="checkbox" id='82040_CB_CO2'>
												Co-Owner or
												<input type="checkbox" id='82040_CB_LNIADL'>
												Lessee’s Name as It Appears on Driver License
												<small>(First, Full Middle/Maiden, & Last Name)</small>
												<br />
											</label>
											<input id='82040_FMLN1' class="form-control w-100" type="text">
										</div>
										<div class="col-md-4">
											<label class="form-label">
												Co-Owner’s Phone Number <small>(Voluntary)</small>
											</label>
											<input id='82040_COPN' class="form-control w-100" type="tel">
										</div>
									</div>

									<div class="row pt-2 px-2">
										<!-- Second Row -->
										<div class="col-md-6">
											<label class="form-label">Co-Owner’s Email
												<small>(Voluntary)</small>
											</label>
											<input id='82040_COE1' class="form-control w-100" type="email">
										</div>
										<div class="col-md-3">
											<label class="form-label">Sex</label>
											<select id='82040_SEX2' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-3">
											<label class="form-label">Date of Birth</label>
											<input id='82040_DOB3' class="form-control w-100" type="date">
										</div>
									</div>
									<br>
									<hr class="m-0">
									<br>
									<div class="row pt-2 px-2">
										<div class="col-md-6">
											<label class="form-label">
												FL DL/ID or FEID/Suffix Number
											</label>
											<input id='82040_SN3' class="form-control w-100" type="text">
										</div>
										<div class="col-md-6">
											<label class="form-label">
												Co-Owner’s/Lessee’s Mailing Address
											</label>
											<input id='82040_COMA2' class="form-control w-100" type="tel">
										</div>
										<div class="col-md-6 mt-2">
											<label class="form-label">City<br />
											</label>
											<select id='82040_CITY3' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-3 mt-2">
											<label class="form-label">State<br />
											</label>
											<select id='82040_STATE3' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-3 mt-2">
											<label class="form-label">Zip Code<br />
												<small> </small>
											</label>
											<input id='82040_ZC3' class="form-control w-100" type="number">
										</div>
									</div>
									<br>
									<hr class="m-0">
									<br>
									<div class="row pt-2 px-2">
										<div class="col-md-12">
											<label class="form-label">
												Co-Owner’s/Lessee’s Residential Street Address
											</label>
											<input id='82040_COLRSA' class="form-control w-100" type="text">
										</div>
										<div class="col-md-4 mt-2">
											<label class="form-label">City<br />
											</label>
											<select id='82040_CITY4' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-4 mt-2">
											<label class="form-label">State<br />
											</label>
											<select id='82040_STATE4' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-4 mt-2">
											<label class="form-label">
												Zip Code
											</label>
											<input id='82040_ZC4' class="form-control w-100" type="number">
										</div>
									</div>

								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="col-md-12">

									<h6 class="p-2 custom-bg-grey">Section 2: MOTOR VEHICLE DESCRIPTION</h6>
									<hr class="m-0">
									<br>
									<div class="row px-2">
										<div class="col-md-3">
											<label class="form-label">
												<small>Vehicle Identification Number (VIN)</small>
											</label>
											<input id='82040_VIN' class="form-control w-100" type="text">
										</div>
										<div class="col-md-3">
											<label class="form-label">Florida Title Number' </label>
											<input id='82040_FTN' class="form-control w-100" type="text">
										</div>
										<div class="col-md-3">
											<label class="form-label">License Plate Number</label>
											<input id='82040_LPN' class="form-control w-100" type="text">
										</div>
										<div class="col-md-3">
											<label class="form-label">Previous State of Issue' </label>
											<input id='82040_PSOI' class="form-control w-100" type="text">
										</div>

										<div class="col-md-2 mt-2">
											<label class="form-label">Make/Manufacturer' </label>
											<input id='82040_MM' class="form-control w-100" type="text">
										</div>
										<div class="col-md-2 mt-2">
											<label class="form-label">Model</label>
											<input id='82040_MODEL' class="form-control w-100" type="text">
										</div>
										<div class="col-md-1 mt-2">
											<label class="form-label">Year</label>
											<select id='82040_YEAR' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-1 mt-2">
											<label class="form-label">Body</label>
											<input id='82040_BODY' class="form-control w-100" type="text">
										</div>
										<div class="col-md-1 mt-2">
											<label class="form-label">Color</label>
											<input id='82040_COLOR' class="form-control w-100" type="text">
										</div>
										<div class="col-md-2 mt-2">
											<label class="form-label">Length</label>
											<div class="w-100 d-flex">
												<label>Ft.</label>
												<input id='82040_LENGTH' class="form-control w-100" type="number">
												<label>In.</label>
												<input id='82040_IN' class="form-control w-100" type="number">
											</div>
										</div>
										<div class="col-md-1 mt-2">
											<label class="form-label">Weight</label>
											<input id='82040_WEIGHT' class="form-control w-100" type="number">
										</div>
										<div class="col-md-1 mt-2">
											<label class="form-label">GVW</label>
											<input id='82040_GVW' class="form-control w-100" type="text">
										</div>
										<div class="col-md-1 mt-2">
											<label class="form-label">BHP/CC</label>
											<input id='82040_BHP' class="form-control w-100" type="number">
										</div>

										<div class="col-md-3 mt-2">
											<label class="form-label">Van Use</label>
											<small>(If applicable)</small>
											<div class="form-check">
												<input type="checkbox" class="form-check-input" id='82040_CB_PASS'>
												<label class="form-check-label" for='82040_CB_PASS'>Passenger</label>
											</div>
											<div class="form-check">
												<input type="checkbox" class="form-check-input" id='82040_CB_OTHER'>
												<label class="form-check-label" for='82040_CB_OTHER'>Other</label>
											</div>
										</div>
										<div class="col-md-9 mb-3 mt-2">
											<label class="form-label">Fuel Type</label><br>
											<div class="row">
												<div class="col-md-4">
													<div class="form-check">
														<input type="checkbox" class="form-check-input" id='82040_CB_NSL'>
														<label class="form-check-label" for='82040_CB_NSL'>Natural Gas (Liquid)</label>
													</div>
												</div>
												<div class="col-md-4">
													<div class="form-check">
														<input type="checkbox" class="form-check-input" id='82040_CB_NSC'>
														<label class="form-check-label" for='82040_CB_NSC'>Natural Gas (Compressed)</label>
													</div>
												</div>
												<div class="col-md-4">
													<div class="form-check">
														<input type="checkbox" class="form-check-input" id='82040_CB_HGE'>
														<label class="form-check-label" for='82040_CB_HGE'>Hybrid (Gas/Electric)</label>
													</div>
												</div>
												<div class="col-md-4">
													<div class="form-check">
														<input type="checkbox" class="form-check-input" id='82040_CB_DE'>
														<label class="form-check-label" for='82040_CB_DE'>Hybrid (Diesel/Electric)</label>
													</div>
												</div>
												<div class="col-md-4">
													<div class="form-check">
														<input type="checkbox" class="form-check-input" id='82040_CB_ELE'>
														<label class="form-check-label" for='82040_CB_ELE'>Electric</label>
													</div>
												</div>
											</div>
										</div>
									</div>

								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="col-md-12">

									<h6 class="p-2 custom-bg-grey">Section 3: BRANDS, USAGE AND TYPE (Check
										applicable types) </h6>
									<hr class="m-0">
									<br>
									<div class="row px-2">
										<div class="col-md-2">
											<input type="checkbox" id='82040_CB_AFP'>
											<label for='82040_CB_AFP'>Assembled from Parts</label>
											<br>
											<input type="checkbox" id='82040_CB_LTL'>
											<label for='82040_CB_LTL'>Long Term Lease</label>
										</div>
										<div class="col-md-2">
											<input type="checkbox" id='82040_CB_AUTO'>
											<label for='82040_CB_AUTO'>Autonomous</label>
											<br>
											<input type="checkbox" id='82040_CB_MBB'>
											<label for='82040_CB_MBB'>Manuf. Buy Back</label>
										</div>
										<div class="col-md-2">
											<input type="checkbox" id='82040_CB_BT'>
											<label for='82040_CB_BT'>Bonded Title</label>
											<br>
											<input type="checkbox" id='82040_CB_PV'>
											<label for='82040_CB_PV'>Police Veh.</label>
										</div>
										<div class="col-md-2">
											<input type="checkbox" id='82040_CB_CUS'>
											<label for='82040_CB_CUS'>Custom</label>
											<br>
											<input type="checkbox" id='82040_CB_PRI'>
											<label for='82040_CB_PRI'>Private Use</label>
										</div>
										<div class="col-md-2">
											<input type="checkbox" id='82040_CB_ELEC'>
											<label for='82040_CB_ELEC'>Electric</label>
											<br>
											<input type="checkbox" id='82040_CB_REB'>
											<label for='82040_CB_REB'>Rebuilt</label>
										</div>
									</div>

									<div class="row px-2 mb-3">
										<div class="col-md-2">
											<input type="checkbox" id='82040_CB_FLO'>
											<label for='82040_CB_FLO'>Flood</label>
											<br>
											<input type="checkbox" id='82040_CB_REP'>
											<label for='82040_CB_REP'>Replica</label>
										</div>
										<div class="col-md-2">
											<input type="checkbox" id='82040_CB_GKIT'>
											<label for='82040_CB_GKIT'>Glider Kit</label>
											<br>
											<input type="checkbox" id='82040_CB_STL'>
											<label for='82040_CB_STL'>Short Term Lease</label>
										</div>
										<div class="col-md-2">
											<input type="checkbox" id='82040_CB_ILEV'>
											<label for='82040_CB_ILEV'>ILEV</label>
											<br>
											<input type="checkbox" id='82040_CB_ROD'>
											<label for='82040_CB_ROD'>Street Rod</label>
										</div>
										<div class="col-md-2">
											<input type="checkbox" id='82040_CB_CAR'>
											<label for='82040_CB_CAR'>Kit Car</label>
											<br>
											<input type="checkbox" id='82040_CB_TAX'>
											<label for='82040_CB_TAX'>Taxicab</label>
										</div>
									</div>

								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="col-md-12">

									<h6 class="p-2 custom-bg-grey">Section 4: LIENHOLDER INFORMATION (If
										applicable) </h6>
									<hr class="m-0">
									<br>
									<div class="row px-2">
										<div class="col-md-4">
											<label class="form-label">ELT Customer</label><br>
											<input type="checkbox" id='82040_CB_YES33'>
											<span style="margin-right: 2%;">YES</span>
											<input type="checkbox" id='82040_CB_NO33'>
											<span>NO</span>
											<br>
										</div>
										<div class="col-md-8">
											<input type="checkbox" id='82040_CB_FS'>
											<span style="margin-right: 2%;">FEID/Suffix #</span>
											<input type="checkbox" id='82040_CB_DA'>
											<span style="margin-right: 2%;">DMV Account #</span>
											<input type="checkbox" id='82040_CB_SDOB'>
											DL/ID #, Sex and DOB
											<br>
											<input id='82040_SAD' class="form-control w-100" type="text">
										</div>
										<div class="col-md-6 mt-2">
											<label class="form-label">Lienholder’s Phone Number (Voluntary)'
											</label><br>
											<input id='82040_LPN' class="form-control w-100" type="tel">
										</div>
										<div class="col-md-6 mt-2">
											<label class="form-label">Lienholder’s Email (Voluntary)' </label><br>
											<input id='82040_LEV' class="form-control w-100" type="email">
										</div>
									</div>

									<div class="row px-2">
										<div class="col-md-2 mt-2">
											<label class="form-label">Date of Lien</label>
											<input id='82040_DOL' class="form-control w-100" type="date">
										</div>
										<div class="col-md-4 mt-2">
											<label class="form-label">Lienholder’s Mailing Address' </label>
											<input id='82040_LMA1' class="form-control w-100" type="text">
										</div>
										<div class="col-md-3 mt-2">
											<label class="form-label">City</label>
											<select id='82040_CITY5' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-2 mt-2">
											<label class="form-label">State</label>
											<select id='82040_STATE5' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-1">
											<label class="form-label">Zip</label>
											<input id='82040_ZC5' class="form-control w-100" type="number">
										</div>
									</div>
									<div class="row px-2">
										<div class="col-md-6 mt-2">
											<label class="form-label">Lienholder’s Name' </label><br />
											<small> (If box is not checked, title will be mailed to the first
												lienholder.)</small>
											<input id='82040_LN2' class="form-control w-100" type="text">
										</div>
										<div class="col-md-6 mt-2">
											<label class="form-label">
												<input type="checkbox" id='82040_CB_CTBIY'>
												<small>Check this box if you, lienholder representative,
													authorize the Department to send
													the motor vehicle title to the owner and sign here:</small>
												<br />
											</label>
											<input id='82040_CTIY' class="form-control w-100" type="text">
										</div>
									</div>

								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="col-md-12">
									<h6 class="p-2 custom-bg-grey">Section 5: TRANSFER TYPE (If applicable)</h6>
									<hr class="m-0">
									<br>
									<div class="row px-2">
										<!-- First Row -->
										<div class="col-lg-8">
											<label class="form-label">
												<small class="form-label">If ownership has transferred, how and when was the motor vehicle acquired?</small>
											</label>
											<br>
											<div class="d-flex align-items-center">
												<div class="col-auto" style="margin-right: 2%;">
													<input type="checkbox" id='82040_CB_INH' >
													<span>Inheritance  </span>
												</div>
												<div class="col-auto">
													<input type="checkbox" id='82040_CB_SALE'>
													<span>Sale</span>
												</div>
												<span style="margin-right: 1%;">(Price:$</span>
												<input id='82040_SAL1' class="form-control w-10" type="number" style="margin-right: 5px;">
												<span>.</span>
												<input id='82040_SALE2' class="form-control w-25" type="number" style="margin-right: 5px;">
												<span>)</span>
											</div>
										</div>
										<div class="col-lg-4">
											<label class="form-label">Date Acquired</label>
											<input id='82040_DA1' class="form-control w-100" type="date">
										</div>
									</div>
								</div>
								<br />
								<!-- Second Row -->
								<div class="col-md-12">
									<div class="row px-2 d-flex align-items-center">
										<div class="col-auto">
											<input type="checkbox" id='82040_CB_GIFT'>
											<span>Gift</span>
										</div>
										<div class="col-auto" style="margin-left: 1%;">
											<input type="checkbox" id='82040_CB_REPOS'>
											<span>Repossession</span>
										</div>
										<div class="col-auto" style="margin-left: 1%;">
											<input type="checkbox" id='82040_CB_COR'>
											<span>Court Order</span>
										</div>
										<div class="col-auto" style="margin-left: 1%;">
											<input type="checkbox" id='82040_CB_OTHER33'>
											<span>Other (Specify):</span>
										</div>
										<div class="col" style="margin-left: 1%;">
											<input id='82040_OTHERS' class="form-control" type="text" style="width: 100%;">
										</div>
									</div>
								</div>
								<br>
								<hr class="m-0">
								<br>

								<style>
								.custom-bg-grey {
								background-color: #f8f9fa;
								}
								</style>
								<div class="col-md-12">
									<h6 class="p-2 custom-bg-grey">Section 6: ODOMETER DECLARATION</h6>
									<hr class="m-0">
									<br>
									<p class="p-2 custom-bg-grey">
										<b style="color: red;">WARNING: </b>
										Federal and State law requires that you state the mileage in
										connection with an application for a Certificate of Title. Failure to
										complete or providing a
										false statement may result in fines or imprisonment.
									</p>
									
									<hr class="m-0">
									<br>
									<div class="col-md-12">
										<div class="row">
											<div class="col-md-10">
												<span>I/we state that this </span>
												<input type="checkbox" id='82040_CB_OR1'> 5
												<span> or </span>
												<input type="checkbox" id='82040_CB_DIGI'> 6-digit
												<span> odometer now reads</span>
												<div class="row">
													<div class="col-md-1">
														<input id='82040_ONR' class="form-control" type="text">
													</div>
													<div class="col-md-1">
														<input id='82040_ONR1' class="form-control" type="text">
													</div>
													<div class="col-md-1">
														<input id='82040_ONR2' class="form-control" type="text">
													</div>
													<div class="col-md-1">
														<input id='82040_ONR3' class="form-control" type="text">
													</div>
													<div class="col-md-1">
														<input id='82040_ONR4' class="form-control" type="text">
													</div>
													<div class="col-md-1">
														<input id='82040_ONR5' class="form-control" type="text">
													</div>
													<div class="col-4">
														.xx miles.
													</div>
												</div>
											</div>
											<div class="col-md-2">
												<label class="form-label">Date Read</label>
												<input id='82040_DR3' class="form-control w-100" type="date">
											</div>
										</div>
									</div>
									<div class="row px-2">
										<div class="col-md-12">
											<p>I/we hereby certify that to the best of my/our knowledge the
												odometer reading: </p>
										</div>
										<div class="col-md-4 mb-3">
											<input type="checkbox" id='82040_CB_RAM'> 1. REFLECTS
											ACTUAL MILEAGE.'
										</div>
										<div class="col-md-4">
											<input type="checkbox" id='82040_CB_INTAM'> 2. IS NOT THE
											ACTUAL MILEAGE.'
										</div>
										<div class="col-md-4">
											<input type="checkbox" id='82040_CB_IIEOIM'> 3. IS IN
											EXCESS OF ITS MECHANICAL
											LIMITS.
										</div>
									</div>

								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="col-md-12">

									<h6 class="p-2 custom-bg-grey">Section 7: DEALER SALES TAX REPORT AND MOTOR
										VEHICLE TRADE IN INFORMATION (If applicable) </h6>
									<hr class="m-0">
									<br>
									<div class="row px-2">
										<div class="col-md-3">
											<label class="form-label">Florida Sales Tax Registration Number
											</label>
											<input id='82040_FSTRN' class="form-control w-100" type="text">
										</div>
										<div class="col-md-2">
											<label class="form-label">Dealer License Number' </label>
											<input id='82040_DLN3' class="form-control w-100" type="text">
										</div>
										<div class="col-md-2">
											<label class="form-label">Date of Sale</label>
											<input id='82040_DOS1' class="form-control w-100" type="date">
										</div>
										<div class="col-md-2">
											<label class="form-label">Amount of Tax </label>
											<input id='82040_AOT1' class="form-control w-100" type="number">
										</div>
										<div class="col-md-3">
											<label class="form-label">Dealer/Agent Signature</label>
											<input id='82040_DAS1' class="form-control w-100" type="text">
										</div>
									</div>
									<br>

									<div class="row px-2 mb-3">
										<div class="col-md-2">
											<label class="form-label">Year of Trade In</label>
											<select id='82040_YOTI' class="form-control w-100">
												<option selected>ONE</option>
												<option>TWO</option>
											</select>
										</div>
										<div class="col-md-2">
											<label class="form-label">Make of Trade In</label>
											<input id='82040_MOTI' class="form-control w-100" type="text">
										</div>
										<div class="col-md-3">
											<label class="form-label">Title Number of Trade In (If known)' </label>
											<input id='82040_TNOTI' class="form-control w-100" type="text">
										</div>
										<div class="col-md-5">
											<label class="form-label">Vehicle Identification Number (VIN) of
												Trade In</label>
											<input id='82040_VINOTI' class="form-control w-100" type="text">
										</div>
									</div>

								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="col-md-12">

									<h6 class="p-2 custom-bg-grey">Section 8: MOTOR VEHICLE IDENTIFICATION
										NUMBER VERIFICATION </h6>
									<hr class="m-0">
									<br>
									<p class="p-2">
										This section requires a physical inspection and a verification of the
										vehicle identification number (VIN) (or the motor number for motor
										vehicles manufactured prior to
										1955) of the motor vehicle described on this form by a licensed Florida
										dealer, Florida notary public, law enforcement officer, or authorized
										FLHSMV, tax collector
										(TC) or license plate agency (LPA) employee.
										<b>
											Complete this section on all used motor vehicles, including trailer
											(with abbreviation of “TL” and a weight of 2,000lbs or more), not
											currently titled in Florida
										</b>
									</p>
									<h6 class="p-2 custom-bg-grey">I, the undersigned, certify that I have
										physically inspected the above-described vehicle: </h6>
									<div class="row px-2">
										<div class="col-md-4">
											<label class="form-label">Vehicle Identification Number (VIN)</label>
											<input id='82040_VIN3' class="form-control w-100" type="text">
										</div>
										<div class="col-md-3">
											<label class="form-label">Name Certifying Inspector</label>
											<input id='82040_NCI' class="form-control w-100" type="text">
										</div>
										<div class="col-md-3">
											<label class="form-label">Certifying Inspector Signature' </label>
											<input id='82040_CIS' class="form-control w-100" type="text">
										</div>
										<div class="col-md-2">
											<label class="form-label">Date</label>
											<input id='82040_DATE1' class="form-control w-100" type="date">
										</div>
									</div>

									<div class="row px-2 mt-3">
										<div class="col-md-8">
											<div class="row px-2">
												<div class="col-md-4">
													<input type="checkbox" id='82040_CB_LAWE'>
													Law Enforcement
												</div>
												<div class="col-md-4">
													<label class="form-label">Agency Name:</label>
													<input id='82040_AN1' class="form-control w-100" type="text">
												</div>
												<div class="col-md-4">
													<label class="form-label">Badge Number:</label>
													<input id='82040_BN1' class="form-control w-100" type="number">
												</div>
											</div>
											<div class="row px-2">
												<div class="col-md-4">
													<input type="checkbox" id='82040_CB_FDEALER'>
													Florida Dealer
												</div>
												<div class="col-md-4">
													<label class="form-label">Dealer Name:</label>
													<input id='82040_DNAME' class="form-control w-100" type="text">
												</div>
												<div class="col-md-4">
													<label class="form-label">Dealer Number:</label>
													<input id='82040_DNUM' class="form-control w-100" type="text">
												</div>
											</div>
											<div class="row px-2">
												<div class="col-md-4">
													<input type="checkbox" id='82040_CB_FLHS'>
													FLHSMV
												</div>
												<div class="col-md-4">
													<label class="form-label">Office Name:</label>
													<input id='82040_ONAME' class="form-control w-100" type="text">
												</div>
												<div class="col-md-4">
													<label class="form-label">User ID/Badge:</label>
													<input id='82040_UID' class="form-control w-100" type="number">
												</div>
											</div>
											<div class="row px-2">
												<div class="col-md-4">
													<input type="checkbox" id='82040_CB_TCOLPA'>
													Tax Collector or License Plate Agency
												</div>
												<div class="col-md-4 mb-3">
													<label class="form-label">Agency Name:</label>
													<input id='82040_ANAME' class="form-control w-100" type="text">
												</div>
												<div class="col-md-4">
													<label class="form-label">County/Agency:</label>
													<select id='82040_AGENCY' class="form-control w-100">
														<option selected>ONE</option>
														<option>TWO</option>
													</select>
												</div>
											</div>
										</div>
										<div class="col-md-4 flex-column justify-container-between">
											<input type="checkbox" id='82040_CB_TCOLPA'> Tax Collector or License Plate Agency

											<span>
												<label class="form-label">Signature</label>
												<input id='82040_SIGN' class="form-control w-100" type="text">
											</span>
										</div>
									</div>

								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="col-md-12">

									<h6 class="p-2 custom-bg-grey">Section 9: SALES TAX EXEMPTION CERTIFICATION
										(If applicable)</h6>
									<hr class="m-0">
									<br>
									<p class="p-2">
										<b>
											The purchase of a recreational vehicle to be offered for rent as
											living accommodations does not qualify for exemption. I certify the
											motor vehicle
											described has been purchased and is exempt from the sales tax
											imposed by Chapter 212, Florida Statutes, by:
										</b>
									</p>
									<div class="row px-2">
										<div class="col-md-6">
											<input type="checkbox" id='82040_CB_LAWENF'>
											Law Enforcement
											<div class="d-flex w-100">
												<label class="form-label">Consumer’s Certificate of Exemption
													Number: </label>
												<input id='82040_CCOEN' class="form-control w-100" type="text">
											</div>
										</div>
										<div class="col-md-6">
											<input type="checkbox" id='82040_CB_TCOLPA1'>
											Tax Collector or License Plate Agency
											<div class="d-flex w-100">
												<label class="form-label">Sales Tax Registration Number:</label>
												<input id='82040_STRN' class="form-control w-100" type="text">
											</div>
										</div>
									</div>
									<br>
									<hr class="m-0">
									<div class="row px-2 mt-2">
										<div class="col-md-12">
											<p>
												I hereby certify that ownership of the motor vehicle described
												on this application, is not subject to Florida Sales and Use Tax
												for the following reason:
											</p>
										</div>
										<div class="col-md-2">
											<input type="checkbox" id='82040_CB_INHE'>
											Inheritance
										</div>
										<div class="col-md-1">
											<input type="checkbox" id='82040_CB_GIFT12'>
											Gift
										</div>
										<div class="col-md-2">
											<input type="checkbox" id='82040_CB_DDEC'>
											Divorce Decree
										</div>
										<div class="col-md-3">
											<input type="checkbox" id='82040_CB_TBAMC'>
											Transfer between a married couple
										</div>
										<div class="col-md-1">
											<input type="checkbox" id='82040_CB_OTHER44'>
											Other
										</div>
										<div class="col-md-3">
											<div class="d-flex w-100">
												<input id='82040_OTHERS1' class="form-control w-100" type="text">
											</div>
										</div>
										<div class="col-md-3 mt-2">
											<input type="checkbox" id='82040_CB_ETTD'>
											Even trade or trade down
										</div>
										<div class="col-md-9 mt-2">
											<div class="d-flex w-100">
												<input id='82040_ETOTD' class="form-control w-100" type="text">
											</div>
											<p class="text-center">
												(State the facts of the even trade or trade down and the
												transferor information, including the transferor’s name and
												address.)
											</p>
										</div>
									</div>

								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="col-md-12">

									<h6 class="p-2 custom-bg-grey">Section 10: REPOSSESSION DECLARATION</h6>
									<hr class="m-0">
									<br>

									<div class="col-md-12">
										<input type="checkbox" id='82040_CB_ICTTM' style="display: inline-block; vertical-align: middle;">
										<p style="display: inline-block;"> I certify that this motor vehicle was repossessed upon default in the terms of the lien instrument and is now in my possession.</p>
									</div>

								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="col-md-12">

									<h6 class="p-2 custom-bg-grey">Section 11: NON-USE AND OTHER CERTIFICATIONS
									</h6>
									<hr class="m-0">
									<br>
									<div class="col-md-12">
										<p>
											If checked, the following certifications are made by the applicant:
										</p>
									</div>
									<div class="col-md-12">
										<p>
											<input type="checkbox" id='82040_CB_ITILOD' style="display: inline-block;">
											I certify that the certificate of title is lost or destroyed.
											<br>
											<input type="checkbox" id='82040_CB_TVOTSR'>
											The vehicle identified will not be operated on the streets and
											highways of this state until properly registered.
										</p>
									</div>
									<div class="col-md-12 mb-3">
										<div class="w-100 d-flex align-items-baseline">
											<input type="checkbox" id='82040_CB_OTHER55' style="display: inline-block; ">
											Other:
											<small> (explain) </small>
											<input id='82040_OTHER3' class="form-control" type="text" />
										</div>
									</div>

								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="col-md-12">

									<h6 class="p-2 custom-bg-grey">Section 12: APPLICATION ATTESTMENT AND
										SIGNATURES</h6>
									<hr class="m-0">
									<br>
									<p class="p-2">
										<b>
											I/We physically inspected the VIN.
										</b>
										<small>(More than one form HSMV 82040 may be used for additional
											signatures.)</small>
										<br>
										<b class="mt-2">Under penalties of perjury, I declare that I have read the foregoing
											document and that the facts stated in it are true.</b>
									</p>
									<div class="row px-2">
										<div class="col-md-6">
											<label class="form-label">Full Name of Applicant, Owner:</label>
											<input id='82040_FNOAO' class="form-control w-100" type="text">
										</div>
										<div class="col-md-4">
											<label class="form-label">Signature of Applicant, Owner:</label>
											<input id='82040_SOAO1' class="form-control w-100" type="text">
										</div>
										<div class="col-md-2">
											<label class="form-label">Date</label>
											<input id='82040_DATE4' class="form-control w-100" type="date">
										</div>
									</div>
									<div class="row px-2 mt-2">
										<div class="col-md-6">
											<label class="form-label">Full Name of Applicant,Co-Owner:</label>
											<input id='82040_FNOACO' class="form-control w-100" type="text">
										</div>
										<div class="col-md-4 mt-2">
											<label class="form-label">Signature of Applicant, Co-Owner:</label>
											<input id='82040_SOACO' class="form-control w-100" type="text">
										</div>
										<div class="col-md-2 mt-2">
											<label class="form-label">Date</label>
											<input id='82040_DATE6' class="form-control w-100" type="date">
										</div>
									</div>

								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="col-md-12">

									<h6 class="p-2 custom-bg-grey">Section 13: RELEASE OF SPOUSE OR HEIRS
										INTEREST (If applicable) </h6>
									<hr class="m-0">
									<br>
									<div class="d-flex w-100 px-2 pb-2 align-items-baseline justify-container-evenly">
										<span style="margin-right: 1%;">
											The undersigned person(s) state(s) that
										</span>
										<input id='82040_TUPST' class="form-control w-25" placeholder="Name of deceased"
											type="text">
										<span style="margin-left: 1%; margin-right: 1%;">
											died on
										</span>
										<input id='82040_DIED' class="form-control w-25" placeholder="Date" type="date">
									</div>
									<div class="row px-2">
										<div class="col-md-4">
											<input type="checkbox" id='82040_CB_TEST'>
											Testate (with a will)
										</div>
										<div class="col-md-8">
											<input type="checkbox" id='82040_CB_INTES'>
											Intestate (without a will) and left the surviving heir(s)
											named below.
										</div>
										<div class="col-md-12">
											<input type="checkbox" id='82040_CB_WATH'>
											When applicable, the heir(s) (named below) certifies that the
											certificate of title is lost or destroyed.
										</div>
									</div>
									<h6 class="p-2 custom-bg-grey">
										Under penalties of perjury, I declare that I have read the foregoing
										document and that the facts stated in it are true.
										<small>
											(More than one form HSMV 82040 may be used for additional
											signatures.)
										</small>
									</h6>
									<br>
									<hr class="m-0">

									<div class="px-2 row">
										<div class="col-md-6 pt-3">
											<label class="form-label">Full Name of</label>
											<input type="checkbox" id='82040_CB_SPOUSE'>
												<span style="margin-left: 1%; margin-right: 1%; ">Spouse,</span>
												<input type="checkbox" id='82040_CB_OWNER'>
												<span style="margin-left: 1%; margin-right: 1%;">Co-Owner or</span>
												<input type="checkbox" id='82040_CB_HEIR'>
												<span style="margin-left: 1%; ">Heir(s)</span>
												<input id='82040_SOSCOH' type="text" class="form-control w-100" />
										</div>
										<div class="col-md-4 pt-3">
											<label class="form-label">Signature of Spouse, Co-Owner or Heir(s) </label>
											<input id='82040_SOA8' type="text" class="form-control w-100" />
										</div>
										<div class="col-md-2 pt-3">
											<label class="form-label">Date</label>
											<input id='82040_DATE7' type="date" class="form-control w-100" />
										</div>
										<div class="col-md-6 pt-3">
											<label class="form-label">Full Name of</label>
											<input type="checkbox" id='82040_CB_SPOUSE1'>
												<span style="margin-left: 1%; margin-right: 1%; ">Spouse,</span>
												<input type="checkbox" id='82040_CB_OWNER1'>
												<span style="margin-left: 1%; margin-right: 1%;">Co-Owner or</span>
												<input type="checkbox" id='82040_CB_HEIR1'>
												<span style="margin-left: 1%; ">Heir(s)</span>
												<input id='82040_S13' type="text" class="form-control w-100" />
										</div>
										<div class="col-md-4 pt-3">
											<label class="form-label">Signature of Spouse, Co-Owner or Heir(s) </label>
											<input id='82040_SOSCOH1' type="text" class="form-control w-100" />
										</div>
										<div class="col-md-2 pt-3">
											<label class="form-label">Date</label>
											<input id='82040_DATE8' type="date" class="form-control w-100" />
										</div>
									</div>
									<br>
									<h6 class="p-2 custom-bg-grey">
										<b>
											That at the time of death the decedent was owner of the motor
											vehicle described in section 2 of this form. The person(s) signing
											above hereby releases
											all of his/her/their right, title, interest and claim as heir(s) at
											law, legatee(s), devisee(s), or otherwise to the aforesaid motor
											vehicle to:
										</b>
									</h6>
									<br>
									<hr class="m-0">
									<div class="px-2 row">
										<div class="col-md-6 pt-3">
											<label class="">Full Name of Applicant</label>
											<input id='82040_FNOA1' type="text" class="form-control w-100" />
										</div>
										<div class="col-md-4 pt-3">
											<label class="form-label">Signature of Applicant' </label>
											<input id='82040_SOA8' type="text" class="form-control w-100" />
										</div>
										<div class="col-md-2 pt-3">
											<label class="form-label">Date</label>
											<input id='82040_DATE11' type="date" class="form-control w-100" />
										</div>
										<div class="col-md-6 pt-3">
											<label class="form-label">Full Name of Applicant</label>
											<input id='82040_FNOA11' type="text" class="form-control w-100" />
										</div>
										<div class="col-md-4 pt-3">
											<label class="form-label">Signature of Applicant' </label>
											<input id='82040_SOA12' type="text" class="form-control w-100" />
										</div>
										<div class="col-md-2 pt-3">
											<label class="form-label">Date</label>
											<input id='82040_DATE12' type="date" class="form-control w-100" />
										</div>
									</div>
									<br>
									<hr class="m-0">
									<!-- Botones -->
									<div class="m-t-10 col-md-12">
										<button type="submit" class="btn btn-success btn-sm m-r-10">
											<i class="fa fa-save"> </i>
											SAVE
										</button>
										<button type="button" class="btn btn-danger btn-sm">
											<i class="fa fa-times-circle"> </i>
											CANCEL
										</button>
									</div>
									<br>
								</div>
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>

@push('scripts')
<script src="{{asset('assets/js/bootstrap/popper.min.js')}}"></script>
<script src="{{asset('assets/js/bootstrap/bootstrap.min.js')}}"></script>
@endpush

@endsection