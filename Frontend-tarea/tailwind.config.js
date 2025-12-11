/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{html,ts}", // Escanea todos tus componentes Angular
  ],
  theme: {
    extend: {
      colors: {
        // Paleta Corporativa "Slate" (Sobria y Profesional)
        primary: '#0f172a',    // Slate-900 (Textos principales / Header)
        secondary: '#64748b',  // Slate-500 (Textos secundarios)
        background: '#f8fafc', // Slate-50 (Fondo general - descansa la vista)
        surface: '#ffffff',    // White (Tarjetas)
        
        // Colores de Estado (Lógica V6)
        success: '#059669',    // Emerald-600 (Completada/Validada)
        warning: '#d97706',    // Amber-600 (Por Validar)
        danger: '#dc2626',     // Red-600 (Vencida/Rechazada)
        info: '#2563eb',       // Blue-600 (En Progreso)
      }
    },
  },
  plugins: [],
}