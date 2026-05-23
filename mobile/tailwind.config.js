// tailwind.config.js
module.exports = {
  content: ["./app/**/*.{js,jsx,ts,tsx}", "./components/**/*.{js,jsx,ts,tsx}"],
  theme: {
    extend: {
      colors: {
        'kardafrica-primary': '#4ECDC4',
        'kardafrica-secondary': '#FF6B6B',
        'kardafrica-dark': '#1a202c',
        'kardafrica-light': '#f7fafc',
      },
    },
  },
  plugins: [],
}
