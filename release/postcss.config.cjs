const autoprefixer = require('autoprefixer').default || require('autoprefixer');
const purgecss = require('@fullhuman/postcss-purgecss').default || require('@fullhuman/postcss-purgecss');
const postcssScss = require('postcss-scss');

module.exports = {
  parser: postcssScss,
  plugins: [
    autoprefixer({ grid: true, cascade: false }),
    purgecss({
      content: [
        './resources/**/*.blade.php',
        './resources/views/**/*.blade.php',
        './resources/**/*.vue',
        './resources/js/**/*.js',
        './resources/css/**/*.css',
        './routes/**/*.php',
        './public/**/*.html',
      ],
      defaultExtractor: (content) => content.match(/[A-Za-z0-9-_:/]+/g) || [],
      enabled: process.env.NODE_ENV === 'production',
      safelist: {
        standard: [],
        deep: [],
        greedy: [],
      },
    }),
  ],
};