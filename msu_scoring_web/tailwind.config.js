/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./*.php",
    "./api/*.php",
    "./js/*.js"
  ],
  theme: {
    extend: {
      colors: {
        yellow: {
          50:  '#FEF9C3',
          100: '#FEF08A',
          400: '#FACC15',
          500: '#EAB308',
          600: '#CA8A04',
          700: '#A16207',
          800: '#854D0E',
          900: '#713F12',
        }
      },
      fontFamily: {
        sans: ['Inter', 'Sarabun', 'system-ui', 'sans-serif'],
        thai: ['Sarabun', 'Inter', 'system-ui', 'sans-serif'],
        mono: ['ui-monospace', 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', 'monospace']
      }
    },
  },
  plugins: [],
}
