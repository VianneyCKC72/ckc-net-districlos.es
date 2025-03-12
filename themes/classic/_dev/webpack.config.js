const webpack = require("webpack");
const path = require("path");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const TerserPlugin = require("terser-webpack-plugin");
const CssoWebpackPlugin = require("csso-webpack-plugin").default;
const LicensePlugin = require("webpack-license-plugin");

let config = {
    entry: {
        theme: [
            "./js/theme.js",
            "./js/modules/facet.js",
            "./js/modules/opartdevis.js",
            "./js/modules/swiper.js",
            "./js/modules/social.js",
            "./js/modules/faq.js",
            "./js/pages/product.js",
            "./js/composents/header.js",
            "./js/pages/homepage.js",
            "./js/pages/category.js",
            "./css/theme.scss"
        ],
        error: ["./css/error.scss"],
    },
    output: {
        path: path.resolve(__dirname, "../assets/js"),
        filename: "[name].js",
    },
    resolve: {
        preferRelative: true,
    },
    module: {
        rules: [
            {
                test: /\.js/,
                loader: "esbuild-loader",
            },
            {
                test: /\.scss$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    {
                        loader: "css-loader",
                        options: {
                            sourceMap: true,
                        },
                    },
                    {
                        loader: "postcss-loader",
                        options: {
                            sourceMap: true,
                            postcssOptions: {
                                plugins: [
                                    require("postcss-sort-media-queries")({
                                        sort: "mobile-first",
                                    }),
                                ],
                            },
                        },
                    },
                    {
                        loader: "sass-loader",
                        options: {
                            sourceMap: true,
                        },
                    },
                ],
            },
            {
                test: /\.(png|woff(2)?|eot|otf|ttf|svg|gif)(\?[a-z0-9=\.]+)?$/,
                type: "asset/resource",
                generator: {
                    filename: "../css/[hash][ext]",
                },
            },
            {
                test: /\.css$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    "style-loader",
                    {
                        loader: "css-loader",
                        options: {
                            sourceMap: true,
                        },
                    },
                    {
                        loader: "postcss-loader",
                        options: {
                            sourceMap: true,
                            postcssOptions: {
                                plugins: [
                                    require("postcss-sort-media-queries")({
                                        sort: "mobile-first",
                                    }),
                                ],
                            },
                        },
                    },
                ],
            },
        ],
    },
    devtool: "source-map",
    externals: {
        prestashop: "prestashop",
        $: "$",
        jquery: "jQuery",
        Swiper: "swiper",
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: path.join("..", "css", "[name].css"),
        }),
        new CssoWebpackPlugin({
            forceMediaMerge: true,
        }),
        new LicensePlugin({
            outputFilename: "thirdPartyNotice.json",
            licenseOverrides: {
                "bootstrap-touchspin@3.1.1": "Apache-2.0",
            },
            replenishDefaultLicenseTexts: true,
        }),
    ],
};

if (process.env.NODE_ENV === "production") {
    config.optimization = {
        minimize: true,
        minimizer: [
            new TerserPlugin({
                parallel: true,
                extractComments: false,
            }),
        ],
    };
}

module.exports = config;
