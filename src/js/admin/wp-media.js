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
						wp.media.model.Attachment.prototype.sync = prevSync;

						// select attachment
						!! wp.media.frame && wp.media.frame.state().get('selection').add( wp.media.attachment( id ) );
					},50);

					// add message ... Well ... for now we just shut up.
				}

				return origSuccess.apply(this,arguments);

			}
		});

	});

	$(document).on('click','.compat-field-wpum-duplicates a[data-id]', function(e){
		e.preventDefault();
		var lib = wp.media.frame.state().get('library'),
			itm = itm=lib.findWhere({id: parseInt( $(this).data('id') ) } );
		/*
		wp.media.frame.trigger('edit:attachment', itm, wp.media.frame );
		/*/
		wp.media.frames.edit.open().trigger( 'refresh', itm );
		//*/
	})

})(jQuery)
