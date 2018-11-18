(function($){

$(document).ready(function(){
	var origSuccess = wp.Uploader.prototype.success;
	$.extend( wp.Uploader.prototype, {
		success: function( mediaModel ){

			if ( mediaModel.get('duplicate_upload') ) {

				var prevSync = wp.media.model.Attachment.prototype.sync;

				// timeout due to post thumbnail select
				setTimeout(function(){
					var id = mediaModel.get('id');
					// prevent sync
					wp.media.model.Attachment.prototype.sync = function(){};
					mediaModel.destroy();
console.log(mediaModel,id)
					wp.media.model.Attachment.prototype.sync = prevSync;

					// select attachment
					wp.media.frame.state().get('selection').add( wp.media.attachment( id ) );
				},50);

				// add message ... Well ... for now we just shut up.
			}

			return origSuccess.apply(this,arguments);

		}
	});

})


})(jQuery)
