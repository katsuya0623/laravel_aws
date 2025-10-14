module.exports = {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './storage/framework/views/*.php'
  ],
  theme: {
    extend: {},
  },
  plugins: [
    require('daisyui'),   // ★ ここ追加！
  ],
  daisyui: {
    themes: ['light', 'dark'], // 好きなテーマ（corporate, retro, synthwave 等も可）
  },
}
