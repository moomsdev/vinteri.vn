/**
 * The external dependencies.
 */
const { ProvidePlugin, WatchIgnorePlugin } = require('webpack');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const WebpackAssetsManifest = require('webpack-assets-manifest');
const SpritesmithPlugin = require('webpack-spritesmith');

/**
 * The internal dependencies.
 */
const utils = require('./lib/utils');
const configLoader = require('./config-loader');
const spriteSmith = require('./spritesmith');
const postcss = require('./postcss');
const browsersync = require('./browsersync');

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
        cacheDirectory: true,
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
    new SpritesmithPlugin({
        src: {
            cwd: utils.srcImagesPath('sprite'),
            glob: '*.{jpg,jpeg,png}',
        },
        target: {
            image: utils.distImagesPath('sprite.png'),
            css: utils.srcStylesPath('theme/_sprite.scss'),
        },
        apiOptions: {
            cssImageRef: '../images/sprite.png',
        },
    }),
    spriteSmith,
    browsersync,
    new WebpackAssetsManifest(),
];

/**
 * Export the configuration.
 */
module.exports = {
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
                            importLoaders: 1
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
                        // options: {
                        //     name: file => `/images/[name].${utils.filehash(file).substr(0, 10)}.[ext]`,
                        // },
                        options: {
                            name: '[path][name].[ext]',
                            context: utils.srcPath(), // hoặc sử dụng đường dẫn thích hợp để webpack lấy đường dẫn tương đối chính xác
                            outputPath: 'images', // Đảm bảo hình ảnh được đặt vào thư mục /dist/images/
                            publicPath: '../images', // Đảm bảo đường dẫn trong CSS là đúng
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
    mode: envName,
    cache: true,
    bail: false,
    watch: true,
    devtool: 'source-map',
};
