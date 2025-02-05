<?php
/**
 *
 */

// https://app.beta.openthc.com/pub/b2b/01JJW7TC4E833DTE8PQN86G1YK/wcia.json
// https://openthc.pub/Y6SCMwZu5WWOQRbpwRRbnqjJhMaNwmu44WmDq0jADD0/wcia.json
// https://u35080139.ct.sendgrid.net/ls/click?upn=u001.2eQxAe-2BT3KYJRLMx-2Fa1CgygZFwCYXidBnJwiB-2F3wI-2Fs7o4YJ2qmwVmZrvJljvhP76K2k90msLWv8PiNAABaccEivRGAbEh-2Fg16r3MgiLOHQnpkBd9lg1ari0IcgUKP7PREwo_H-2BKmeYI2FXJCpe9GrCGPsxYxASdSJFGz4eajxEqqb4J8-2FLt1-2FHTyIsNRL7gNVzZnCL7pvsHMt0b6Z3SvMpRG4K63Cr5PsEfnnJ4Uizl-2FlnNRKgFsI0jGjACy0We1TczQlKz32yX1bpO9h8VoS8zdl8asE0tvNEkvEU7PdPNIs1mGeDqG8WRpODSZTYWShtF-2FMkoXem0UU-2BduAu3AWtM52w-3D-3D

$link = '';
if ( ! empty($_GET['source'])) {
	$x = trim($_GET['source']);
	if (preg_match('/^http/', $x)) {
		$link = $x;
	}
}


?>


<form autocomplete="off"
	enctype="multipart/form-data"
	id="wcia-form"
	method="post"
	hx-encoding="multipart/form-data"
	hx-post="/wcia">
<div class="container">

	<div class="card mt-4">
		<div class="card-header"><?= $this->data['page_title'] ?></div>
		<div class="card-body">

			<p>Upload either a link or a file and the system will process it.</p>
			<p>You can upload <strong>Transfer</strong> or <strong>Lab Result</strong> documents.</p>

			<div class="mb-2">
				<div class="input-group">
					<div class="input-group-text">Link:</div>
					<input class="form-control" name="wcia-link" value="<?= __h($link) ?>">
				</div>
			</div>

			<div class="mb-2">
				<div class="input-group">
					<div class="input-group-text">File:</div>
					<input class="form-control" type="file" name="wcia-file">
				</div>
			</div>

		</div>
		<div class="card-footer">
			<button class="btn btn-primary"
				hx-on:click="show_loading_spinner()"
				hx-post="/wcia"
				hx-target="#wcia-validator-result"><i class="fa-regular fa-circle-check"></i> Validate</button>
		</div>
	</div>

	<!-- <progress id="progress" value='0' max='100'></progress> -->

	<div class="mt-4" id="wcia-validator-result"></div>

</div>
</form>


<script>
function show_loading_spinner()
{
	// <!-- <i class="fa-solid fa-fan"></i> -->
	$('#wcia-validator-result').empty();
	$('#wcia-validator-result').html('<h2 class="text-bg-dark rounded p-4"><i class="fa-solid fa-arrows-rotate fa-spin"></i> Loading...</h2>');
}
$(function() {
	$('#wcia-validator-result').on('click', '#http-header-frob', function() {
		console.log('SHOW/HIDE');
	});

});
// htmx.on('#wcia-form', 'htmx:xhr:progress', function(evt) {
// 	htmx.find('#progress').setAttribute('value', evt.detail.loaded/evt.detail.total * 100)
//});
</script>
