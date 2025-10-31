module.exports = {
  // Define los entornos donde se ejecutará tu código (navegador, Node.js, etc.)
  env: {
    browser: true,
    es2021: true,
    node: true,
  },
  // Extiende configuraciones recomendadas. El orden es importante.
  extends: [
    'eslint:recommended', // Reglas base recomendadas por ESLint
    'plugin:vue/vue3-essential', // Reglas esenciales para Vue 3
    'plugin:prettier/recommended', // Integra Prettier
  ],
  // Especifica el parser y la versión de ECMAScript
  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module',
  },
  // Define los plugins que estás utilizando
  plugins: ['vue'],
  // Aquí puedes sobreescribir o añadir reglas específicas
  rules: {
    // Ejemplo: Desactiva la regla que obliga a que los componentes de Vue tengan nombres de más de una palabra.
    'vue/multi-word-component-names': 'off',
  },
};