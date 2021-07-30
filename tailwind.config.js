module.exports = {
  purge: [
    './storage/framework/views/*.php',
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  darkMode: false, // or 'media' or 'class'
  theme: {
    extend: {
      colors: {
        ubcblue: {
          DEFAULT: '#002145',
          500: '#0055B7',
          400: '#00A7E1',
          300: '#40B4E5',
          200: '#6EC4E8',
          100: '#97D4E9',
        }
      }
    },
  },
  variants: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
  // mark all utilities as important, this simplifies specificity issues by
  // letting us know that local utilities should be the final say
  important: true
}
