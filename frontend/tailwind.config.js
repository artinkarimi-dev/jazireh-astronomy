export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Vazirmatn', 'Tahoma', 'Arial', 'sans-serif']
      },
      colors: {
        space: {
          950: '#020814',
          900: '#06101f',
          850: '#081426',
          800: '#0b1b31'
        },
        aurora: {
          300: '#93c5fd',
          400: '#60a5fa',
          500: '#3b82f6'
        }
      },
      boxShadow: {
        glow: '0 0 45px rgba(251,191,36,.16)',
        panel: '0 18px 60px rgba(0,0,0,.32)'
      },
      backgroundImage: {
        'space-grid': 'linear-gradient(rgba(251,191,36,.035) 1px, transparent 1px), linear-gradient(90deg, rgba(251,191,36,.035) 1px, transparent 1px)'
      },
      animation: {
        float: 'float 7s ease-in-out infinite',
        pulseSoft: 'pulseSoft 3s ease-in-out infinite',
        radar: 'radar 4s linear infinite',
        drift: 'drift 26s linear infinite'
      },
      keyframes: {
        float: {
          '0%,100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-12px)' }
        },
        pulseSoft: {
          '0%,100%': { opacity: '.55' },
          '50%': { opacity: '1' }
        },
        radar: {
          from: { transform: 'rotate(0deg)' },
          to: { transform: 'rotate(360deg)' }
        },
        drift: {
          from: { transform: 'translate3d(0,0,0)' },
          to: { transform: 'translate3d(-8%,3%,0)' }
        }
      }
    }
  },
  plugins: []
}
