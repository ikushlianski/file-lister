var gulp       = require('gulp'),
	sass         = require('gulp-sass'),
	browserSync  = require('browser-sync'),
	concat       = require('gulp-concat'),
    sourcemaps   = require('gulp-sourcemaps'),
	uglify       = require('gulp-uglifyjs'),
	cssnano      = require('gulp-cssnano'),
	rename       = require('gulp-rename'),
	del          = require('del'),
	imagemin     = require('gulp-imagemin'),
	pngquant     = require('imagemin-pngquant'),
	cache        = require('gulp-cache'),
	autoprefixer = require('gulp-autoprefixer');
	const gulpbabel = require('gulp-babel');
	var babel = require("babel-core");


gulp.task('sass', function(){
	return gulp.src('./sass/materialize.scss')
        .pipe(sourcemaps.init())
		.pipe(sass())
		.pipe(autoprefixer(['last 15 versions', '> 1%', 'ie 8', 'ie 7'], { cascade: true }))
		.pipe(cssnano())
        .pipe(sourcemaps.write('./'))
		.pipe(gulp.dest('./css'))
		.on('error', function(errorInfo) {
			console.log(errorInfo.toString());
			// this.emit('end');
		})
		.pipe(browserSync.reload({stream: true}))
});

gulp.task('browser-sync', function() {
	browserSync({
		proxy: "epamtask08",
		notify: false
	});
});

gulp.task('scripts', function() {
	return gulp.src('./js/js_dev/**/*.js')
		.pipe(concat('scripts.js'))
		.pipe(gulpbabel({
            presets: ['env']
        }))
		.pipe(uglify())
		.pipe(gulp.dest('./js'))
		.pipe(browserSync.reload({stream: true}))
});


gulp.task('watch', ['browser-sync', 'sass', 'scripts'], function() {
	gulp.watch('./sass/**/*.scss', ['sass']);
	gulp.watch('../App/Views/**/*.html', browserSync.reload);
	gulp.watch('./**/*.php', browserSync.reload);
	gulp.watch('./js/js_dev/**/*.js', ['scripts']);
});


gulp.task('img', function() {
	return gulp.src('./img/img_dev/**/*')
		.pipe(cache(imagemin({
			interlaced: true,
			progressive: true,
			svgoPlugins: [{removeViewBox: false}],
			use: [pngquant()]
		})))
		.pipe(gulp.dest('./img'));
});