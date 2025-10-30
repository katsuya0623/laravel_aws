// tailwind.config.js
module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './storage/framework/views/*.php',
  ],
  theme: {
    extend: {
      colors: {
        brand: '#C23A41',         // ← 追加
        'brand-dark': '#B23339',  // ← hover用（お好み）
      },
    },
  },
  plugins: [require('daisyui')],
  daisyui: { themes: ['light', 'dark'] },
}
