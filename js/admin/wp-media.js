(function($){

$(document).ready(function(){
	$.extend( wp.Uploader.prototype, {
		success: function( mediaModel ){
			if ( mediaModel.get('duplicate_upload') ) {

				var prevSync = wp.media.model.Attachment.prototype.sync,
					id = mediaModel.get('id');

				// prevent sync
				wp.media.model.Attachment.prototype.sync = function(){};
				mediaModel.destroy();
				wp.media.model.Attachment.prototype.sync = prevSync;

				// add message ... Where? How?

				// select attachment
				wp.media.frame.state().get('selection').add( wp.media.attachment( id ) );
			}
		}
	});

})


})(jQuery)
