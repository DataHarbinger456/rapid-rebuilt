@extends('layouts.admin.master')

@section('title')Form 84490
{{ $title }}
@endsection

@push('css')
@endpush

@section('content')
@component('components.breadcrumb')
@slot('breadcrumb_title')
<h3>Form 84490</h3>
@endslot
<li class="breadcrumb-item">Forms</li>
<li class="breadcrumb-item active">Form 84490</li>
@endcomponent

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<div class="card">
				<div class="body mt-5">

					<h4 class="text-center">Form 84490</h4>
					<h6 class="text-center">STATEMENT OF BUILDER </h6>
					<br>
					<div class="col-md-12 mt-3">
						<div class="row" style="margin-left: 15px !important; margin-right: 15px !important;">
							<div class="col-md-2">
								<input type="checkbox" id='84490_cb_1'>
								REBUILT

							</div>
							<div class="col-md-2">
								<input type="checkbox" id='84490_cb_2'>
								ASPT

							</div>
							<div class="col-md-2">
								<input type="checkbox" id='84490_cb_3'>
								KIT CAR

							</div>
							<div class="col-md-2">
								<input type="checkbox" id='84490_cb_4'>
								OTHER:

							</div>
							<div class="col-md-4">
								<input id='84490_other' class="form-control w-100" type="text">
							</div>
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
									<input type="checkbox" id='84490_cb_5'>
									Motor Vehicle :

								</div>
								<div class="col-2">
									<input type="checkbox" id='84490_cb_6'>
									Motorcycle:

								</div>
								<div class="col-2">
									<input type="checkbox" id='84490_cb_7'>
									Mobile Home :

								</div>
							</div>
							<br>
							<hr class="m-0">
							<br>
							<div class="row">
								<div class="col-1">1.</div>
								<div class="col-2"><input id='84490_year' class="form-control w-100" type="number">
								</div>
								<div class="col-2"><input id='84490_make' class="form-control w-100" type="text">
								</div>
								<div class="col-3"><input id='84490_id' class="form-control w-100" type="text">
								</div>
								<div class="col-2"><input id='84490_color' class="form-control w-100" type="text">
								</div>
								<div class="col-1"><input id='84490_body' class="form-control w-100" type="text">
								</div>
								<div class="col-1"><input id='84490_len' class="form-control w-100" type="text">
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
							<div class="row">
								<div class="col-1">2.</div>
								<div class="col-1"><label for="">Title Number:</label></div>
								<div class="col-4"><input id='84490_tn' class="form-control w-100" type="text">
								</div>
								<div class="col-1"><label for="">Title State:</label></div>
								<div class="col-4"><input id='84490_ts' class="form-control w-100" type="text">
								</div>
							</div>
							<div class="row mt-3">
								<div class="col-1">3.</div>
								<div class="col-1"><label for="">Other/Title Number:</label></div>
								<div class="col-4"><input id='84490_otn' class="form-control w-100" type="text">
								</div>
								<div class="col-1"><label for="">Title State:</label></div>
								<div class="col-4"><input id='84490_ts1' class="form-control w-100" type="text">
								</div>
							</div>
							<div class="row mt-3">
								<div class="col-1">4.</div>
								<div class="col-3">Motor Vehicle/Motorcycle is complete and in roadoperable
									condition.
								</div>
								<div class="col-7"><input id='84490_mic' class="form-control w-100" type="text">
								</div>
								<div class="col-1">(Initials)</div>
							</div>
							<div class="row mt-3">
								<div class="col-1">5.</div>
								<div class="col-3">Mobile Home is habitable for residential orcommercial
									purposes.
								</div>
								<div class="col-7"><input id='84490_mhhr' class="form-control w-100" type="text">
								</div>
								<div class="col-1">(Initials)</div>
							</div>
						</div>
					</div>
					<br>
					<hr class="m-0">
					<br>
					<div class="row" style="margin-left: 15px !important; margin-right: 15px !important;">
						<div class="col-md-12">
							<div class="row mt-3">
								<div class="col-12">
									<h6 class="custom-bg-grey">
										SECTION II. MAJOR COMPONENT PARTS USED IN THE BUILDING/REPAIR PROCESS
									</h6>
								</div>
							</div>
							<br>
							<hr class="m-0">

							<div class="row mt-4">
								<div class="col-12">
									<p>
										<strong> Note:</strong> Major component parts are defined as: For motor
										vehicles
										other than motorcycles, any fender, hood,
										bumper, cowl assembly, rear quarter panel, trunk lid, door, deck lid,
										floor pan,
										engine, frame, transmission,
										catalytic converter or airbag. In addition to the previously mentioned:
										major
										component parts for electric,
										hybrid, or plug-in vehicles or trucks include any electric traction
										motor,
										electronic transmission, charge port,
										DC power converter, onboard charger, power electronics controller,
										thermal
										system,
										transaction battery
										pack, or airbag
									</p>
								</div>
							</div>
							<div class="row mt-4">

								<div class="row" style="margin-left: 10px !important; margin-right: 10px !important;">
									<div class="col-md-12 d-flex align-items-center">
										<p style="text-align: justify;">
											1. - This section <b>IS NOT</b> applicable as the:
										</p>
										<p style="margin-left: 2%;"><input type="checkbox" id='84490_cb_8' class="d-inline"> Motor Vehicle</p>
										<p style="margin-left: 2%;"><input type="checkbox" id='84490_cb_9' class="d-inline"> Motorcycle</p>
										<p style="margin-left: 2%;"><input type="checkbox" id='84490_cb_10' class="d-inline"> Mobile Home</p>

									</div>
									<div class="col-md-12 d-flex align-items-center">
										<p class="mb-0">was purchased from</p>
										<input type="text" id='84490_pc_from' class="form-control mx-2" style="flex: 1;">
										<p class="mb-0">on</p>
										<input id='84490_date_from' class="form-control mx-2" type="date" style="width: auto;">
										<p class="mb-0">in complete rebuilt or ASPT condition.</p>
									</div>
								</div>

								<div class="col-md-12 mt-4">
									<div class="row"
										style="margin-left: 10px !important; margin-right: 10px !important;">
										<p style="text-align: justify;">
											2. - List the major component parts used in the building/repair process
											(if
											additional
											space is needed, please
											use form HSMV 84491).
										</p>
									</div>
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
												<td><input id='84490_p1' class="form-control w-100" type="text">
												</td>
												<td><input type="checkbox" id='84490_cb_11'></td>
												<td><input type="checkbox" id='84490_cb_12'></td>
												<td><input type="checkbox" id='84490_cb_13'></td>
												<td><input type="checkbox" id='84490_cb_14'></td>
												<td><input type="checkbox" id='84490_cb_15'></td>
												<td><input id='84490_v1' class="form-control w-100" type="text">
												</td>
											</tr>
											<tr>
												<td><input id='84490_p2' class="form-control w-100" type="text">
												</td>
												<td><input type="checkbox" id='84490_cb_16'></td>
												<td><input type="checkbox" id='84490_cb_17'></td>
												<td><input type="checkbox" id='84490_cb_18'></td>
												<td><input type="checkbox" id='84490_cb_19'></td>
												<td><input type="checkbox" id='84490_cb_20'></td>
												<td><input id='84490_v2' class="form-control w-100" type="text">
												</td>
											</tr>
											<tr>
												<td><input id='84490_p3' class="form-control w-100" type="text">
												</td>
												<td><input type="checkbox" id='84490_cb_21'></td>
												<td><input type="checkbox" id='84490_cb_22'></td>
												<td><input type="checkbox" id='84490_cb_23'></td>
												<td><input type="checkbox" id='84490_cb_24'></td>
												<td><input type="checkbox" id='84490_cb_25'></td>
												<td><input id='84490_v3' class="form-control w-100" type="text">
												</td>
											</tr>
											<tr>
												<td><input id='84490_p4' class="form-control w-100" type="text">
												</td>
												<td><input type="checkbox" id='84490_cb_26'></td>
												<td><input type="checkbox" id='84490_cb_27'></td>
												<td><input type="checkbox" id='84490_cb_28'></td>
												<td><input type="checkbox" id='84490_cb_29'></td>
												<td><input type="checkbox" id='84490_cb_30'></td>
												<td><input id='84490_v4' class="form-control w-100" type="text">
												</td>
											</tr>
											<tr>
												<td><input id='84490_p5' class="form-control w-100" type="text">
												</td>
												<td><input type="checkbox" id='84490_cb_31'></td>
												<td><input type="checkbox" id='84490_cb_32'></td>
												<td><input type="checkbox" id='84490_cb_33'></td>
												<td><input type="checkbox" id='84490_cb_34'></td>
												<td><input type="checkbox" id='84490_cb_35'></td>
												<td><input id='84490_v5' class="form-control w-100" type="text">
												</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
							<div class="row">
								<div class="col-1">
									3.
								</div>
								<div class="col-11">
									When Section II (1) is not applicable, describe the repairs made in detail.
									(If
									additional space is needed,
									please use form HSMV 84491. Attach the original MSO, bill of sale(s), or
									receipt(s)
									for
									all major
									component parts(must contain name, address, telephone, and signature
									ofseller).
								</div>
							</div>
							<div class="row">
								<div class="col-1">
								</div>
								<div class="col-11">
									<textarea id='84490_r1' class="form-control w-100" type="textarea"></textarea>
								</div>
							</div>

							<div class="row mt-2">
								<div class="col-1">
									4.
								</div>
								<div class="col-2">
									Number of Receipts:
								</div>
								<div class="col-2">
									<input id='84490_nor' class="form-control w-100" type="number">
								</div>
							</div>
						</div>
					</div>
					<br>
					<hr class="m-0">
					<br>
					<div class="row" style="margin-left: 15px !important; margin-right: 15px !important;">
						<div class="col-md-12">
							<div class="row mt-4 ">
								<div class="col-12">
									<h6 class="custom-bg-grey">SECTION III. CUSTOM VEHICLE OR STREET ROD </h6>
								</div>
							</div>
							<br>
							<hr class="m-0">
							<div class="row mt-2">
								<div class="col-12">
									<p>
										The following statements are required to be attested to according to
										section
										320.0863, Florida Statutes.
										Failure to attest to these statements will cause this agency to reject
										your
										application.
									</p>
								</div>
							</div>
							<div class="row mt-2">
								<div class="col-1">
									<input type="checkbox" id='84490_cb_36'>

								</div>
								<div class="col-11">
									The vehicle will not be used for general daily transportation but will be
									maintained
									for
									occasional
									transportation, exhibitions, club activities, parades, tours, or other
									functions of
									public interest and
									similar uses.
								</div>
							</div>
							<div class="row mt-2">
								<div class="col-1">
									<input type="checkbox" id='84490_cb_37'>

								</div>
								<div class="col-11">
									The vehicle meets state equipment and safety requirements for motor vehicles
									that
									were
									in effect in this
									state as a condition of sale in the year listed as the model year on the
									certificate
									of
									title.
								</div>
							</div>
							<br>
							<div class="row mt-2 ">
								<div class="col-12">
									<h6>By checking the boxes above and by signature below, I acknowledge and
										attest to
										the
										statements above as
										my written statement relating to a custom vehicle or street rod. </h6>
								</div>
							</div>
							<br>
							<div class="row">
								<div class="col-8">
									<input id='84490_t1' class="form-control w-100" type="text">
								</div>
								<div class="col-4">
									<input id='84490_t2' class="form-control w-100" type="text">
								</div>
							</div>
							<div class="row">
								<div class="col-8">
									<p class="text-center">
										Signature
									</p>
								</div>
								<div class="col-4">
									<p class="text-center">
										Date
									</p>
								</div>
							</div>
						</div>
					</div>
					<br>
					<hr class="m-0">
					<br>
					<div class="row" style="margin-left: 15px !important; margin-right: 15px !important;">
						<div class="col-md-12">
							<div class="row">
								<div class="col-12">
									<h6 class="custom-bg-grey">SECTION IV. APPLICANT INFORMATION AND SIGNATURE
									</h6>
								</div>
							</div>
							<br>
							<hr class="m-0">
							<br>
							<div class="row">
								<div class="col-1">
									<p>Date : </p>
								</div>
								<div class="col-3"><input id='84490_date' class="form-control w-100" type="date">
								</div>
							</div>
							<div class="row mt-3">
								<div class="col-12">
									<p>
										The undersigned hereby certifies that the vehicle conforms to Florida
										and
										Federal
										Motor Vehicle Safety
										Standards. Under penalties of perjury, I declare that I have read the
										foregoing
										document and that the facts
										provided herein are true. No material information regarding the motor
										vehicle,
										motorcycle, or mobile home
										has been omitted.
									</p>
								</div>
							</div>
							<div class="row">
								<div class="col-6"><input id='84490_q1' class="form-control w-100" type="text">
								</div>
								<div class="col-6"><input id='84490_q2' class="form-control w-100" type="text">
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
								<div class="col-6"><input id='84490_' class="form-control w-100" type="text">
								</div>
								<div class="col-6"><input id='84490_' class="form-control w-100" type="text">
								</div>
							</div>
							<div class="row">
								<div class="col-6">
									<p class="text-center">Street Address</p>
								</div>
								<div class="col-6">
									<p class="text-center">Street Address </p>
								</div>
							</div>
							<div class="row">
								<div class="col-2"><input id='84490_w1' class="form-control w-100" type="text">
								</div>
								<div class="col-2"><input id='84490_w2' class="form-control w-100" type="text">
								</div>
								<div class="col-2"><input id='84490_w3' class="form-control w-100" type="text">
								</div>
								<div class="col-2"><input id='84490_w4' class="form-control w-100" type="text">
								</div>
								<div class="col-2"><input id='84490_w5' class="form-control w-100" type="text">
								</div>
								<div class="col-2"><input id='84490_w6' class="form-control w-100" type="text">
								</div>
							</div>
							<div class="row">
								<div class="col-2">
									<p class="text-center">City</p>
								</div>
								<div class="col-2">
									<p class="text-center">State</p>
								</div>
								<div class="col-2">
									<p class="text-center">Zip</p>
								</div>
								<div class="col-2">
									<p class="text-center">City</p>
								</div>
								<div class="col-2">
									<p class="text-center">State</p>
								</div>
								<div class="col-2">
									<p class="text-center">Zip</p>
								</div>
							</div>
							<div class="row">
								<div class="col-6"><input id='84490_y1' class="form-control w-100" type="text">
								</div>
								<div class="col-6"><input id='84490_y2' class="form-control w-100" type="text">
								</div>
							</div>
							<div class="row">
								<div class="col-6">
									<p class="text-center">Telephone Number(S)</p>
								</div>
								<div class="col-6">
									<p class="text-center">Telephone Number(S)</p>
								</div>
							</div>
							<div class="row">
								<div class="col-6"><input id='84490_y3' class="form-control w-100" type="text">
								</div>
								<div class="col-6"><input id='84490_y4' class="form-control w-100" type="text">
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
						</div>
					</div>
					<br>
					<hr class="m-0">
					<br>
					<div class="row" style="margin-left: 15px !important; margin-right: 15px !important;">
						<div class="col-md-12">
							<div class="row custom-bg-grey">
								<div class="col-6">
									<h6 class="custom-bg-grey">SECTION V. HSMV OFFICE USE ONLY </h6>
								</div>
							</div>
							<br>
							<hr class="m-0">
							<br>
							<div class="row">
								<div class="col-12">
									<p>HSMV CE signature below attests to the VIN verification and vehicle
										inspection
										requirement. PRVIP Inspector attest to
										vehicle inspection only. </p>
								</div>
							</div>
							<div class="row mb-3">
								<div class="col-2">
									<p>VIN </p>
								</div>
								<div class="col-4"><input id='84490_vin' class="form-control w-100" type="text">
								</div>
								<div class="col-2">
									<p>Title Number </p>
								</div>
								<div class="col-4"><input id='84490_tnum' class="form-control w-100" type="text">
								</div>
							</div>
							<div class="row">
								<div class="col-1">
									<p>D-1 </p>
								</div>
								<div class="col-3"><input id='84490_d1' class="form-control w-100" type="text">
								</div>
								<div class="col-2">
									<p>Title State </p>
								</div>
								<div class="col-2"><input id='84490_tsta' class="form-control w-100" type="text">
								</div>
								<div class="col-2">
									<p>Odometer </p>
								</div>
								<div class="col-2"><input id='84490_odo' class="form-control w-100" type="text">
								</div>
							</div>
							<div class="row">
								<div class="col-1">
									<p>D-2 </p>
								</div>
								<div class="col-3"><input id='84490_d2' class="form-control w-100" type="text">
								</div>
								<div class="col-1">
									<p>Year </p>
								</div>
								<div class="col-3"><input id='84490_year1' class="form-control w-100" type="text">
								</div>
								<div class="col-1">
									<p>Make </p>
								</div>
								<div class="col-3"><input id='84490_make1' class="form-control w-100" type="text">
								</div>
							</div>
							<div class="row">
								<div class="col-1">
									<p>D-3 </p>
								</div>
								<div class="col-3"><input id='84490_d3' class="form-control w-100" type="text">
								</div>
								<div class="col-1">
									<p>Body </p>
								</div>
								<div class="col-3"><input id='84490_body1' class="form-control w-100" type="text">
								</div>
								<div class="col-1">
									<p>Color </p>
								</div>
								<div class="col-3"><input id='84490_color1' class="form-control w-100" type="text">
								</div>
							</div>
							<div class="row">
								<div class="col-1">
									<p>D-4 </p>
								</div>
								<div class="col-3"><input id='84490_d4' class="form-control w-100" type="text">
								</div>
								<div class="col-1">
									<p>Audit # </p>
								</div>
								<div class="col-3"><input id='84490_audit' class="form-control w-100" type="text">
								</div>
								<div class="col-1">
									<p>Region # </p>
								</div>
								<div class="col-3"><input id='84490_region' class="form-control w-100" type="text">
								</div>
							</div>

							<table class="table">
								<thead>
									<th>Please mark the appropriate answer</th>
									<th>Yes</th>
									<th>No</th>
									<th>Please mark the appropriate answer </th>
									<th>Yes</th>
									<th>No</th>
								</thead>
								<tbody>
									<tr>
										<td></td>
										<td></td>
										<td></td>
										<td>FRVIS</td>
										<td>
											<input type="checkbox" id='84490_cb_38'>

										</td>
										<td>
											<input type="checkbox" id='84490_cb_39'>

										</td>
									</tr>
									<tr>
										<td>Secondary VIN Verified</td>
										<td>
											<input type="checkbox" id='84490_cb_40'>

										</td>
										<td>
											<input type="checkbox" id='84490_cb_41'>

										</td>
										<td>Previous Rebuilt</td>
										<td>
											<input type="checkbox" id='84490_cb_42'>

										</td>
										<td>
											<input type="checkbox" id='84490_cb_43'>

										</td>
									</tr>
									<tr>
										<td>Federal Decal</td>
										<td>
											<input type="checkbox" id='84490_cb_44'>

										</td>
										<td>
											<input type="checkbox" id='84490_cb_45'>

										</td>
										<td>NICB Check </td>
										<td>
											<input type="checkbox" id='84490_cb_46'>

										</td>
										<td>
											<input type="checkbox" id='84490_cb_47'>

										</td>
									</tr>
									<tr>
										<td>Replacement VIN Plate/Decal </td>
										<td>
											<input type="checkbox" id='84490_cb_48'>

										</td>
										<td>
											<input type="checkbox" id='84490_cb_49'>

										</td>
										<td>Component Parts Marked</td>
										<td>
											<input type="checkbox" id='84490_cb_50'>

										</td>
										<td>
											<input type="checkbox" id='84490_cb_51'>

										</td>
									</tr>
									<tr>
										<td>Vehicle Painted Prior to Inspection </td>
										<td>
											<input type="checkbox" id='84490_cb_52'>

										</td>
										<td>
											<input type="checkbox" id='84490_cb_53'>

										</td>
										<td>Flood Damaged </td>
										<td>
											<input type="checkbox" id='84490_cb_54'>

										</td>
										<td>
											<input type="checkbox" id='84490_cb_55'>

										</td>
									</tr>
									<tr>
										<td>Odometer Replacement Notice </td>
										<td>
											<input type="checkbox" id='84490_cb_56'>

										</td>
										<td>
											<input type="checkbox" id='84490_cb_57'>

										</td>
										<td>Theft</td>
										<td>
											<input type="checkbox" id='84490_cb_58'>

										</td>
										<td>
											<input type="checkbox" id='84490_cb_59'>

										</td>
									</tr>
								</tbody>
							</table>

							<div class="row mt-4">
								<div class="col-md-3">
									This ASPT/Vehicle resembles a:
								</div>
								<div class="col-4">
									<input id='84490_tavr' class="form-control w-100" type="text">
								</div>
								<div class="col-2">Tax Due On:</div>
								<div class="col-3"><input id='84490_tdo' class="form-control w-100" type="text">
								</div>
							</div>
							<div class="row mt-4">
								<div class="col-md-12 d-flex align-items-center">
									<a style="margin-right: 1%;"><b>MOBILE HOME USE ONLY</b> Mobile Home was measured:</a>  
										<div class="form-check me-3">
											<input type="checkbox" id='84490_cb_60' class="form-check-input">
											<label for='84490_cb_60' class="form-check-label">With Tongue</label>
										</div>
										<div class="form-check">
											<input type="checkbox" id='84490_cb_61' class="form-check-input">
											<label for='84490_cb_61' class="form-check-label">Without Tongue</label>
										</div>
								</div>
							</div>
							<div class="row mt-4">
								<div class="col-2">Comments :</div>
								<div class="col-10"><textarea name="" id="" rows="3"
										class="form-control w-100"></textarea></div>
							</div>
							<br>
							<hr class="m-0">
							<br>
							<div class="row mt-4">
								<div class="col-12">
									<h6>Under Penalties of perjury, I declare that I have made an inspection of
										this
										motor vehicle, motorcycle or mobile
										home and completed Section V based on that inspection. </h6>
								</div>
							</div>
							<div class="row mt-4">
								<div class="col-4"><input id='84490_t11' class="form-control w-100" type="text">
								</div>
								<div class="col-4"><input id='84490_t12' class="form-control w-100" type="text">
								</div>
								<div class="col-2"><input id='84490_t13' class="form-control w-100" type="text">
								</div>
								<div class="col-2"><input id='84490_t14' class="form-control w-100" type="text">
								</div>
							</div>
							<div class="row">
								<div class="col-4">
									<p class="text-center">Signature of HSMV <br>Compliance Examiner </p>
								</div>
								<div class="col-4">
									<p class="text-center">Print Name of HSMV <br>Compliance Examiner </p>
								</div>
								<div class="col-2">
									<p class="text-center">Region # </p>
								</div>
								<div class="col-2">
									<p class="text-center">Date</p>
								</div>
							</div>
							<div class="row">
								<div class="col-4"><input id='84490_sign' class="form-control w-100" type="text">
								</div>
								<div class="col-4"><input id='84490_print' class="form-control w-100" type="text">
								</div>
								<div class="col-2"><input id='84490_reg' class="form-control w-100" type="text">
								</div>
								<div class="col-2"><input id='84490_date123' class="form-control w-100" type="text">
								</div>
							</div>
							<div class="row">
								<div class="col-4">
									<p class="text-center">Signature of PRVIP Inspector</p>
								</div>
								<div class="col-4">
									<p class="text-center">Print Name of PRVIP Inspector</p>
								</div>
								<div class="col-2">
									<p class="text-center">Co/Agy # </p>
								</div>
								<div class="col-2">
									<p class="text-center">Date</p>
								</div>
							</div>
						</div>
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
					<br>
					<br>

				</form>
			</div>
		</div>
	</div>
</div>

@push('scripts')
<script src="{{asset('assets/js/form-wizard/form-wizard-three.js')}}"></script>
<script src="{{asset('assets/js/form-wizard/jquery.backstretch.min.js')}}"></script>
@endpush

@endsection