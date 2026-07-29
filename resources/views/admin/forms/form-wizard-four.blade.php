@extends('layouts.admin.master')

@section('title')Form 84491
{{ $title }}
@endsection

@push('css')
@endpush

@section('content')
@component('components.breadcrumb')
@slot('breadcrumb_title')
<h3>Form 84491</h3>
@endslot
<li class="breadcrumb-item">Forms</li>
<li class="breadcrumb-item active">Form 84491</li>
@endcomponent

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="body mt-5">
					<h4 class="text-center">Form 84491</h4>
					<h6 class="text-center mt-2">Additional Sheet </h6>
					<br>
					<div class="col-md-12">
						<div class="row" style="margin-left: 15px !important; margin-right: 15px !important;">
							<div class="col-md-2">
								<input type="checkbox" id='84491_cb_1'>
								REBUILT

							</div>
							<div class="col-md-2">
								<input type="checkbox" id='84491_cb_2'>
								ASPT

							</div>
							<div class="col-md-2">
								<input type="checkbox" id='84491_cb_3'>
								KIT CAR

							</div>
							<div class="col-md-2">
								<input type="checkbox" id='84491_cb_4'>
								OTHER:

							</div>
							<div class="col-4">
								<input id='84491_1' class="form-control w-100" type="text">
							</div>

						</div>
					</div>
					<br>
					<hr class="m-0">
					<br>
					<form [formGroup]="Form82050Group">
						<div class="row" style="margin-left: 15px !important; margin-right: 15px !important;">
							<div class="col-md-12">
								<div class="row mt-2">
									<div class="col-4">
										<h6 class="custom-bg-grey mt-2">SECTION I. DESCRIPTION OF:</h6>
									</div>
									<div class="col-2">
										<input type="checkbox" id='84491_cb_5'>
										Motor Vehicle :

									</div>
									<div class="col-2">
										<input type="checkbox" id='84491_cb_6'>
										Motorcycle:

									</div>
									<div class="col-2">
										<input type="checkbox" id='84491_cb_7'>
										Mobile Home :

									</div>
								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="row">
									<div class="col-1">1.</div>
									<div class="col-2"><input id='84491_year' class="form-control w-100"
											type="number">
									</div>
									<div class="col-2"><input id='84491_make' class="form-control w-100"
											type="text">
									</div>
									<div class="col-3"><input id='84491_id' class="form-control w-100" type="text">
									</div>
									<div class="col-2"><input id='84491_color' class="form-control w-100"
											type="text">
									</div>
									<div class="col-1"><input id='84491_body' class="form-control w-100"
											type="text">
									</div>
									<div class="col-1"><input id='84491_len' class="form-control w-100" type="text">
									</div>
								</div>
								<div class="row">
									<div class="col-1">
										<p class="text-center"></p>
									</div>
									<div class="col-2">
										<p class="text-center">Year</p>
									</div>
									<div class="col-2">
										<p class="text-center">Make</p>
									</div>
									<div class="col-3">
										<p class="text-center">Identification Number</p>
									</div>
									<div class="col-2">
										<p class="text-center">Color</p>
									</div>
									<div class="col-1">
										<p class="text-center">Body</p>
									</div>
									<div class="col-1">
										<p class="text-center">Length</p>
									</div>
								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="row">
									<div class="col-12">
										<h6 class="custom-bg-grey">
											SECTION II. MAJOR COMPONENT PARTS USED IN THE BUILDING/REPAIR
											PROCESS
										</h6>
									</div>
								</div>
								<div class="row mt-4">
									<div class="col-12">
										<table class="table">
											<thead>
												<th>Part</th>
												<th>New</th>
												<th>Used</th>
												<th>Repaired</th>
												<th>Aftermarket</th>
												<th>Homemade</th>
												<th>Source/VIN</th>
											</thead>
											<tbody>
												<tr>
													<td><input id='84491_8' class="form-control w-100" type="text">
													</td>
													<td><input type="checkbox" id='84491_cb_8'>
													</td>
													<td><input type="checkbox" id='84491_cb_9'>
													</td>
													<td><input type="checkbox" id='84491_cb_10'>
													</td>
													<td><input type="checkbox" id='84491_cb_11'>
													</td>
													<td><input type="checkbox" id='84491_cb_12'>
													</td>
													<td><input id='84491_9' class="form-control w-100" type="text">
													</td>
												</tr>
												<tr>
													<td><input id='84491_10' class="form-control w-100" type="text">
													</td>
													<td><input type="checkbox" id='84491_cb_13'>
													</td>
													<td><input type="checkbox" id='84491_cb_14'>
													</td>
													<td><input type="checkbox" id='84491_cb_15'>
													</td>
													<td><input type="checkbox" id='84491_cb_16'>
													</td>
													<td><input type="checkbox" id='84491_cb_17'>
													</td>
													<td><input id='84491_11' class="form-control w-100" type="text">
													</td>
												</tr>
												<tr>
													<td><input id='84491_12' class="form-control w-100" type="text">
													</td>
													<td><input type="checkbox" id='84491_cb_18'>
													</td>
													<td><input type="checkbox" id='84491_cb_19'>
													</td>
													<td><input type="checkbox" id='84491_cb_20'>
													</td>
													<td><input type="checkbox" id='84491_cb_21'>
													</td>
													<td><input type="checkbox" id='84491_cb_22'>
													</td>
													<td><input id='84491_13' class="form-control w-100" type="text">
													</td>
												</tr>
												<tr>
													<td><input id='84491_14' class="form-control w-100" type="text">
													</td>
													<td><input type="checkbox" id='84491_cb_23'>
													</td>
													<td><input type="checkbox" id='84491_cb_24'>
													</td>
													<td><input type="checkbox" id='84491_cb_25'>
													</td>
													<td><input type="checkbox" id='84491_cb_26'>
													</td>
													<td><input type="checkbox" id='84491_cb_27'>
													</td>
													<td><input id='84491_15' class="form-control w-100" type="text">
													</td>
												</tr>
												<tr>
													<td><input id='84491_16' class="form-control w-100" type="text">
													</td>
													<td><input type="checkbox" id='84491_cb_28'>
													</td>
													<td><input type="checkbox" id='84491_cb_29'>
													</td>
													<td><input type="checkbox" id='84491_cb_30'>
													</td>
													<td><input type="checkbox" id='84491_cb_31'>
													</td>
													<td><input type="checkbox" id='84491_cb_32'>
													</td>
													<td><input id='84491_17' class="form-control w-100" type="text">
													</td>
												</tr>
												<tr>
													<td><input id='84491_18' class="form-control w-100" type="text">
													</td>
													<td><input type="checkbox" id='84491_cb_33'>
													</td>
													<td><input type="checkbox" id='84491_cb_34'>
													</td>
													<td><input type="checkbox" id='84491_cb_35'>
													</td>
													<td><input type="checkbox" id='84491_cb_36'>
													</td>
													<td><input type="checkbox" id='84491_cb_37'>
													</td>
													<td><input id='84491_19' class="form-control w-100" type="text">
													</td>
												</tr>
												<tr>
													<td><input id='84491_20' class="form-control w-100" type="text">
													</td>
													<td><input type="checkbox" id='84491_cb_38'>
													</td>
													<td><input type="checkbox" id='84491_cb_39'>
													</td>
													<td><input type="checkbox" id='84491_cb_40'>
													</td>
													<td><input type="checkbox" id='84491_cb_41'>
													</td>
													<td><input type="checkbox" id='84491_cb_42'>
													</td>
													<td><input id='84491_21' class="form-control w-100" type="text">
													</td>
												</tr>
												<tr>
													<td><input id='84491_2' class="form-control w-100" type="text">
													</td>
													<td><input type="checkbox" id='84491_cb_43'>
													</td>
													<td><input type="checkbox" id='84491_cb_44'>
													</td>
													<td><input type="checkbox" id='84491_cb_45'>
													</td>
													<td><input type="checkbox" id='84491_cb_46'>
													</td>
													<td><input type="checkbox" id='84491_cb_47'>
													</td>
													<td><input id='84491_23' class="form-control w-100" type="text">
													</td>
												</tr>
												<tr>
													<td><input id='84491_24' class="form-control w-100" type="text">
													</td>
													<td><input type="checkbox" id='84491_cb_48'>
													</td>
													<td><input type="checkbox" id='84491_cb_49'>
													</td>
													<td><input type="checkbox" id='84491_cb_50'>
													</td>
													<td><input type="checkbox" id='84491_cb_51'>
													</td>
													<td><input type="checkbox" id='84491_cb_52'>
													</td>
													<td><input id='84491_25' class="form-control w-100" type="text">
													</td>
												</tr>
												<tr>
													<td><input id='84491_26' class="form-control w-100" type="text">
													</td>
													<td><input type="checkbox" id='84491_cb_53'>
													</td>
													<td><input type="checkbox" id='84491_cb_54'>
													</td>
													<td><input type="checkbox" id='84491_cb_55'>
													</td>
													<td><input type="checkbox" id='84491_cb_56'>
													</td>
													<td><input type="checkbox" id='84491_cb_57'>
													</td>
													<td><input id='84491_27' class="form-control w-100" type="text">
													</td>
												</tr>
												<tr>
													<td><input id='84491_28' class="form-control w-100" type="text">
													</td>
													<td><input type="checkbox" id='84491_cb_58'>
													</td>
													<td><input type="checkbox" id='84491_cb_59'>
													</td>
													<td><input type="checkbox" id='84491_cb_60'>
													</td>
													<td><input type="checkbox" id='84491_cb_61'>
													</td>
													<td><input type="checkbox" id='84491_cb_62'>
													</td>
													<td><input id='84491_29' class="form-control w-100" type="text">
													</td>
												</tr>
												<tr>
													<td><input id='84491_30' class="form-control w-100" type="text">
													</td>
													<td><input type="checkbox" id='84491_cb_63'>
													</td>
													<td><input type="checkbox" id='84491_cb_64'>
													</td>
													<td><input type="checkbox" id='84491_cb_65'>
													</td>
													<td><input type="checkbox" id='84491_cb_66'>
													</td>
													<td><input type="checkbox" id='84491_cb_67'>
													</td>
													<td><input id='84491_31' class="form-control w-100" type="text">
													</td>
												</tr>
											</tbody>
										</table>
									</div>
								</div>

								<div class="row mt-4">
									<div class="col-12">
										<p>
											<strong> Note:</strong> Major component parts are defined as: For
											motor vehicles
											other than motorcycles, any fender, hood,
											bumper, cowl assembly, rear quarter panel, trunk lid, door, deck
											lid, floor pan,
											engine, frame, transmission,
											catalytic converter or airbag. In addition to the previously
											mentioned: major
											component parts for electric,
											hybrid, or plug-in vehicles or trucks include any electric traction
											motor,
											electronic transmission, charge port,
											DC power converter, onboard charger, power electronics controller,
											thermal system,
											transaction battery
											pack, or airbag.
										</p>
									</div>
								</div>
								<div class="row mt-4">
									<div class="col-12">Please continue to describe any repairs made:</div>
									<div class="col-12"><textarea name="" id="" rows="10"
											class="form-control w-100"></textarea>
									</div>
								</div>
								<br>
								<hr class="m-0">
								<br>
								<div class="row mt-4">
									<div class="col-12">
										<h6 class="custom-bg-grey">SECTION III. TO BE COMPLETED AND SUBMITTED
											WITH ALL
											APPLICATIONS </h6>
									</div>
								</div>
								<br>
								<hr class="m-0">
								<div class="row mt-4">
									<div class="col-1">
										<p>Date</p>
									</div>
									<div class="col-4"><input id='84491_32' class="form-control w-100" type="date">
									</div>
								</div>
								<div class="row mt-4">
									<div class="col-12">
										<p>UNDER PENALTIES OF PERJURY, I DECLARE THAT I HAVE READ THE FOREGOING
											DOCUMENT AND
											THAT THE
											FACTS STATED IN IT ARE TRUE. NO MATERIAL INFORMATION REGARDING THE
											MOTOR VEHICLE,
											MOTORCYCLE, OR MOBILE HOME HAS BEEN OMITTED. </p>
									</div>
								</div>
								<div class="row">
									<div class="col-6"><input id='84491_33' class="form-control w-100" type="text">
									</div>
									<div class="col-6"><input id='84491_34' class="form-control w-100" type="text">
									</div>
								</div>
								<div class="row">
									<div class="col-6">
										<p class="text-center">Printed Name of Applicant/Business</p>
									</div>
									<div class="col-6">
										<p class="text-center">Printed Name of Applicant/Business </p>
									</div>
								</div>
								<div class="row">
									<div class="col-6"><input id='84491_35' class="form-control w-100" type="text">
									</div>
									<div class="col-6"><input id='84491_36' class="form-control w-100" type="text">
									</div>
								</div>
								<div class="row">
									<div class="col-6">
										<p class="text-center">Signature Of Applicant/Business</p>
									</div>
									<div class="col-6">
										<p class="text-center">Signature Of Applicant/Business</p>
									</div>
								</div>
								<br>
								<hr class="m-0">
								<br>
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
							</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

@push('scripts')
<script src="{{asset('assets/js/form-wizard/form-wizard-three.js')}}"></script>
<script src="{{asset('assets/js/form-wizard/jquery.backstretch.min.js')}}"></script>
@endpush

@endsection