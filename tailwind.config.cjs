module.exports = {
  content: [
    './resources/**/*.{blade.php,js}',
    './storage/framework/views/*.php',
  ],
  safelist: [
    'mx-auto', 'w-auto', 'max-h-32',
    'text-sm','text-gray-700','dark:text-gray-300','underline'
  ],
  theme: { extend: {} },
  plugins: [],
}
