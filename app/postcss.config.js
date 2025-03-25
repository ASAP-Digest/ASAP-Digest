export default {
    plugins: {
        // Uncomment this line to properly process Tailwind 4 syntax
        '@tailwindcss/postcss': {},
        // autoprefixer is no longer needed as prefixing is handled automatically in v4
        // autoprefixer: {},
        ...(process.env.NODE_ENV === 'production' ? {
            cssnano: {
                preset: ['default', {
                    discardComments: { removeAll: true },
                    // Further optimize by removing duplicates
                    discardDuplicates: true,
                    // Optimize whitespace
                    collapseWhitespace: true
                }]
            }
        } : {})
    },
}; 