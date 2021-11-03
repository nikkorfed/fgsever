const gulp = require("gulp"),
  pug = require("gulp-pug"),
  sourcemaps = require("gulp-sourcemaps"),
  sass = require("gulp-sass"),
  postcss = require("gulp-postcss"),
  at2x = require("postcss-at2x"),
  autoprefixer = require("autoprefixer"),
  assets = require("postcss-assets"),
  inlinesvg = require("postcss-inline-svg"),
  cssnano = require("cssnano"),
  babel = require("gulp-babel"),
  concat = require("gulp-concat"),
  terser = require("gulp-terser"),
  imageResize = require("gulp-image-resize"),
  imagemin = require("gulp-imagemin"),
  cache = require("gulp-cache"),
  rename = require("gulp-rename"),
  connect = require("gulp-connect-php"),
  browserSync = require("browser-sync"),
  del = require("del");

function html() {
  return gulp
    .src("src/pages/**/*.pug")
    .pipe(pug({ basedir: "src/template", locals: { time: +new Date() } }))
    .pipe(rename((path) => (path.extname = ".php")))
    .pipe(gulp.dest("dev"));
}

function styles(cb) {
  gulp
    .src("src/styles/libraries/*.css")
    .pipe(concat("libraries.css"))
    .pipe(postcss([cssnano()]))
    .pipe(gulp.dest("dev/styles"));
  gulp
    .src("src/styles/*.sass")
    // .pipe(sourcemaps.init())
    .pipe(sass({ includePaths: ["src/styles"] }))
    .pipe(
      postcss([
        at2x(),
        autoprefixer(),
        assets({ basePath: "src/" }),
        inlinesvg({ paths: ["src/"], removeFill: true }),
        cssnano(),
      ])
    )
    // .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest("dev/styles"));
  return cb();
}

function scripts(cb) {
  gulp
    .src(["src/scripts/javascript/libraries/jquery.min.js", "src/scripts/javascript/libraries/*.js"])
    .pipe(concat("libraries.js"))
    .pipe(terser())
    .pipe(gulp.dest("dev/scripts/javascript"));
  gulp
    .src("src/scripts/javascript/*.js")
    // .pipe(sourcemaps.init())
    // .pipe(babel({ presets: ['@babel/env'] }))
    .pipe(concat("main.js"))
    // .pipe(terser())
    // .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest("dev/scripts/javascript"));
  gulp.src("src/scripts/php/**/*").pipe(gulp.dest("dev/scripts/php"));
  return cb();
}

function images(cb) {
  // Обработка фотографий моделей авто
  gulp
    .src("src/images/models/**/*.png")
    .pipe(imagemin())
    .pipe(rename((path) => (path.basename = path.basename.replace(/\s/g, "-"))))
    .pipe(gulp.dest("dev/images/models"));

  // Создание обычных версий изображений для шапок страниц
  gulp
    .src("src/images/**/main.{jpg,jpeg,JPG}")
    .pipe(imageResize({ percentage: 50, format: "jpg", imageMagick: true }))
    .pipe(imagemin())
    .pipe(rename((path) => (path.basename = path.basename.replace(/\s/g, "-"))))
    .pipe(gulp.dest("dev/images"));

  // Создание Retina версий изображений для шапок страниц
  gulp
    .src("src/images/**/main.{jpg,jpeg,JPG}")
    .pipe(imageResize({ format: "jpg", imageMagick: true }))
    .pipe(imagemin())
    .pipe(rename((path) => (path.basename = path.basename.replace(/\s/g, "-") + "@2x")))
    .pipe(gulp.dest("dev/images"));

  // Создание обычных фотографий
  gulp
    .src(["src/images/**/*.{jpg,jpeg,JPG,png}", "!src/images/models/**/*.png", "!src/images/**/main.{jpg,jpeg,JPG}"])
    .pipe(imageResize({ width: 400, height: 400, cover: true, format: "jpg", imageMagick: true }))
    .pipe(imagemin())
    .pipe(rename((path) => (path.basename = path.basename.replace(/\s/g, "-") + "-min")))
    .pipe(gulp.dest("dev/images"));

  // Создание Retina фотографий
  gulp
    .src(["src/images/**/*.{jpg,jpeg,JPG,png}", "!src/images/models/**/*.png", "!src/images/**/main.{jpg,jpeg,JPG}"])
    .pipe(imageResize({ width: 800, height: 800, cover: true, format: "jpg", imageMagick: true }))
    .pipe(imagemin())
    .pipe(rename((path) => (path.basename = path.basename.replace(/\s/g, "-") + "-min@2x")))
    .pipe(gulp.dest("dev/images"));

  // Сжатие и перенос полных фотографий
  gulp
    .src(["src/images/**/*.{jpg,jpeg,JPG,png}", "!src/images/models/**/*.png", "!src/images/**/main.{jpg,jpeg,JPG}"])
    .pipe(imageResize({ format: "jpg", imageMagick: true }))
    .pipe(imagemin())
    .pipe(rename((path) => (path.basename = path.basename.replace(/\s/g, "-"))))
    .pipe(gulp.dest("dev/images"));

  // Перенос фавиконок
  gulp.src("src/images/favicon/*").pipe(imagemin()).pipe(gulp.dest("dev/images/favicon"));

  return cb();
}

function videos() {
  return gulp.src("src/videos/**/*").pipe(gulp.dest("dev/videos"));
}

function fonts() {
  return gulp.src("src/fonts/**/*").pipe(gulp.dest("dev/fonts"));
}

function data() {
  return gulp.src("src/data/**/*").pipe(gulp.dest("dev/data"));
}

function serve(cb) {
  connect.closeServer();
  connect.server({
    base: "dev",
    keepalive: true,
    port: 3000,
  });
  browserSync({
    proxy: "localhost:3000",
    port: "8080",
    notify: false,
  });
  gulp.watch(["src/template/**/*", "src/pages/**/*"], gulp.series(html, reload));
  gulp.watch("src/styles/**/*", gulp.series(styles, reload));
  gulp.watch("src/scripts/**/*", gulp.series(scripts, reload));
  // gulp.watch('src/images/**/*', gulp.series(images, reload));
  gulp.watch("src/videos/**/*", gulp.series(videos, reload));
  gulp.watch("src/fonts/**/*", gulp.series(fonts, reload));
  gulp.watch("src/data/**/*", gulp.series(data, reload));
  return cb;
}

function reload(cb) {
  browserSync.reload();
  cb();
}

function clean() {
  return del("dev");
}

build = gulp.series(videos, fonts, data, html, styles, scripts);

exports.default = gulp.series(clean, build, serve);
exports.html = gulp.series(clean, html);
exports.images = gulp.series(images);
