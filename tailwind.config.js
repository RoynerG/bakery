/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.php",
    "./public/**/*.php",
    "./public/**/*.js",
    "./public/**/*.html",
  ],
  theme: {
    extend: {
      fontFamily: {
        display: ['"Quicksand"', 'system-ui', 'sans-serif'],
        sweet:   ['"Pacifico"', 'cursive'],
      },
      colors: {
        cream:    { 50: '#fffaf3', 100: '#fff3e0', 200: '#ffe5b4' },
        rose:     { 50: '#fff0f5', 100: '#ffd6e7', 200: '#ffb3d1', 300: '#ff8fb7', 400: '#ff6b9d', 500: '#e8528a', 600: '#c93a73' },
        chocolate:{ 50: '#f7efe6', 100: '#e8d4bd', 500: '#8b4513', 700: '#5d2f0c', 900: '#3a1d05' },
        mint:     { 100: '#d4f4e2', 300: '#7dd3a8', 500: '#3eb97a' },
      },
      keyframes: {
        floaty:  { '0%,100%': { transform: 'translateY(0)' }, '50%': { transform: 'translateY(-10px)' } },
        wiggle:  { '0%,100%': { transform: 'rotate(-3deg)' }, '50%': { transform: 'rotate(3deg)' } },
        pop:     { '0%': { transform: 'scale(.85)', opacity: '0' }, '100%': { transform: 'scale(1)', opacity: '1' } },
        drip:    { '0%,100%': { transform: 'translateY(0) scaleY(1)' }, '50%': { transform: 'translateY(4px) scaleY(.95)' } },
        shimmer: { '0%': { backgroundPosition: '-200% 0' }, '100%': { backgroundPosition: '200% 0' } },
        sprinkle:{ '0%': { transform: 'translate(0,0) rotate(0)' }, '100%': { transform: 'translate(20px,-30px) rotate(180deg)' } },
      },
      animation: {
        floaty:  'floaty 4s ease-in-out infinite',
        wiggle:  'wiggle 1.4s ease-in-out infinite',
        pop:     'pop .35s ease-out both',
        drip:    'drip 2.4s ease-in-out infinite',
        shimmer: 'shimmer 2.5s linear infinite',
        sprinkle:'sprinkle 8s linear infinite',
      },
    },
  },
  plugins: [],
}
