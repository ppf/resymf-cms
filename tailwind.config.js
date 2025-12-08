/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./assets/**/*.js",
        "./assets/**/*.vue",
        "./templates/**/*.html.twig",
    ],
    theme: {
        container: {
            center: true,
            padding: "2rem",
            screens: {
                "2xl": "1400px",
            },
        },
        extend: {
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
            },
            colors: {
                border: "hsl(240 5.9% 90%)",
                input: "hsl(240 5.9% 90%)",
                ring: "hsl(240 5.9% 10%)",
                background: "hsl(0 0% 100%)",
                foreground: "hsl(240 10% 3.9%)",
                primary: {
                    DEFAULT: "hsl(240 5.9% 10%)",
                    foreground: "hsl(0 0% 98%)",
                },
                secondary: {
                    DEFAULT: "hsl(240 4.8% 95.9%)",
                    foreground: "hsl(240 5.9% 10%)",
                },
                destructive: {
                    DEFAULT: "hsl(0 84.2% 60.2%)",
                    foreground: "hsl(0 0% 98%)",
                },
                muted: {
                    DEFAULT: "hsl(240 4.8% 95.9%)",
                    foreground: "hsl(240 3.8% 46.1%)",
                },
                accent: {
                    DEFAULT: "hsl(240 4.8% 95.9%)",
                    foreground: "hsl(240 5.9% 10%)",
                },
                popover: {
                    DEFAULT: "hsl(0 0% 100%)",
                    foreground: "hsl(240 10% 3.9%)",
                },
                card: {
                    DEFAULT: "hsl(0 0% 100%)",
                    foreground: "hsl(240 10% 3.9%)",
                },
            },
            borderRadius: {
                lg: "0.5rem",
                md: "calc(0.5rem - 2px)",
                sm: "calc(0.5rem - 4px)",
            },
        },
    },
    plugins: [],
}
