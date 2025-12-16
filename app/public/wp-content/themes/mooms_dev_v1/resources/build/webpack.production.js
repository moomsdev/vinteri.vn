/**
 * The external dependencies.
 */
const { ProvidePlugin, WatchIgnorePlugin } = require('webpack');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const ImageminPlugin = require('imagemin-webpack-plugin').default;
const WebpackAssetsManifest = require('webpack-assets-manifest');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
/**
 * The internal dependencies.
 */
const utils = require('./lib/utils');
const configLoader = require('./config-loader');
const spriteSmith = require('./spritesmith');
const postcss = require('./postcss');

/**
 * Setup the env.
 */
const { env: envName } = utils.detectEnv();

/**
 * Setup babel loader.
 */
const babelLoader = {
    loader: 'babel-loader',
    options: {
        cacheDirectory: false,
        comments: false,
        presets: [
            '@babel/preset-env',
        ],
    },
};

/**
 * Setup webpack plugins.
 */
const plugins = [
    new CleanWebpackPlugin({
        cleanOnceBeforeBuildPatterns: [utils.distPath()],
    }),
    new WatchIgnorePlugin({
        paths: [/node_modules/, /dist/]
    }),
    new ProvidePlugin({
        $: 'jquery',
        jQuery: 'jquery',
    }),
    new MiniCssExtractPlugin({
        filename: 'styles/[name].css',
    }),
    spriteSmith,
    // new UglifyJSPlugin(),
    new ImageminPlugin({
        optipng: { optimizationLevel: 7 },
        gifsicle: { optimizationLevel: 3 },
        svgo: { plugins: [/* svgo plugins */] },
        plugins: [
            require('imagemin-mozjpeg')({
                quality: 100,
            }),
        ],
    }),
    new WebpackAssetsManifest(),
];

/**
 * Export the configuration.
 */
module.exports = {
    optimization: {
        minimize: true,
        minimizer: [
            new TerserPlugin({
                parallel: true,
                terserOptions: {
                    compress: {
                        drop_console: true,
                    },
                },
            }),
            new CssMinimizerPlugin(),
        ],
        splitChunks: {
            chunks: 'all',
        },
    },
    entry: require('./webpack/entry'),
    output: require('./webpack/output'),
    resolve: require('./webpack/resolve'),
    externals: require('./webpack/externals'),
    module: {
        rules: [
            {
                enforce: 'pre',
                test: /\.(js|jsx|css|scss|sass)$/i,
                use: 'import-glob',
            },
            {
                test: utils.themeRootPath('config.json'),
                use: configLoader,
            },
            {
                test: utils.tests.scripts,
                exclude: /node_modules/,
                use: babelLoader,
            },
            {
                test: utils.tests.styles,
                use: [
                    MiniCssExtractPlugin.loader,
                    {
                        loader: 'css-loader',
                        options: {
                            sourceMap: true,
                            importLoaders: 1,
                        },
                    },
                    {
                        loader: 'postcss-loader',
                        options: {
                            postcssOptions: postcss,
                        },
                    },
                    'sass-loader',
                ],
            },
            {
                test: utils.tests.images,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: file => `images/[name].${utils.filehash(file).substr(0, 10)}.[ext]`,
                        },
                    },
                ],
            },
            {
                test: utils.tests.fonts,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: file => `fonts/[name].${utils.filehash(file).substr(0, 10)}.[ext]`,
                        },
                    },
                ],
            },
        ],
    },
    plugins,

    /**
     * Setup the development tools.
     */
    mode: 'production',
    cache: false,
    bail: false,
    watch: false,
    devtool: false,
};
