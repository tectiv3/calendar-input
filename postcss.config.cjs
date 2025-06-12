module.exports = {
    plugins: {
        require('postcss-nesting')(),
        require('cssnano')({
            preset: 'default',
        }),
        "postcss-import": {},
        "tailwindcss/nesting": {},
        tailwindcss: {},
        autoprefixer: {},
    },
}
