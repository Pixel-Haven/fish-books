import "./bootstrap";
import 'vue3-toastify/dist/index.css';

import "../css/app.css";

import setupDayJS from "./utils/dayjs";
import { createInertiaApp } from '@inertiajs/vue3'
import { createApp, h, DefineComponent } from "vue";
import { ZiggyVue } from "../../vendor/tightenco/ziggy";
import type { Config as ZiggyConfig } from 'ziggy-js';
import { formatDate, timeAgo } from "./utils/functions";
import Vue3Toastify, { type ToastContainerOptions } from 'vue3-toastify';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

setupDayJS();

const appName =
    document.querySelector<HTMLMetaElement>('meta[name="app-name"]')?.content ??
    import.meta.env.VITE_APP_NAME ??
    window.document.getElementsByTagName('title')[0]?.innerText ??
    'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>("./pages/**/*.vue")
        ),
    setup({ el, App, props, plugin }) {
        const ziggy = (window as typeof window & { Ziggy?: ZiggyConfig }).Ziggy

        const vueApp = createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(Vue3Toastify, {
                autoClose: 3000,
            } as ToastContainerOptions)
            .mixin({
                methods: {
                    timeAgo,
                    formatDate,
                }
            });

        if (ziggy) {
            vueApp.use(ZiggyVue, ziggy)
        }

        vueApp.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
