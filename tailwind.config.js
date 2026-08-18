const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
    content: [
        './resources/views/**/*.blade.php',
        './public/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    50: '#eefaf8',
                    100: '#d5f2ef',
                    200: '#abe4df',
                    300: '#74cfc9',
                    400: '#3bb3ae',
                    500: '#08A899',
                    600: '#086E66',
                    700: '#08574f',
                    800: '#064540',
                    900: '#043D3A',
                    950: '#022321',
                },
                gold: {
                    50: '#fdf8ec',
                    100: '#f9edcd',
                    200: '#f2d996',
                    300: '#eac05d',
                    400: '#e3aa35',
                    500: '#D4911E',
                    600: '#b87417',
                    700: '#935516',
                    800: '#794418',
                    900: '#663919',
                },
            },
            fontFamily: {
                sans: ['"Fira Sans"', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                xl: '1rem',
                '2xl': '1.25rem',
                '3xl': '1.75rem',
            },
            boxShadow: {
                soft: '0 8px 30px rgba(2, 35, 33, 0.08)',
                card: '0 2px 8px rgba(2, 35, 33, 0.05), 0 12px 28px -8px rgba(2, 35, 33, 0.10)',
                glow: '0 0 0 4px rgba(8, 168, 153, 0.15)',
                'glow-gold': '0 0 0 4px rgba(212, 145, 30, 0.15)',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(16px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                'fade-in': {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
                'scale-in': {
                    '0%': { opacity: '0', transform: 'scale(0.96)' },
                    '100%': { opacity: '1', transform: 'scale(1)' },
                },
                float: {
                    '0%, 100%': { transform: 'translateY(0)' },
                    '50%': { transform: 'translateY(-10px)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.6s cubic-bezier(0.22, 1, 0.36, 1) both',
                'fade-in': 'fade-in 0.4s ease both',
                'scale-in': 'scale-in 0.25s cubic-bezier(0.22, 1, 0.36, 1) both',
                float: 'float 5s ease-in-out infinite',
            },
        },
    },
    plugins: [],
};
