var autoprefixer = require('gulp-autoprefixer');
var concat = require('gulp-concat');
var gulp = require('gulp');
var gulputil = require('gulp-util');
var rename = require('gulp-rename');
var sass = require('gulp-sass');
var sourcemaps = require('gulp-sourcemaps');
var uglify = require('gulp-uglify');

function do_scss( src ) {
	var dir = src.substring( 0, src.lastIndexOf('/') );
	return gulp.src( './src/scss/' + src + '.scss' )
		.pipe( sourcemaps.init() )
		.pipe( sass( { outputStyle: 'nested' } ).on('error', sass.logError) )
		.pipe( autoprefixer({
			browsers:['last 2 versions']
		}) )
		.pipe( gulp.dest( './css/' + dir ) )
        .pipe( sass( { outputStyle: 'compressed' } ).on('error', sass.logError) )
		.pipe( rename( { suffix: '.min' } ) )
        .pipe( sourcemaps.write() )
        .pipe( gulp.dest( './css/' + dir ) );

}

function do_js( src ) {
	var dir = src.substring( 0, src.lastIndexOf('/') );
	return gulp.src( './src/js/' + src + '.js' )
		.pipe( sourcemaps.init() )
		.pipe( gulp.dest( './js/' + dir ) )
		.pipe( uglify() )
		.pipe( rename( { suffix: '.dev' } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( './js/' + dir ) );
}

function concat_js( src, dest ) {
	return gulp.src( src )
		.pipe( sourcemaps.init() )
		.pipe( uglify() )
		.pipe( concat( dest ) )
		.pipe( gulp.dest( './js/' ) )
		.pipe( rename( { suffix: '.dev' } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( './js/' ) );

}


// scss tasks

// scss admin tasks

// scss
// gulp.task('scss', gulp.parallel(
// ));

// admin js

gulp.task( 'js:admin', function(){
	return do_js( 'admin/wp-media' );
} );

gulp.task( 'js:frontend', function(){
	return concat_js( [
	], 'frontend.js');
} );

gulp.task('js', gulp.parallel( 'js:admin' ) );

gulp.task('build', gulp.parallel('js') );


gulp.task('watch', function() {
	// place code for your default task here
	//gulp.watch('./src/scss/**/*.scss',gulp.parallel( 'scss' ));
	gulp.watch('./src/js/**/*.js',gulp.parallel( 'js' ) );
});

gulp.task('dev', gulp.parallel('watch') );

gulp.task('default', gulp.parallel('build','watch'));

