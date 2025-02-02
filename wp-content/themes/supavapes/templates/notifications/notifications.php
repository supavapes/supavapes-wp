<?php
?>
<div class="position-fixed ersrv-notification-wrapper p-3" style="z-index: 5; right: 0; bottom: 0;">
	<!-- 
		classes for Bg color
		Notice : bg-warning
		Error  : bg-danger
		success: bg-success

		for Icon
		Notice : <span class="fa fa-exclamation-circle mr-2"></span>
		Error  : <span class="fa fa-skull-crossbones mr-2"></span>
		success: <span class="fa fa-check-circle mr-2"></span>
	-->
	<div class="ersrv-notification toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-delay="6000" data-animation="false">
		<div class="toast-header bg-transparent">
			<span class="ersrv-notification-icon fa mr-2"></span>
			<strong class="ersrv-notification-heading mr-auto"></strong>
			<button type="button" class="ml-2 mb-1 close-notification" data-dismiss="toast" aria-label="Close">
				<span aria-hidden="true">&times;</span>
			</button>
		</div>
		<div class="toast-body ersrv-notification-message"></div>
	</div>
</div>